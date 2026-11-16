<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctPeriodCloseChecklist;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\AcctFiscalYear;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\Accounting\GlRecurringTemplate;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingGeneralLedgerService
{
    public function __construct(
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Create or update a Recurring Journal Template.
     */
    public function saveRecurringTemplate(array $data): GlRecurringTemplate
    {
        $companyId = (string) ($data['company_id'] ?? '1');

        if (empty($data['template_name'])) {
            throw new InvalidArgumentException('Template name is required.');
        }

        $code = !empty($data['template_code'])
            ? (string) $data['template_code']
            : $this->numberingService->generateNextNumber($companyId, 'TMPL', 'REC');

        $template = GlRecurringTemplate::updateOrCreate(
            [
                'template_id' => $data['template_id'] ?? null,
            ],
            [
                'company_id' => $companyId,
                'template_code' => $code,
                'template_name' => $data['template_name'],
                'journal_type' => $data['journal_type'] ?? 'RECURRING',
                'frequency' => strtoupper((string) ($data['frequency'] ?? 'MONTHLY')),
                'next_run_date' => $data['next_run_date'] ?? now()->toDateString(),
                'last_run_date' => $data['last_run_date'] ?? null,
                'auto_post' => (bool) ($data['auto_post'] ?? false),
                'default_branch_id' => $data['default_branch_id'] ?? 'BR-01',
                'description' => $data['description'] ?? null,
                'lines_json' => $data['lines_json'] ?? [],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );

        return $template;
    }

    /**
     * Execute all due Recurring Journal Templates up to a given date.
     */
    public function generateRecurringJournals(string $companyId, ?string $runDate = null): array
    {
        $date = $runDate ? Carbon::parse($runDate)->toDateString() : now()->toDateString();
        $actor = Auth::user()->name ?? 'Recurring Engine';

        $templates = GlRecurringTemplate::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $date)
            ->get();

        $generatedJournals = [];

        foreach ($templates as $tmpl) {
            DB::transaction(function () use ($tmpl, $date, $companyId, $actor, &$generatedJournals) {
                $lines = (array) ($tmpl->lines_json ?? []);
                if (count($lines) < 2) {
                    return;
                }

                $targetStatus = $tmpl->auto_post ? 'POSTED' : 'DRAFT';

                $journal = $this->postingService->saveDraftOrPostJournal([
                    'company_id' => $companyId,
                    'branch_id' => $tmpl->default_branch_id,
                    'journal_date' => $date,
                    'journal_type' => $tmpl->journal_type ?: 'RECURRING',
                    'source_module' => 'GL',
                    'source_document_type' => 'RECURRING_TEMPLATE',
                    'source_document_id' => $tmpl->template_id,
                    'source_document_number' => $tmpl->template_code,
                    'currency_code' => 'PHP',
                    'exchange_rate' => 1.0,
                    'is_system_generated' => true,
                    'description' => "Recurring Journal from Template {$tmpl->template_code}: {$tmpl->template_name}",
                    'acted_by' => $actor,
                ], $lines, $targetStatus);

                // Advance next run date
                $nextCarbon = Carbon::parse($tmpl->next_run_date);
                match ($tmpl->frequency) {
                    'QUARTERLY' => $nextCarbon->addMonths(3),
                    'ANNUALLY' => $nextCarbon->addYear(),
                    default => $nextCarbon->addMonth(),
                };

                $tmpl->last_run_date = $date;
                $tmpl->next_run_date = $nextCarbon->toDateString();
                $tmpl->save();

                $generatedJournals[] = $journal;
            });
        }

        return $generatedJournals;
    }

    /**
     * Execute Year-End Closing:
     * Clears all P&L (Revenue, COGS, Expense) accounts into RETAINED_EARNINGS and marks the fiscal year closed.
     */
    public function executeYearEndClosing(string $companyId, string $fiscalYearId): GlJournalHeader
    {
        $actor = Auth::user()->name ?? 'Chief Financial Officer';

        return DB::transaction(function () use ($companyId, $fiscalYearId, $actor) {
            $year = AcctFiscalYear::where('company_id', $companyId)->where('fiscal_year_id', $fiscalYearId)->lockForUpdate()->firstOrFail();
            if ($year->status === 'CLOSED') {
                throw new DomainException("Fiscal Year [{$year->year_name}] has already been closed.");
            }

            $closingDate = $year->end_date ? $year->end_date->toDateString() : now()->toDateString();
            $retainedEarnings = $this->postingService->resolveSystemRoleAccount($companyId, 'RETAINED_EARNINGS');

            // Find all posted journal lines belonging to this fiscal year
            $plLines = GlJournalLine::join('gl_journal_headers', 'gl_journal_lines.journal_id', '=', 'gl_journal_headers.journal_id')
                ->join('chart_of_accounts', 'gl_journal_lines.account_id', '=', 'chart_of_accounts.account_id')
                ->where('gl_journal_headers.company_id', $companyId)
                ->where('gl_journal_headers.status', 'POSTED')
                ->whereBetween('gl_journal_headers.journal_date', [$year->start_date->toDateString(), $year->end_date->toDateString()])
                ->whereIn('chart_of_accounts.account_type', ['REVENUE', 'COGS', 'EXPENSE'])
                ->select(
                    'gl_journal_lines.account_id',
                    'chart_of_accounts.account_code',
                    'chart_of_accounts.account_name',
                    'chart_of_accounts.account_type',
                    'chart_of_accounts.normal_balance',
                    DB::raw('SUM(gl_journal_lines.base_debit) as total_debit'),
                    DB::raw('SUM(gl_journal_lines.base_credit) as total_credit')
                )
                ->groupBy('gl_journal_lines.account_id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'chart_of_accounts.account_type', 'chart_of_accounts.normal_balance')
                ->get();

            $closingJournalLines = [];
            $totalNetIncome = 0.0;

            foreach ($plLines as $line) {
                $dr = (float) $line->total_debit;
                $cr = (float) $line->total_credit;

                if ($line->account_type === 'REVENUE') {
                    // Normal balance is CREDIT. Net revenue = CR - DR.
                    $netCredit = round($cr - $dr, 4);
                    if (abs($netCredit) > 0.0001) {
                        // To close revenue account to zero, DEBIT revenue
                        $closingJournalLines[] = [
                            'account_id' => $line->account_id,
                            'branch_id' => 'BR-01',
                            'transaction_debit' => $netCredit > 0 ? $netCredit : 0.0,
                            'transaction_credit' => $netCredit < 0 ? abs($netCredit) : 0.0,
                            'description' => "Close Revenue: {$line->account_code} - {$line->account_name}",
                        ];
                        $totalNetIncome += $netCredit;
                    }
                } else {
                    // Normal balance is DEBIT (COGS or EXPENSE). Net expense = DR - CR.
                    $netDebit = round($dr - $cr, 4);
                    if (abs($netDebit) > 0.0001) {
                        // To close expense account to zero, CREDIT expense
                        $closingJournalLines[] = [
                            'account_id' => $line->account_id,
                            'branch_id' => 'BR-01',
                            'transaction_debit' => $netDebit < 0 ? abs($netDebit) : 0.0,
                            'transaction_credit' => $netDebit > 0 ? $netDebit : 0.0,
                            'description' => "Close Expense: {$line->account_code} - {$line->account_name}",
                        ];
                        $totalNetIncome -= $netDebit;
                    }
                }
            }

            if (empty($closingJournalLines)) {
                throw new DomainException("No P&L balances found to close for fiscal year [{$year->year_name}].");
            }

            // Transfer net income / net loss to Retained Earnings
            if ($totalNetIncome > 0.0001) {
                // Net Profit -> Credit Retained Earnings
                $closingJournalLines[] = [
                    'account_id' => $retainedEarnings->account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $totalNetIncome,
                    'description' => "Year-End Net Income transferred to Retained Earnings ({$year->year_name})",
                ];
            } elseif ($totalNetIncome < -0.0001) {
                // Net Loss -> Debit Retained Earnings
                $closingJournalLines[] = [
                    'account_id' => $retainedEarnings->account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => abs($totalNetIncome),
                    'transaction_credit' => 0.0,
                    'description' => "Year-End Net Loss transferred from Retained Earnings ({$year->year_name})",
                ];
            }

            // Post Closing Journal Entry
            $closingJournal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $closingDate,
                'journal_type' => 'CLOSING',
                'source_module' => 'GL',
                'source_document_type' => 'YEAR_END_CLOSING',
                'source_document_number' => "YE-CLOSE-{$year->year_code}",
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Year-End Closing Journal for {$year->year_name} to Retained Earnings",
                'acted_by' => $actor,
            ], $closingJournalLines, 'POSTED');

            // Lock all periods and close the fiscal year
            AcctFiscalPeriod::where('fiscal_year_id', $year->fiscal_year_id)
                ->update(['status' => 'CLOSED']);

            $year->status = 'CLOSED';
            $year->save();

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: 'BR-01',
                module: 'GL',
                documentType: 'YEAR_END_CLOSING',
                documentId: $year->fiscal_year_id,
                documentNumber: $year->year_code,
                action: 'CLOSED',
                summary: "Fiscal Year {$year->year_name} closed. Net Profit/Loss transferred to Retained Earnings: ₱" . number_format($totalNetIncome, 2),
                actedBy: $actor
            );

            return $closingJournal;
        });
    }

    /**
     * Update period close checklist items and lock/unlock controls.
     */
    public function updatePeriodCloseChecklist(string $companyId, string $periodId, array $data): AcctPeriodCloseChecklist
    {
        $actor = Auth::user()->name ?? 'Financial Controller';

        return DB::transaction(function () use ($companyId, $periodId, $data, $actor) {
            $period = AcctFiscalPeriod::where('company_id', $companyId)->where('period_id', $periodId)->firstOrFail();

            $checklist = AcctPeriodCloseChecklist::firstOrNew([
                'company_id' => $companyId,
                'fiscal_period_id' => $periodId,
            ]);

            if (isset($data['bank_reconciliation_done'])) {
                $checklist->bank_reconciliation_done = (bool) $data['bank_reconciliation_done'];
            }
            if (isset($data['depreciation_posted'])) {
                $checklist->depreciation_posted = (bool) $data['depreciation_posted'];
            }
            if (isset($data['inventory_reconciled'])) {
                $checklist->inventory_reconciled = (bool) $data['inventory_reconciled'];
            }
            if (isset($data['ap_ar_reconciled'])) {
                $checklist->ap_ar_reconciled = (bool) $data['ap_ar_reconciled'];
            }
            if (isset($data['recurring_posted'])) {
                $checklist->recurring_posted = (bool) $data['recurring_posted'];
            }
            if (isset($data['fx_revaluation_done'])) {
                $checklist->fx_revaluation_done = (bool) $data['fx_revaluation_done'];
            }

            if (isset($data['is_locked'])) {
                $isLocked = (bool) $data['is_locked'];
                $checklist->is_locked = $isLocked;
                $checklist->locked_by = $isLocked ? $actor : null;
                $checklist->locked_at = $isLocked ? now() : null;

                $period->status = $isLocked ? 'LOCKED' : 'OPEN';
                $period->locked_by = $isLocked ? $actor : null;
                $period->locked_at = $isLocked ? now() : null;
                $period->save();
            }

            if (isset($data['notes'])) {
                $checklist->notes = $data['notes'];
            }

            $checklist->save();

            return $checklist;
        });
    }

    /**
     * General Ledger Account Inquiry drill-down with running net balances.
     */
    public function getGlInquiry(string $companyId, string $accountId, ?string $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = ChartOfAccount::where('company_id', $companyId)->where('account_id', $accountId)->firstOrFail();
        $isNormalDebit = strtoupper((string) $account->normal_balance) === 'DEBIT';

        // 1. Calculate Opening Balance before $startDate
        $openingBalance = 0.0;
        if ($startDate) {
            $priorLinesQuery = GlJournalLine::join('gl_journal_headers', 'gl_journal_lines.journal_id', '=', 'gl_journal_headers.journal_id')
                ->where('gl_journal_headers.company_id', $companyId)
                ->where('gl_journal_headers.status', 'POSTED')
                ->where('gl_journal_lines.account_id', $accountId)
                ->where('gl_journal_headers.journal_date', '<', $startDate);

            if ($branchId) {
                $priorLinesQuery->where('gl_journal_lines.branch_id', $branchId);
            }

            $priorSum = $priorLinesQuery->select(
                DB::raw('SUM(gl_journal_lines.base_debit) as dr_sum'),
                DB::raw('SUM(gl_journal_lines.base_credit) as cr_sum')
            )->first();

            $priorDr = (float) ($priorSum->dr_sum ?? 0.0);
            $priorCr = (float) ($priorSum->cr_sum ?? 0.0);
            $openingBalance = $isNormalDebit ? ($priorDr - $priorCr) : ($priorCr - $priorDr);
        }

        // 2. Fetch Period Lines
        $linesQuery = GlJournalLine::join('gl_journal_headers', 'gl_journal_lines.journal_id', '=', 'gl_journal_headers.journal_id')
            ->where('gl_journal_headers.company_id', $companyId)
            ->where('gl_journal_headers.status', 'POSTED')
            ->where('gl_journal_lines.account_id', $accountId)
            ->select(
                'gl_journal_lines.*',
                'gl_journal_headers.journal_number',
                'gl_journal_headers.journal_date',
                'gl_journal_headers.journal_type',
                'gl_journal_headers.source_module',
                'gl_journal_headers.source_document_type',
                'gl_journal_headers.source_document_number'
            )
            ->orderBy('gl_journal_headers.journal_date', 'asc')
            ->orderBy('gl_journal_headers.created_at', 'asc');

        if ($branchId) {
            $linesQuery->where('gl_journal_lines.branch_id', $branchId);
        }
        if ($startDate) {
            $linesQuery->where('gl_journal_headers.journal_date', '>=', $startDate);
        }
        if ($endDate) {
            $linesQuery->where('gl_journal_headers.journal_date', '<=', $endDate);
        }

        $rawLines = $linesQuery->get();

        $runningBalance = $openingBalance;
        $totalDr = 0.0;
        $totalCr = 0.0;
        $formattedLines = [];

        foreach ($rawLines as $row) {
            $dr = (float) $row->base_debit;
            $cr = (float) $row->base_credit;

            $totalDr += $dr;
            $totalCr += $cr;

            if ($isNormalDebit) {
                $runningBalance += ($dr - $cr);
            } else {
                $runningBalance += ($cr - $dr);
            }

            $formattedLines[] = [
                'line_id' => $row->line_id,
                'journal_id' => $row->journal_id,
                'journal_number' => $row->journal_number,
                'journal_date' => $row->journal_date,
                'journal_type' => $row->journal_type,
                'source_module' => $row->source_module,
                'source_document_type' => $row->source_document_type,
                'source_document_number' => $row->source_document_number,
                'reference_number' => $row->source_document_number,
                'branch_id' => $row->branch_id,
                'department_id' => $row->department_id,
                'project_code' => $row->project_code,
                'description' => $row->description,
                'debit' => $dr,
                'credit' => $cr,
                'running_balance' => round($runningBalance, 4),
            ];
        }

        return [
            'account' => [
                'account_id' => $account->account_id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'normal_balance' => $account->normal_balance,
                'system_role' => $account->system_role,
            ],
            'opening_balance' => round($openingBalance, 4),
            'total_debit' => round($totalDr, 4),
            'total_credit' => round($totalCr, 4),
            'closing_balance' => round($runningBalance, 4),
            'lines' => $formattedLines,
        ];
    }
}

<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctAuditLog;
use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlAccountPeriodBalance;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountingPostingService
{
    public function __construct(
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Create or update a DRAFT journal, or post directly when targetStatus is POSTED.
     * Enforces open fiscal period, multi-dimension lines, dual currency, FX rounding auto-balance, and immutability.
     */
    public function saveDraftOrPostJournal(array $headerData, array $linesData, string $targetStatus = 'DRAFT'): GlJournalHeader
    {
        $targetStatus = strtoupper(trim($targetStatus));
        if (!in_array($targetStatus, GlJournalHeader::STATUSES, true)) {
            throw new InvalidArgumentException("Invalid journal status [{$targetStatus}].");
        }
        if ($targetStatus === 'REVERSED') {
            throw new InvalidArgumentException('Use reverseJournal() to create a REVERSAL entry.');
        }

        if (empty($linesData) || count($linesData) < 2) {
            throw new InvalidArgumentException('A journal entry must contain at least 2 lines.');
        }

        $companyId = (string) ($headerData['company_id'] ?? '1');
        $journalDate = Carbon::parse($headerData['journal_date'] ?? now()->toDateString())->toDateString();
        $currencyCode = strtoupper((string) ($headerData['currency_code'] ?? 'PHP'));
        $headerExchangeRate = max(0.000001, (float) ($headerData['exchange_rate'] ?? 1.0));
        $actor = $headerData['acted_by'] ?? (Auth::user()->name ?? 'Accounting Officer');

        return DB::transaction(function () use ($headerData, $linesData, $targetStatus, $companyId, $journalDate, $currencyCode, $headerExchangeRate, $actor) {
            // 1. Validate Fiscal Period
            $period = $this->resolveFiscalPeriod($companyId, $journalDate, $targetStatus === 'POSTED');

            // 2. Load or initialize Journal Header
            $journal = null;
            if (!empty($headerData['journal_id'])) {
                $journal = GlJournalHeader::where('journal_id', $headerData['journal_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array(strtoupper($journal->status), ['POSTED', 'REVERSED'], true)) {
                    throw new DomainException("Immutable Posted Record: Cannot edit {$journal->status} journal [{$journal->journal_number}].");
                }
            }

            // 3. Normalize lines, validate accounts, compute dual-currency base amounts
            $normalizedLines = [];
            $txDebitTotal = 0.0;
            $txCreditTotal = 0.0;
            $baseDebitTotal = 0.0;
            $baseCreditTotal = 0.0;

            $accountIds = array_values(array_unique(array_filter(array_column($linesData, 'account_id'))));
            $accountsById = ChartOfAccount::where('company_id', $companyId)
                ->whereIn('account_id', $accountIds)
                ->get()
                ->keyBy('account_id');

            $lineNo = 1;
            foreach ($linesData as $rawLine) {
                $accountId = $rawLine['account_id'] ?? null;
                $account = $accountId ? ($accountsById[$accountId] ?? null) : null;
                if (!$account && !empty($rawLine['system_role'])) {
                    $account = ChartOfAccount::where('company_id', $companyId)
                        ->where('system_role', strtoupper($rawLine['system_role']))
                        ->where('is_active', true)
                        ->first();
                }
                if (!$account && !empty($rawLine['account_code'])) {
                    $account = ChartOfAccount::where('company_id', $companyId)
                        ->where('account_code', $rawLine['account_code'])
                        ->first();
                }

                if (!$account) {
                    throw new InvalidArgumentException("Line #{$lineNo}: Valid Chart of Accounts account is required.");
                }
                if (!$account->is_active) {
                    throw new InvalidArgumentException("Line #{$lineNo}: Account [{$account->account_code}] is inactive.");
                }

                $txDebit = round(max(0, (float) ($rawLine['transaction_debit'] ?? $rawLine['debit'] ?? 0)), 4);
                $txCredit = round(max(0, (float) ($rawLine['transaction_credit'] ?? $rawLine['credit'] ?? 0)), 4);
                if ($txDebit > 0 && $txCredit > 0) {
                    throw new InvalidArgumentException("Line #{$lineNo}: A single journal line cannot carry both Debit and Credit amounts.");
                }
                if ($txDebit <= 0 && $txCredit <= 0) {
                    continue;
                }

                $lineCurrency = strtoupper((string) ($rawLine['transaction_currency_code'] ?? $currencyCode));
                $lineRate = max(0.000001, (float) ($rawLine['exchange_rate'] ?? $headerExchangeRate));

                $baseDebit = isset($rawLine['base_debit']) && (float) $rawLine['base_debit'] > 0
                    ? round((float) $rawLine['base_debit'], 2)
                    : round($txDebit * $lineRate, 2);
                $baseCredit = isset($rawLine['base_credit']) && (float) $rawLine['base_credit'] > 0
                    ? round((float) $rawLine['base_credit'], 2)
                    : round($txCredit * $lineRate, 2);

                $txDebitTotal += $txDebit;
                $txCreditTotal += $txCredit;
                $baseDebitTotal += $baseDebit;
                $baseCreditTotal += $baseCredit;

                $normalizedLines[] = [
                    'journal_line_id' => (string) Str::uuid(),
                    'line_number' => $lineNo++,
                    'account_id' => $account->account_id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'company_id' => $companyId,
                    'branch_id' => (string) ($rawLine['branch_id'] ?? $headerData['branch_id'] ?? 'BR-01'),
                    'department_id' => $rawLine['department_id'] ?? null,
                    'project_code' => $rawLine['project_code'] ?? null,
                    'partner_type' => $rawLine['partner_type'] ?? null,
                    'partner_id' => $rawLine['partner_id'] ?? null,
                    'partner_name' => $rawLine['partner_name'] ?? null,
                    'tax_code' => $rawLine['tax_code'] ?? null,
                    'description' => $rawLine['description'] ?? ($headerData['description'] ?? null),
                    'transaction_currency_code' => $lineCurrency,
                    'exchange_rate' => $lineRate,
                    'transaction_debit' => $txDebit,
                    'transaction_credit' => $txCredit,
                    'base_debit' => $baseDebit,
                    'base_credit' => $baseCredit,
                    'is_fx_rounding_line' => (bool) ($rawLine['is_fx_rounding_line'] ?? false),
                ];
            }

            if (count($normalizedLines) < 2) {
                throw new InvalidArgumentException('Journal must have at least 2 non-zero lines.');
            }

            if (round($txDebitTotal, 2) !== round($txCreditTotal, 2)) {
                throw new InvalidArgumentException(sprintf(
                    'Journal is out of balance in transaction currency: Debits (%.2f) != Credits (%.2f).',
                    $txDebitTotal,
                    $txCreditTotal
                ));
            }

            // 4. FX Conversion Rounding Auto-Balancing Line (centavo drift <= 0.05)
            $baseDiff = round($baseDebitTotal - $baseCreditTotal, 2);
            if (abs($baseDiff) > 0.0001) {
                if (abs($baseDiff) <= 0.05 && $currencyCode !== 'PHP') {
                    $fxAccount = $this->resolveSystemRoleAccount($companyId, 'FX_GAIN_LOSS_REALIZED');
                    $roundDebit = $baseDiff < 0 ? abs($baseDiff) : 0.00;
                    $roundCredit = $baseDiff > 0 ? abs($baseDiff) : 0.00;
                    $normalizedLines[] = [
                        'journal_line_id' => (string) Str::uuid(),
                        'line_number' => $lineNo++,
                        'account_id' => $fxAccount->account_id,
                        'account_code' => $fxAccount->account_code,
                        'account_name' => $fxAccount->account_name,
                        'company_id' => $companyId,
                        'branch_id' => (string) ($headerData['branch_id'] ?? $normalizedLines[0]['branch_id'] ?? 'BR-01'),
                        'department_id' => null,
                        'project_code' => null,
                        'partner_type' => null,
                        'partner_id' => null,
                        'partner_name' => null,
                        'tax_code' => null,
                        'description' => 'Auto FX conversion rounding adjustment',
                        'transaction_currency_code' => $currencyCode,
                        'exchange_rate' => $headerExchangeRate,
                        'transaction_debit' => 0.0000,
                        'transaction_credit' => 0.0000,
                        'base_debit' => $roundDebit,
                        'base_credit' => $roundCredit,
                        'is_fx_rounding_line' => true,
                    ];
                    $baseDebitTotal = round($baseDebitTotal + $roundDebit, 2);
                    $baseCreditTotal = round($baseCreditTotal + $roundCredit, 2);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Journal is out of balance in base currency: Debits (%.2f) != Credits (%.2f).',
                        $baseDebitTotal,
                        $baseCreditTotal
                    ));
                }
            }

            // 5. Create or Update Header
            $journalNumber = $journal
                ? $journal->journal_number
                : ($headerData['journal_number'] ?? $this->numberingService->generateNextNumber($companyId, 'JE', 'JE'));

            $headerPayload = [
                'journal_number' => $journalNumber,
                'company_id' => $companyId,
                'branch_id' => (string) ($headerData['branch_id'] ?? $normalizedLines[0]['branch_id'] ?? 'BR-01'),
                'fiscal_period_id' => $period?->period_id,
                'journal_date' => $journalDate,
                'journal_type' => strtoupper((string) ($headerData['journal_type'] ?? 'MANUAL')),
                'source_module' => strtoupper((string) ($headerData['source_module'] ?? 'GL')),
                'source_document_type' => $headerData['source_document_type'] ?? null,
                'source_document_id' => $headerData['source_document_id'] ?? null,
                'source_document_number' => $headerData['source_document_number'] ?? null,
                'currency_code' => $currencyCode,
                'exchange_rate' => $headerExchangeRate,
                'total_transaction_debit' => round($txDebitTotal, 4),
                'total_transaction_credit' => round($txCreditTotal, 4),
                'total_debit' => round($baseDebitTotal, 4),
                'total_credit' => round($baseCreditTotal, 4),
                'status' => $targetStatus,
                'is_system_generated' => (bool) ($headerData['is_system_generated'] ?? false),
                'reversed_from_journal_id' => $headerData['reversed_from_journal_id'] ?? null,
                'description' => $headerData['description'] ?? null,
                'created_by' => $journal ? $journal->created_by : $actor,
            ];

            if ($targetStatus === 'APPROVED') {
                $headerPayload['approved_by'] = $actor;
                $headerPayload['approved_at'] = now();
            } elseif ($targetStatus === 'POSTED') {
                $headerPayload['approved_by'] = $headerData['approved_by'] ?? ($journal?->approved_by ?: $actor);
                $headerPayload['approved_at'] = $journal?->approved_at ?? now();
                $headerPayload['posted_by'] = $actor;
                $headerPayload['posted_at'] = now();
            }

            if ($journal) {
                $journal->fill($headerPayload);
                $journal->save();
                GlJournalLine::where('journal_id', $journal->journal_id)->delete();
            } else {
                $journal = GlJournalHeader::create($headerPayload);
            }

            foreach ($normalizedLines as $lineRow) {
                $lineRow['journal_id'] = $journal->journal_id;
                GlJournalLine::create($lineRow);
            }

            // 6. Update Period Balance Rollups when POSTED
            if ($targetStatus === 'POSTED' && $period) {
                $this->applyLinesToPeriodBalances($companyId, $period->period_id, $normalizedLines);
            }

            // 7. Write Immutable Audit Log
            $this->recordAudit(
                companyId: $companyId,
                branchId: $journal->branch_id,
                module: $journal->source_module ?: 'GL',
                documentType: 'GL_JOURNAL',
                documentId: $journal->journal_id,
                documentNumber: $journal->journal_number,
                action: $targetStatus === 'POSTED' ? 'POSTED' : ($journal->wasRecentlyCreated ? 'CREATED' : 'UPDATED'),
                summary: "Journal {$journal->journal_number} ({$journal->journal_type}) saved as {$targetStatus} — ₱" . number_format((float) $journal->total_debit, 2),
                actedBy: $actor
            );

            return $journal->fresh('lines');
        });
    }

    /**
     * Transition an existing DRAFT or APPROVED journal forward to APPROVED or POSTED.
     */
    public function transitionJournalStatus(string $journalId, string $targetStatus, ?string $actedBy = null): GlJournalHeader
    {
        $targetStatus = strtoupper(trim($targetStatus));
        $actor = $actedBy ?? (Auth::user()->name ?? 'Accounting Approver');

        return DB::transaction(function () use ($journalId, $targetStatus, $actor) {
            $journal = GlJournalHeader::with('lines')->where('journal_id', $journalId)->lockForUpdate()->firstOrFail();
            $current = strtoupper($journal->status);

            $allowed = [
                'DRAFT' => ['APPROVED', 'POSTED'],
                'APPROVED' => ['DRAFT', 'POSTED'],
            ];

            if (!in_array($targetStatus, $allowed[$current] ?? [], true)) {
                throw new DomainException("Cannot transition journal [{$journal->journal_number}] from {$current} to {$targetStatus}.");
            }

            if ($targetStatus === 'POSTED') {
                $period = $this->resolveFiscalPeriod($journal->company_id, $journal->journal_date->toDateString(), true);
                if (round((float) $journal->total_debit, 2) !== round((float) $journal->total_credit, 2) || (float) $journal->total_debit <= 0) {
                    throw new DomainException("Journal [{$journal->journal_number}] is unbalanced or zero and cannot be posted.");
                }
                $journal->fiscal_period_id = $period?->period_id;
                $journal->approved_by = $journal->approved_by ?: $actor;
                $journal->approved_at = $journal->approved_at ?: now();
                $journal->posted_by = $actor;
                $journal->posted_at = now();
                $journal->status = 'POSTED';
                $journal->save();

                if ($period) {
                    $this->applyLinesToPeriodBalances($journal->company_id, $period->period_id, $journal->lines->toArray());
                }
            } elseif ($targetStatus === 'APPROVED') {
                $journal->approved_by = $actor;
                $journal->approved_at = now();
                $journal->status = 'APPROVED';
                $journal->save();
            } else {
                $journal->status = 'DRAFT';
                $journal->save();
            }

            $this->recordAudit(
                companyId: $journal->company_id,
                branchId: $journal->branch_id,
                module: 'GL',
                documentType: 'GL_JOURNAL',
                documentId: $journal->journal_id,
                documentNumber: $journal->journal_number,
                action: $targetStatus,
                summary: "Journal {$journal->journal_number} transitioned from {$current} to {$targetStatus}.",
                actedBy: $actor
            );

            return $journal->fresh('lines');
        });
    }

    /**
     * Create a mirror REVERSAL journal for an immutable POSTED journal.
     */
    public function reverseJournal(string $journalId, string $reason, ?string $reversalDate = null, ?string $actedBy = null): GlJournalHeader
    {
        $actor = $actedBy ?? (Auth::user()->name ?? 'Accounting Controller');
        $cleanReason = trim($reason) !== '' ? trim($reason) : 'Accounting reversal entry';

        return DB::transaction(function () use ($journalId, $cleanReason, $reversalDate, $actor) {
            $original = GlJournalHeader::with('lines')
                ->where('journal_id', $journalId)
                ->lockForUpdate()
                ->firstOrFail();

            if (strtoupper($original->status) !== 'POSTED') {
                throw new DomainException("Only POSTED journals can be reversed (current status: {$original->status}).");
            }

            $revDate = $reversalDate
                ? Carbon::parse($reversalDate)->toDateString()
                : now()->toDateString();

            $mirroredLines = $original->lines->map(function (GlJournalLine $line) use ($cleanReason) {
                return [
                    'account_id' => $line->account_id,
                    'account_code' => $line->account_code,
                    'account_name' => $line->account_name,
                    'branch_id' => $line->branch_id,
                    'department_id' => $line->department_id,
                    'project_code' => $line->project_code,
                    'partner_type' => $line->partner_type,
                    'partner_id' => $line->partner_id,
                    'partner_name' => $line->partner_name,
                    'tax_code' => $line->tax_code,
                    'description' => "Reversal: {$cleanReason}" . ($line->description ? " ({$line->description})" : ''),
                    'transaction_currency_code' => $line->transaction_currency_code,
                    'exchange_rate' => (float) $line->exchange_rate,
                    'transaction_debit' => (float) $line->transaction_credit,
                    'transaction_credit' => (float) $line->transaction_debit,
                    'base_debit' => (float) $line->base_credit,
                    'base_credit' => (float) $line->base_debit,
                    'is_fx_rounding_line' => (bool) $line->is_fx_rounding_line,
                ];
            })->all();

            $reversalJournal = $this->saveDraftOrPostJournal([
                'company_id' => $original->company_id,
                'branch_id' => $original->branch_id,
                'journal_date' => $revDate,
                'journal_type' => 'REVERSAL',
                'source_module' => $original->source_module ?: 'GL',
                'source_document_type' => 'GL_REVERSAL',
                'source_document_id' => $original->journal_id,
                'source_document_number' => $original->journal_number,
                'currency_code' => $original->currency_code,
                'exchange_rate' => (float) $original->exchange_rate,
                'is_system_generated' => true,
                'reversed_from_journal_id' => $original->journal_id,
                'description' => "Reversal of {$original->journal_number}: {$cleanReason}",
                'acted_by' => $actor,
            ], $mirroredLines, 'POSTED');

            // Transition original status POSTED -> REVERSED (explicitly allowed by DB trigger)
            $original->status = 'REVERSED';
            $original->reversal_journal_id = $reversalJournal->journal_id;
            $original->reversal_reason = $cleanReason;
            $original->reversed_by = $actor;
            $original->reversed_at = now();
            $original->save();

            $this->recordAudit(
                companyId: $original->company_id,
                branchId: $original->branch_id,
                module: 'GL',
                documentType: 'GL_JOURNAL',
                documentId: $original->journal_id,
                documentNumber: $original->journal_number,
                action: 'REVERSED',
                summary: "Journal {$original->journal_number} reversed via {$reversalJournal->journal_number}. Reason: {$cleanReason}",
                actedBy: $actor
            );

            return $reversalJournal;
        });
    }

    /**
     * Resolve a protected system_role account for a company.
     */
    public function resolveSystemRoleAccount(string $companyId, string $systemRole): ChartOfAccount
    {
        $role = strtoupper(trim($systemRole));
        $account = ChartOfAccount::where('company_id', $companyId)
            ->where('system_role', $role)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            throw new DomainException("No active Chart of Accounts entry found for system_role [{$role}] in company [{$companyId}].");
        }

        return $account;
    }

    /**
     * Compute real-time account balances from posted journal lines (zero COA lock contention).
     */
    public function getAccountBalances(string $companyId, ?string $periodId = null, ?string $branchId = null): Collection
    {
        $query = DB::table('gl_journal_lines as l')
            ->join('gl_journal_headers as h', 'h.journal_id', '=', 'l.journal_id')
            ->where('h.company_id', $companyId)
            ->whereIn('h.status', ['POSTED', 'REVERSED'])
            ->select(
                'l.account_id',
                DB::raw('SUM(l.base_debit) as total_debit'),
                DB::raw('SUM(l.base_credit) as total_credit')
            )
            ->groupBy('l.account_id');

        if ($periodId) {
            $query->where('h.fiscal_period_id', $periodId);
        }
        if ($branchId && strtoupper($branchId) !== 'ALL') {
            $query->where('l.branch_id', $branchId);
        }

        return $query->get()->keyBy('account_id');
    }

    protected function resolveFiscalPeriod(string $companyId, string $journalDate, bool $requireOpen): ?AcctFiscalPeriod
    {
        $period = AcctFiscalPeriod::where('company_id', $companyId)
            ->whereDate('start_date', '<=', $journalDate)
            ->whereDate('end_date', '>=', $journalDate)
            ->first();

        if (!$period && $requireOpen) {
            throw new DomainException("No fiscal period configured covering date [{$journalDate}] for company [{$companyId}].");
        }

        if ($period && $requireOpen && strtoupper($period->status) !== 'OPEN') {
            throw new DomainException("Fiscal period [{$period->period_name}] is {$period->status}; cannot post entries into a locked/closed period.");
        }

        return $period;
    }

    protected function applyLinesToPeriodBalances(string $companyId, string $periodId, array $lines): void
    {
        $grouped = [];
        foreach ($lines as $line) {
            $accId = $line['account_id'];
            $branchId = (string) ($line['branch_id'] ?? 'BR-01');
            $debit = (float) ($line['base_debit'] ?? 0);
            $credit = (float) ($line['base_credit'] ?? 0);

            foreach ([$branchId, 'ALL'] as $scopeBranch) {
                $key = $accId . '|' . $scopeBranch;
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'account_id' => $accId,
                        'branch_id' => $scopeBranch,
                        'debit' => 0.0,
                        'credit' => 0.0,
                    ];
                }
                $grouped[$key]['debit'] += $debit;
                $grouped[$key]['credit'] += $credit;
            }
        }

        foreach ($grouped as $entry) {
            $bal = GlAccountPeriodBalance::where('company_id', $companyId)
                ->where('fiscal_period_id', $periodId)
                ->where('account_id', $entry['account_id'])
                ->where('branch_id', $entry['branch_id'])
                ->lockForUpdate()
                ->first();

            if (!$bal) {
                $bal = new GlAccountPeriodBalance([
                    'balance_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'fiscal_period_id' => $periodId,
                    'account_id' => $entry['account_id'],
                    'branch_id' => $entry['branch_id'],
                    'opening_debit' => 0,
                    'opening_credit' => 0,
                    'period_debit' => 0,
                    'period_credit' => 0,
                    'closing_debit' => 0,
                    'closing_credit' => 0,
                    'net_balance' => 0,
                ]);
            }

            $bal->period_debit = round((float) $bal->period_debit + $entry['debit'], 4);
            $bal->period_credit = round((float) $bal->period_credit + $entry['credit'], 4);
            $bal->closing_debit = round((float) $bal->opening_debit + (float) $bal->period_debit, 4);
            $bal->closing_credit = round((float) $bal->opening_credit + (float) $bal->period_credit, 4);
            $bal->net_balance = round((float) $bal->closing_debit - (float) $bal->closing_credit, 4);
            $bal->save();
        }
    }

    public function recordAudit(
        string $companyId,
        ?string $branchId,
        string $module,
        string $documentType,
        string $documentId,
        ?string $documentNumber,
        string $action,
        string $summary,
        ?string $actedBy = null,
        ?array $diff = null
    ): AcctAuditLog {
        return AcctAuditLog::create([
            'audit_id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'module' => $module,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'document_number' => $documentNumber,
            'action' => $action,
            'summary' => $summary,
            'payload_diff_json' => $diff,
            'acted_by' => $actedBy ?? (Auth::user()->name ?? 'System'),
            'ip_address' => request()?->ip(),
            'acted_at' => now(),
        ]);
    }
}


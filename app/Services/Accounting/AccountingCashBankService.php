<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctBankAccount;
use App\Models\Accounting\AcctBankReconciliation;
use App\Models\Accounting\AcctBankStatementLine;
use App\Models\Accounting\AcctCheckRegister;
use App\Models\Accounting\AcctEmployeeAdvance;
use App\Models\Accounting\AcctEmployeeAdvanceLiquidation;
use App\Models\Accounting\AcctFundTransfer;
use App\Models\Accounting\ChartOfAccount;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountingCashBankService
{
    public function __construct(
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Create or update a Bank Account or Petty Cash Fund.
     */
    public function saveBankAccount(array $data): AcctBankAccount
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $glId = $data['gl_account_id'] ?? null;

        if (!$glId) {
            $role = ($data['account_type'] ?? '') === 'PETTY_CASH' ? 'CASH_ON_HAND' : 'CASH_IN_BANK';
            $acc = $this->postingService->resolveSystemRoleAccount($companyId, $role);
            $glId = $acc->account_id;
        }

        $id = $data['bank_account_id'] ?? null;
        $acc = $id ? AcctBankAccount::where('bank_account_id', $id)->firstOrFail() : new AcctBankAccount();

        $acc->fill([
            'company_id' => $companyId,
            'account_name' => trim($data['account_name']),
            'account_number' => trim($data['account_number']),
            'bank_name' => trim($data['bank_name']),
            'branch_name' => $data['branch_name'] ?? null,
            'account_type' => strtoupper($data['account_type'] ?? 'CHECKING'),
            'gl_account_id' => $glId,
            'currency_code' => strtoupper($data['currency_code'] ?? 'PHP'),
            'opening_balance' => (float) ($data['opening_balance'] ?? 0.0),
            'current_balance' => $acc->exists ? $acc->current_balance : (float) ($data['opening_balance'] ?? 0.0),
            'custodian_name' => $data['custodian_name'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
        $acc->save();

        return $acc;
    }

    /**
     * Post a Bank-to-Bank Transfer or Petty Cash Replenishment.
     */
    public function transferFunds(array $data): AcctFundTransfer
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $actor = $data['acted_by'] ?? (Auth::user()->name ?? 'Treasury Specialist');

        return DB::transaction(function () use ($data, $companyId, $actor) {
            $src = AcctBankAccount::where('bank_account_id', $data['source_bank_account_id'])->lockForUpdate()->firstOrFail();
            $dest = AcctBankAccount::where('bank_account_id', $data['destination_bank_account_id'])->lockForUpdate()->firstOrFail();

            $amount = max(0.01, (float) ($data['amount'] ?? 0.0));
            $fee = max(0.0, (float) ($data['fee_amount'] ?? 0.0));
            $totalOut = round($amount + $fee, 4);

            $transferNumber = $this->numberingService->generateNextNumber($companyId, 'FT', 'FT');

            $trf = AcctFundTransfer::create([
                'transfer_number' => $transferNumber,
                'company_id' => $companyId,
                'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
                'source_bank_account_id' => $src->bank_account_id,
                'destination_bank_account_id' => $dest->bank_account_id,
                'amount' => $amount,
                'fee_amount' => $fee,
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'POSTED',
                'notes' => $data['notes'] ?? "Transfer from {$src->account_name} to {$dest->account_name}",
                'created_by' => $actor,
                'posted_by' => $actor,
                'posted_at' => now(),
            ]);

            $glLines = [
                [
                    'account_id' => $dest->gl_account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => $amount,
                    'transaction_credit' => 0.0,
                    'description' => "Transfer in to {$dest->account_name} ({$trf->transfer_number})",
                ],
                [
                    'account_id' => $src->gl_account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $totalOut,
                    'description' => "Transfer out from {$src->account_name} ({$trf->transfer_number})",
                ],
            ];

            if ($fee > 0.0001) {
                $chargeAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'BANK_PENALTY_EXPENSE');
                $glLines[] = [
                    'account_id' => $chargeAccount->account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => $fee,
                    'transaction_credit' => 0.0,
                    'description' => "Bank Transfer Fee on {$trf->transfer_number}",
                ];
            }

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $trf->transfer_date->toDateString(),
                'journal_type' => 'MANUAL',
                'source_module' => 'CASH_BANK',
                'source_document_type' => 'FUND_TRANSFER',
                'source_document_id' => $trf->transfer_id,
                'source_document_number' => $trf->transfer_number,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Fund Transfer {$trf->transfer_number}: {$src->account_name} -> {$dest->account_name}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $trf->journal_id = $journal->journal_id;
            $trf->save();

            $src->current_balance = round((float) $src->current_balance - $totalOut, 4);
            $src->save();
            $dest->current_balance = round((float) $dest->current_balance + $amount, 4);
            $dest->save();

            return $trf;
        });
    }

    /**
     * Issue an Employee Advance (Kept in dedicated subledger, isolated from AP).
     */
    public function issueEmployeeAdvance(array $data): AcctEmployeeAdvance
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $branchId = (string) ($data['branch_id'] ?? 'BR-01');
        $actor = $data['acted_by'] ?? (Auth::user()->name ?? 'HR / Cashier');

        return DB::transaction(function () use ($data, $companyId, $branchId, $actor) {
            $advanceNumber = $this->numberingService->generateNextNumber($companyId, 'ADV', 'ADV');
            $amount = max(0.01, (float) ($data['amount'] ?? 0.0));

            $disburseAccount = null;
            if (!empty($data['disbursement_account_id'])) {
                $bank = \App\Models\Accounting\AcctBankAccount::where('company_id', $companyId)
                    ->where('bank_account_id', $data['disbursement_account_id'])
                    ->first();
                if ($bank && !empty($bank->gl_account_id)) {
                    $disburseAccount = ChartOfAccount::find($bank->gl_account_id);
                }
                if (!$disburseAccount) {
                    $disburseAccount = ChartOfAccount::find($data['disbursement_account_id']);
                }
            }
            if (!$disburseAccount) {
                $disburseAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_IN_BANK');
            }

            $advanceAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'EMPLOYEE_ADVANCES');

            $adv = AcctEmployeeAdvance::create([
                'advance_number' => $advanceNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'employee_id' => (string) $data['employee_id'],
                'employee_name' => $data['employee_name'] ?? 'Employee',
                'request_date' => $data['request_date'] ?? now()->toDateString(),
                'disbursement_date' => $data['disbursement_date'] ?? now()->toDateString(),
                'settlement_due_date' => $data['settlement_due_date'] ?? now()->addDays(15)->toDateString(),
                'amount' => $amount,
                'disbursement_account_id' => $disburseAccount->account_id,
                'gl_advance_account_id' => $advanceAccount->account_id,
                'purpose' => $data['purpose'] ?? 'Store / Kitchen Operating Advance',
                'status' => 'DISBURSED',
                'liquidated_amount' => 0.0,
                'balance_due' => $amount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor,
                'approved_by' => $actor,
                'approved_at' => now(),
            ]);

            // Post GL Journal for Advance Disbursement
            $glLines = [
                [
                    'account_id' => $advanceAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'EMPLOYEE',
                    'partner_id' => $adv->employee_id,
                    'transaction_debit' => $amount,
                    'transaction_credit' => 0.0,
                    'description' => "Employee Advance {$adv->advance_number} to {$adv->employee_name}",
                ],
                [
                    'account_id' => $disburseAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $amount,
                    'description' => "Cash/Bank disbursement for Advance {$adv->advance_number}",
                ],
            ];

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $adv->disbursement_date->toDateString(),
                'journal_type' => 'MANUAL',
                'source_module' => 'CASH_BANK',
                'source_document_type' => 'EMPLOYEE_ADVANCE',
                'source_document_id' => $adv->advance_id,
                'source_document_number' => $adv->advance_number,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Employee Advance {$adv->advance_number}: {$adv->purpose}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $adv->disbursement_journal_id = $journal->journal_id;
            $adv->save();

            return $adv;
        });
    }

    /**
     * Liquidate an Employee Advance with expense breakdown, VAT input, and cash return / reimbursement.
     */
    public function liquidateEmployeeAdvance(array $data, array $expenseItems): AcctEmployeeAdvanceLiquidation
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $branchId = (string) ($data['branch_id'] ?? 'BR-01');
        $actor = $data['acted_by'] ?? (Auth::user()->name ?? 'Auditing Accountant');

        return DB::transaction(function () use ($data, $expenseItems, $companyId, $branchId, $actor) {
            $adv = AcctEmployeeAdvance::where('advance_id', $data['advance_id'])->lockForUpdate()->firstOrFail();
            $liqNumber = $this->numberingService->generateNextNumber($companyId, 'LIQ', 'LIQ');

            $expenseTotal = 0.0;
            $vatTotal = 0.0;
            $glExpenseLines = [];

            $fallbackExpAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'COGS');
            $vatInputAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'INPUT_VAT');

            foreach ($expenseItems as $item) {
                $amt = max(0.01, (float) ($item['expense_amount'] ?? $item['amount'] ?? 0.0));
                $vatAmt = max(0.0, (float) ($item['vat_amount'] ?? 0.0));
                $expenseTotal += $amt;
                $vatTotal += $vatAmt;

                $expId = $item['gl_expense_account_id'] ?? $item['gl_account_id'] ?? $fallbackExpAccount->account_id;
                $glExpenseLines[] = [
                    'account_id' => $expId,
                    'branch_id' => $branchId,
                    'transaction_debit' => $amt,
                    'transaction_credit' => 0.0,
                    'description' => $item['description'] ?? 'Liquidation expense',
                ];
            }

            if ($vatTotal > 0.0001) {
                $glExpenseLines[] = [
                    'account_id' => $vatInputAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $vatTotal,
                    'transaction_credit' => 0.0,
                    'description' => "Input VAT on Liquidation {$liqNumber}",
                ];
            }

            $totalAccounted = round($expenseTotal + $vatTotal, 4);
            $cashReturned = max(0.0, (float) ($data['cash_returned_amount'] ?? 0.0));
            $reimbursementDue = max(0.0, round($totalAccounted - (float) $adv->balance_due - $cashReturned, 4));

            $liq = AcctEmployeeAdvanceLiquidation::create([
                'liquidation_number' => $liqNumber,
                'advance_id' => $adv->advance_id,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'liquidation_date' => $data['liquidation_date'] ?? now()->toDateString(),
                'total_expense_amount' => $expenseTotal,
                'vat_input_amount' => $vatTotal,
                'cash_returned_amount' => $cashReturned,
                'reimbursement_due_amount' => $reimbursementDue,
                'expense_details_json' => $expenseItems,
                'status' => 'POSTED',
                'notes' => $data['notes'] ?? "Liquidation of Advance {$adv->advance_number}",
                'submitted_by' => $adv->employee_name,
                'posted_by' => $actor,
                'posted_at' => now(),
            ]);

            // Clear Advance Asset
            $advanceAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'EMPLOYEE_ADVANCES');
            $creditAdvanceAmt = min((float) $adv->balance_due, $totalAccounted + $cashReturned);

            $glLines = array_merge($glExpenseLines, [
                [
                    'account_id' => $advanceAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'EMPLOYEE',
                    'partner_id' => $adv->employee_id,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $creditAdvanceAmt,
                    'description' => "Clearing Advance {$adv->advance_number} via Liquidation {$liqNumber}",
                ],
            ]);

            if ($cashReturned > 0.0001) {
                $cashAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_ON_HAND');
                $glLines[] = [
                    'account_id' => $cashAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $cashReturned,
                    'transaction_credit' => 0.0,
                    'description' => "Cash refund returned on Advance {$adv->advance_number}",
                ];
            }

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $liq->liquidation_date->toDateString(),
                'journal_type' => 'MANUAL',
                'source_module' => 'CASH_BANK',
                'source_document_type' => 'ADVANCE_LIQUIDATION',
                'source_document_id' => $liq->liquidation_id,
                'source_document_number' => $liq->liquidation_number,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Liquidation {$liq->liquidation_number} for {$adv->employee_name}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $liq->journal_id = $journal->journal_id;
            $liq->save();

            // Update Advance Balance
            $newLiquidated = round((float) $adv->liquidated_amount + $creditAdvanceAmt, 4);
            $newBalance = max(0.0, round((float) $adv->amount - $newLiquidated, 4));
            $adv->liquidated_amount = $newLiquidated;
            $adv->balance_due = $newBalance;
            $adv->status = $newBalance <= 0.05 ? 'FULLY_LIQUIDATED' : 'PARTIALLY_LIQUIDATED';
            $adv->save();

            return $liq;
        });
    }

    /**
     * Clear a Post-Dated Check (PDC) upon bank clearance.
     */
    public function clearPdcCheck(string $checkId): AcctCheckRegister
    {
        $actor = Auth::user()->name ?? 'Bank Reconciler';

        return DB::transaction(function () use ($checkId, $actor) {
            $check = AcctCheckRegister::where('check_id', $checkId)->lockForUpdate()->firstOrFail();
            if ($check->status === 'CLEARED') {
                return $check;
            }

            $companyId = (string) $check->company_id;
            $amount = (float) $check->amount;

            $bankAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_IN_BANK');
            $pdcPayableAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'PDC_PAYABLE');
            $pdcReceivableAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'PDC_RECEIVABLE');

            if ($check->direction === 'OUTGOING') {
                // Outgoing PDC Issued to Vendor: Dr PDC_PAYABLE / Cr CASH_IN_BANK
                $glLines = [
                    [
                        'account_id' => $pdcPayableAccount->account_id,
                        'branch_id' => 'BR-01',
                        'transaction_debit' => $amount,
                        'transaction_credit' => 0.0,
                        'description' => "Clear Outgoing PDC {$check->check_number} to {$check->party_name}",
                    ],
                    [
                        'account_id' => $bankAccount->account_id,
                        'branch_id' => 'BR-01',
                        'transaction_debit' => 0.0,
                        'transaction_credit' => $amount,
                        'description' => "Bank clearing for Check {$check->check_number}",
                    ],
                ];
            } else {
                // Incoming PDC Received from Customer: Dr CASH_IN_BANK / Cr PDC_RECEIVABLE
                $glLines = [
                    [
                        'account_id' => $bankAccount->account_id,
                        'branch_id' => 'BR-01',
                        'transaction_debit' => $amount,
                        'transaction_credit' => 0.0,
                        'description' => "Deposit cleared for Check {$check->check_number} ({$check->party_name})",
                    ],
                    [
                        'account_id' => $pdcReceivableAccount->account_id,
                        'branch_id' => 'BR-01',
                        'transaction_debit' => 0.0,
                        'transaction_credit' => $amount,
                        'description' => "Clearing Incoming PDC {$check->check_number}",
                    ],
                ];
            }

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => now()->toDateString(),
                'journal_type' => 'MANUAL',
                'source_module' => 'CASH_BANK',
                'source_document_type' => 'CHECK_CLEARANCE',
                'source_document_id' => $check->check_id,
                'source_document_number' => $check->check_number,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "PDC Check Clearance {$check->check_number} ({$check->party_name})",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $check->status = 'CLEARED';
            $check->cleared_at = now();
            $check->clearance_journal_id = $journal->journal_id;
            $check->save();

            return $check;
        });
    }

    /**
     * Run Bank Reconciliation matching between imported statement lines and GL book lines.
     */
    public function reconcileBankStatement(string $reconciliationId, array $statementLines): AcctBankReconciliation
    {
        return DB::transaction(function () use ($reconciliationId, $statementLines) {
            $recon = AcctBankReconciliation::where('reconciliation_id', $reconciliationId)->lockForUpdate()->firstOrFail();
            $matchedCount = 0;

            foreach ($statementLines as $raw) {
                $lineId = $raw['statement_line_id'] ?? (string) Str::uuid();
                $isMatched = (bool) ($raw['is_matched'] ?? false);
                if ($isMatched) $matchedCount++;

                AcctBankStatementLine::updateOrCreate(
                    ['statement_line_id' => $lineId],
                    [
                        'reconciliation_id' => $recon->reconciliation_id,
                        'transaction_date' => $raw['transaction_date'] ?? now()->toDateString(),
                        'reference_number' => $raw['reference_number'] ?? null,
                        'description' => $raw['description'] ?? 'Bank item',
                        'debit_amount' => (float) ($raw['debit_amount'] ?? 0.0),
                        'credit_amount' => (float) ($raw['credit_amount'] ?? 0.0),
                        'is_matched' => $isMatched,
                        'matched_journal_line_id' => $raw['matched_journal_line_id'] ?? null,
                    ]
                );
            }

            $diff = round((float) $recon->statement_ending_balance - (float) $recon->gl_book_balance, 2);
            $recon->difference = $diff;
            if (abs($diff) <= 0.01) {
                $recon->status = 'COMPLETED';
                $recon->completed_at = now();
            }
            $recon->save();

            return $recon->fresh(['lines']);
        });
    }
}

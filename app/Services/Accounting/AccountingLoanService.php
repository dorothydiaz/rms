<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctLoan;
use App\Models\Accounting\AcctLoanAmortization;
use App\Models\Accounting\AcctLoanPayment;
use App\Models\Accounting\ChartOfAccount;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingLoanService
{
    public function __construct(
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Create and record a new Loan or Debt facility, post disbursement journal, and generate amortization schedule.
     *
     * Disbursement Journal:
     * DR CASH_IN_BANK (proceeds received)
     * CR LOAN_PAYABLE (principal liability)
     */
    public function createLoan(array $data): AcctLoan
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $branchId = (string) ($data['branch_id'] ?? 'BR-01');
        $actor = $data['acted_by'] ?? (Auth::user()->name ?? 'Treasury Manager');

        if (empty($data['lender_name'])) {
            throw new InvalidArgumentException('Lender name is required.');
        }

        $principal = max(0.01, (float) ($data['principal_amount'] ?? 0.0));
        $annualRate = max(0.0, (float) ($data['annual_interest_rate_pct'] ?? 0.0));
        $termMonths = max(1, (int) ($data['term_months'] ?? 12));
        $startDate = $data['start_date'] ?? now()->toDateString();
        $startCarbon = Carbon::parse($startDate);
        $maturityDate = $startCarbon->copy()->addMonths($termMonths)->toDateString();

        return DB::transaction(function () use ($data, $companyId, $branchId, $principal, $annualRate, $termMonths, $startDate, $maturityDate, $actor) {
            $loanNumber = !empty($data['loan_number'])
                ? (string) $data['loan_number']
                : $this->numberingService->generateNextNumber($companyId, 'LOAN', 'LOAN');

            // Resolve Accounts
            $disbursementAccId = $data['disbursement_account_id'] ?? null;
            if (!$disbursementAccId) {
                $bankAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_IN_BANK');
                $disbursementAccId = $bankAcc->account_id;
            }

            $loanPayableAccId = $data['loan_payable_account_id'] ?? null;
            if (!$loanPayableAccId) {
                $loanAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'LOAN_PAYABLE');
                $loanPayableAccId = $loanAcc->account_id;
            }

            $interestExpenseAccId = $data['interest_expense_account_id'] ?? null;
            if (!$interestExpenseAccId) {
                $interestAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'INTEREST_EXPENSE');
                $interestExpenseAccId = $interestAcc->account_id;
            }

            // Calculate Monthly Installment (Annuity Formula or Simple Diminishing)
            $monthlyRate = ($annualRate / 100) / 12;
            if ($monthlyRate > 0) {
                $monthlyInstallment = round($principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / (pow(1 + $monthlyRate, $termMonths) - 1), 4);
                $totalExpectedInterest = round(($monthlyInstallment * $termMonths) - $principal, 4);
            } else {
                $monthlyInstallment = round($principal / $termMonths, 4);
                $totalExpectedInterest = 0.0000;
            }

            // Post Disbursement GL Journal
            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $startDate,
                'journal_type' => 'SYSTEM_LOAN',
                'source_module' => 'LOANS',
                'source_document_type' => 'LOAN_DISBURSEMENT',
                'source_document_number' => $loanNumber,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Loan Proceeds from {$data['lender_name']} ({$loanNumber})",
                'acted_by' => $actor,
            ], [
                [
                    'account_id' => $disbursementAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => $principal,
                    'transaction_credit' => 0.0,
                    'description' => "Loan proceeds deposited from {$data['lender_name']}",
                ],
                [
                    'account_id' => $loanPayableAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $principal,
                    'description' => "Loan payable principal for {$loanNumber}",
                ],
            ], 'POSTED');

            $loan = AcctLoan::create([
                'loan_number' => $loanNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'lender_name' => $data['lender_name'],
                'lender_contact' => $data['lender_contact'] ?? null,
                'loan_type' => $data['loan_type'] ?? 'BANK_LOAN',
                'principal_amount' => $principal,
                'annual_interest_rate_pct' => $annualRate,
                'start_date' => $startDate,
                'term_months' => $termMonths,
                'maturity_date' => $maturityDate,
                'payment_frequency' => $data['payment_frequency'] ?? 'MONTHLY',
                'interest_method' => $data['interest_method'] ?? 'DIMINISHING_BALANCE',
                'monthly_installment_amount' => $monthlyInstallment,
                'total_interest_expected' => $totalExpectedInterest,
                'principal_paid' => 0.0000,
                'interest_paid' => 0.0000,
                'balance_remaining' => $principal,
                'disbursement_account_id' => $disbursementAccId,
                'loan_payable_account_id' => $loanPayableAccId,
                'interest_expense_account_id' => $interestExpenseAccId,
                'disbursement_journal_id' => $journal->journal_id,
                'status' => 'ACTIVE',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor,
            ]);

            // Generate Amortization Schedule
            $this->generateAmortizationSchedule($loan);

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: $branchId,
                module: 'LOANS',
                documentType: 'LOAN',
                documentId: $loan->loan_id,
                documentNumber: $loan->loan_number,
                action: 'CREATED',
                summary: "Loan {$loan->loan_number} ({$loan->lender_name}) recorded — Principal: ₱" . number_format($principal, 2),
                actedBy: $actor
            );

            return $loan->fresh(['amortizations']);
        });
    }

    /**
     * Generate the amortization schedule rows for an active loan.
     */
    public function generateAmortizationSchedule(AcctLoan $loan): void
    {
        $principal = (float) $loan->principal_amount;
        $annualRate = (float) $loan->annual_interest_rate_pct;
        $termMonths = (int) $loan->term_months;
        $monthlyRate = ($annualRate / 100) / 12;
        $startCarbon = Carbon::parse($loan->start_date);
        $installment = (float) $loan->monthly_installment_amount;

        $currentBalance = $principal;

        for ($period = 1; $period <= $termMonths; $period++) {
            $dueDate = $startCarbon->copy()->addMonths($period)->toDateString();
            $interest = round($currentBalance * $monthlyRate, 4);

            if ($period === $termMonths) {
                // Final period adjustment to zero out exactly
                $principalRepayment = $currentBalance;
                $totalPayment = round($principalRepayment + $interest, 4);
                $endingBalance = 0.0000;
            } else {
                $principalRepayment = round(min($currentBalance, $installment - $interest), 4);
                $totalPayment = round($principalRepayment + $interest, 4);
                $endingBalance = max(0.0, round($currentBalance - $principalRepayment, 4));
            }

            AcctLoanAmortization::create([
                'loan_id' => $loan->loan_id,
                'period_number' => $period,
                'due_date' => $dueDate,
                'beginning_principal' => $currentBalance,
                'principal_amount' => $principalRepayment,
                'interest_amount' => $interest,
                'total_installment' => $totalPayment,
                'ending_principal' => $endingBalance,
                'status' => 'PENDING',
            ]);

            $currentBalance = $endingBalance;
        }
    }

    /**
     * Record a Loan Repayment with Principal reduction and Interest Expense breakdown.
     *
     * Repayment Journal:
     * DR LOAN_PAYABLE (principal reduction)
     * DR INTEREST_EXPENSE (interest charge)
     * DR BANK_PENALTY_EXPENSE (late fee, if any)
     * CR CASH_IN_BANK (total paid)
     */
    public function recordLoanPayment(string $loanId, array $paymentData): AcctLoanPayment
    {
        $actor = $paymentData['acted_by'] ?? (Auth::user()->name ?? 'Treasury Specialist');

        return DB::transaction(function () use ($loanId, $paymentData, $actor) {
            $loan = AcctLoan::where('loan_id', $loanId)->lockForUpdate()->firstOrFail();

            if ($loan->status === 'CLOSED') {
                throw new DomainException("Loan [{$loan->loan_number}] is already fully paid and closed.");
            }

            $companyId = (string) $loan->company_id;
            $branchId = (string) $loan->branch_id;
            $paymentDate = $paymentData['payment_date'] ?? now()->toDateString();
            $principal = max(0.0, (float) ($paymentData['principal_amount'] ?? 0.0));
            $interest = max(0.0, (float) ($paymentData['interest_amount'] ?? 0.0));
            $penalty = max(0.0, (float) ($paymentData['penalty_amount'] ?? 0.0));
            $totalPaid = round($principal + $interest + $penalty, 4);

            if ($totalPaid <= 0.0001) {
                throw new InvalidArgumentException('Total payment must be greater than zero.');
            }

            $paymentNumber = $this->numberingService->generateNextNumber($companyId, 'LPAY', 'LPAY');

            // Resolve bank disbursement account
            $disbursementAccId = $paymentData['disbursement_account_id'] ?? null;
            if (!$disbursementAccId) {
                $bankAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_IN_BANK');
                $disbursementAccId = $bankAcc->account_id;
            }

            // Build GL Journal lines
            $glLines = [];

            // 1. Debit Loan Payable (reduces liability)
            if ($principal > 0.0001) {
                $glLines[] = [
                    'account_id' => $loan->loan_payable_account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $principal,
                    'transaction_credit' => 0.0,
                    'description' => "Principal repayment for {$loan->loan_number}",
                ];
            }

            // 2. Debit Interest Expense
            if ($interest > 0.0001) {
                $glLines[] = [
                    'account_id' => $loan->interest_expense_account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $interest,
                    'transaction_credit' => 0.0,
                    'description' => "Interest expense for {$loan->loan_number}",
                ];
            }

            // 3. Debit Penalty Expense if any
            if ($penalty > 0.0001) {
                try {
                    $penaltyAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'BANK_PENALTY_EXPENSE');
                    $penaltyAccId = $penaltyAcc->account_id;
                } catch (\Throwable $e) {
                    $penaltyAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'GENERAL_EXPENSE');
                    $penaltyAccId = $penaltyAcc->account_id;
                }

                $glLines[] = [
                    'account_id' => $penaltyAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => $penalty,
                    'transaction_credit' => 0.0,
                    'description' => "Late payment penalty for {$loan->loan_number}",
                ];
            }

            // 4. Credit Cash/Bank (money drawn from company)
            $glLines[] = [
                'account_id' => $disbursementAccId,
                'branch_id' => $branchId,
                'transaction_debit' => 0.0,
                'transaction_credit' => $totalPaid,
                'description' => "Loan payment {$paymentNumber} for {$loan->loan_number}",
            ];

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $paymentDate,
                'journal_type' => 'SYSTEM_LOAN',
                'source_module' => 'LOANS',
                'source_document_type' => 'LOAN_PAYMENT',
                'source_document_id' => $loan->loan_id,
                'source_document_number' => $paymentNumber,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Loan Payment {$paymentNumber} for {$loan->loan_number} ({$loan->lender_name})",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $payment = AcctLoanPayment::create([
                'payment_number' => $paymentNumber,
                'loan_id' => $loan->loan_id,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'payment_date' => $paymentDate,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'penalty_amount' => $penalty,
                'total_paid' => $totalPaid,
                'payment_method' => $paymentData['payment_method'] ?? 'BANK_TRANSFER',
                'disbursement_account_id' => $disbursementAccId,
                'journal_id' => $journal->journal_id,
                'status' => 'POSTED',
                'reference_number' => $paymentData['reference_number'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'posted_by' => $actor,
                'posted_at' => now(),
            ]);

            // Update loan balances
            $newPrincipalPaid = round((float) $loan->principal_paid + $principal, 4);
            $newInterestPaid = round((float) $loan->interest_paid + $interest, 4);
            $newRemaining = max(0.0, round((float) $loan->principal_amount - $newPrincipalPaid, 4));

            $loan->principal_paid = $newPrincipalPaid;
            $loan->interest_paid = $newInterestPaid;
            $loan->balance_remaining = $newRemaining;
            if ($newRemaining <= 0.05) {
                $loan->status = 'CLOSED';
            }
            $loan->save();

            // Match and mark amortization row as paid
            $amortRow = AcctLoanAmortization::where('loan_id', $loan->loan_id)
                ->where('status', 'PENDING')
                ->orderBy('period_number')
                ->first();

            if ($amortRow) {
                $amortRow->status = 'PAID';
                $amortRow->payment_id = $payment->loan_payment_id;
                $amortRow->payment_date = $paymentDate;
                $amortRow->save();
            }

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: $branchId,
                module: 'LOANS',
                documentType: 'LOAN_PAYMENT',
                documentId: $payment->loan_payment_id,
                documentNumber: $payment->payment_number,
                action: 'POSTED',
                summary: "Loan Repayment {$payment->payment_number} (Principal: ₱" . number_format($principal, 2) . ", Interest: ₱" . number_format($interest, 2) . ")",
                actedBy: $actor
            );

            return $payment;
        });
    }
}

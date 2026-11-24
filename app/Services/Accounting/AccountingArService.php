<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctCheckRegister;
use App\Models\Accounting\AcctCollectionReceipt;
use App\Models\Accounting\AcctCollectionReceiptLine;
use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctCustomerMemo;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\AcctSalesInvoiceLine;
use App\Models\Accounting\AcctTaxCode;
use App\Models\Sales\SalesOrder;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountingArService
{
    public function __construct(
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Create or update a Sales Invoice and post to GL if targetStatus is POSTED.
     */
    public function saveDraftOrPostInvoice(array $headerData, array $linesData, string $targetStatus = 'DRAFT'): AcctSalesInvoice
    {
        $targetStatus = strtoupper(trim($targetStatus));
        $companyId = (string) ($headerData['company_id'] ?? '1');
        $branchId = (string) ($headerData['branch_id'] ?? 'BR-01');
        $actor = $headerData['acted_by'] ?? (Auth::user()->name ?? 'Billing Specialist');

        if (empty($headerData['customer_id'])) {
            throw new InvalidArgumentException('Customer is required for Sales Invoice.');
        }
        if (empty($linesData)) {
            throw new InvalidArgumentException('Sales Invoice must contain at least one line item.');
        }

        return DB::transaction(function () use ($headerData, $linesData, $targetStatus, $companyId, $branchId, $actor) {
            $customer = AcctCustomer::where('customer_id', $headerData['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $inv = null;
            if (!empty($headerData['invoice_id'])) {
                $inv = AcctSalesInvoice::where('invoice_id', $headerData['invoice_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array(strtoupper($inv->status), ['POSTED', 'PAID'], true)) {
                    throw new DomainException("Immutable Record: Cannot modify {$inv->status} invoice [{$inv->invoice_number}].");
                }
            }

            $invoiceNumber = $inv
                ? $inv->invoice_number
                : ($headerData['invoice_number'] ?? $this->numberingService->generateNextNumber($companyId, 'SINV', 'SI'));

            $currency = strtoupper((string) ($headerData['currency_code'] ?? 'PHP'));
            $rate = max(0.000001, (float) ($headerData['exchange_rate'] ?? 1.0));

            // Compute lines, VAT (Output VAT), and subtotal
            $subtotal = 0.0;
            $taxTotal = 0.0;
            $normalizedLines = [];
            $lineNo = 1;

            foreach ($linesData as $raw) {
                $qty = max(0.0001, (float) ($raw['quantity'] ?? 1.0));
                $unitPrice = max(0.0, (float) ($raw['unit_price'] ?? 0.0));
                $lineSubtotal = round($qty * $unitPrice, 4);

                $taxCodeStr = strtoupper((string) ($raw['tax_code'] ?? 'VAT12_OUT'));
                $taxCodeObj = AcctTaxCode::where('company_id', $companyId)->where('tax_code', $taxCodeStr)->first();
                $taxRate = $taxCodeObj ? (float) $taxCodeObj->rate_pct : 12.0;
                $lineTax = in_array($taxCodeStr, ['EXEMPT', 'ZERO_RATED', 'VAT_EXEMPT', 'VAT_ZERO'], true)
                    ? 0.0
                    : round($lineSubtotal * ($taxRate / 100), 4);
                $lineTotal = round($lineSubtotal + $lineTax, 4);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $normalizedLines[] = [
                    'invoice_line_id' => $raw['invoice_line_id'] ?? (string) Str::uuid(),
                    'line_number' => $lineNo++,
                    'item_id' => $raw['item_id'] ?? null,
                    'item_code' => $raw['item_code'] ?? null,
                    'description' => $raw['description'] ?? 'Sales Item',
                    'quantity' => $qty,
                    'uom' => $raw['uom'] ?? 'Unit',
                    'unit_price' => $unitPrice,
                    'tax_code' => $taxCodeStr,
                    'tax_amount' => $lineTax,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineTotal,
                    'gl_revenue_account_id' => $raw['gl_revenue_account_id'] ?? null,
                    'sales_order_line_id' => $raw['sales_order_line_id'] ?? null,
                ];
            }

            $grossSubtotal = round($subtotal, 4);
            $headerDiscount = min($grossSubtotal, max(0.0, round((float) ($headerData['discount_amount'] ?? 0.0), 4)));
            $netSubtotal = round($grossSubtotal - $headerDiscount, 4);
            if ($headerDiscount > 0.0001 && $grossSubtotal > 0.0001) {
                $factor = $netSubtotal / $grossSubtotal;
                $taxTotal = round($taxTotal * $factor, 4);
            } else {
                $taxTotal = round($taxTotal, 4);
            }
            $grandTotal = round($netSubtotal + $taxTotal, 4);

            // Credit Limit Check (if credit limit > 0)
            if ((float) $customer->credit_limit > 0 && $targetStatus === 'POSTED') {
                $projectedBalance = (float) $customer->current_balance + $grandTotal;
                if ($projectedBalance > (float) $customer->credit_limit && empty($headerData['override_credit_limit'])) {
                    throw new DomainException(sprintf(
                        "Credit Limit Exceeded: Customer [%s] has limit ₱%.2f, current balance ₱%.2f. Projected ₱%.2f exceeds customer credit limit.",
                        $customer->customer_name,
                        (float) $customer->credit_limit,
                        (float) $customer->current_balance,
                        $projectedBalance
                    ));
                }
            }

            $payload = [
                'invoice_number' => $invoiceNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'invoice_date' => $headerData['invoice_date'] ?? now()->toDateString(),
                'due_date' => $headerData['due_date'] ?? now()->addDays(30)->toDateString(),
                'payment_terms_code' => $headerData['payment_terms_code'] ?? ($customer->payment_terms_code ?: 'NET_30'),
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'subtotal_amount' => $netSubtotal,
                'discount_amount' => $headerDiscount,
                'tax_amount' => $taxTotal,
                'total_amount' => $grandTotal,
                'paid_amount' => $inv ? (float) $inv->paid_amount : 0.0,
                'balance_due' => round($grandTotal - ($inv ? (float) $inv->paid_amount : 0.0), 4),
                'stock_out_id' => $headerData['stock_out_id'] ?? null,
                'stock_out_number' => $headerData['stock_out_number'] ?? null,
                'sales_order_id' => $headerData['sales_order_id'] ?? ($inv ? $inv->sales_order_id : null),
                'sales_order_number' => $headerData['sales_order_number'] ?? ($inv ? $inv->sales_order_number : null),
                'stock_out_order_ref_id' => $headerData['stock_out_order_ref_id'] ?? ($inv ? $inv->stock_out_order_ref_id : null),
                'status' => $targetStatus,
                'notes' => $headerData['notes'] ?? null,
                'created_by' => $inv ? $inv->created_by : $actor,
            ];

            if ($targetStatus === 'POSTED') {
                $payload['posted_at'] = now();
                $payload['posted_by'] = $actor;
            }

            if ($inv) {
                $inv->fill($payload);
                $inv->save();
                AcctSalesInvoiceLine::where('invoice_id', $inv->invoice_id)->delete();
            } else {
                $inv = AcctSalesInvoice::create($payload);
            }

            foreach ($normalizedLines as $lineRow) {
                $lineRow['invoice_id'] = $inv->invoice_id;
                AcctSalesInvoiceLine::create($lineRow);
            }

            // Post to General Ledger upon POSTED
            if ($targetStatus === 'POSTED') {
                $arAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'ACCOUNTS_RECEIVABLE');
                $revAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'SALES_REVENUE');
                $vatOutputAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'OUTPUT_VAT');

                $glLines = [
                    [
                        'account_id' => $arAccount->account_id,
                        'branch_id' => $branchId,
                        'partner_type' => 'CUSTOMER',
                        'partner_id' => $customer->customer_id,
                        'transaction_debit' => $grandTotal,
                        'transaction_credit' => 0.0,
                        'description' => "AR Trade for Sales Invoice {$inv->invoice_number} ({$customer->customer_name})",
                    ],
                ];

                if ($headerDiscount > 0.0001) {
                    $discAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'SALES_DISCOUNTS');
                    $glLines[] = [
                        'account_id' => $discAccount->account_id,
                        'branch_id' => $branchId,
                        'partner_type' => 'CUSTOMER',
                        'partner_id' => $customer->customer_id,
                        'transaction_debit' => $headerDiscount,
                        'transaction_credit' => 0.0,
                        'description' => "Sales Discounts & Allowances on Invoice {$inv->invoice_number}",
                    ];
                }

                $glLines[] = [
                    'account_id' => $revAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $grossSubtotal,
                    'description' => "Sales Revenue from Invoice {$inv->invoice_number}",
                ];

                if ($taxTotal > 0.0001) {
                    $glLines[] = [
                        'account_id' => $vatOutputAccount->account_id,
                        'branch_id' => $branchId,
                        'transaction_debit' => 0.0,
                        'transaction_credit' => $taxTotal,
                        'description' => "Output VAT on Invoice {$inv->invoice_number}",
                    ];
                }

                $journal = $this->postingService->saveDraftOrPostJournal([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'journal_date' => $inv->invoice_date->toDateString(),
                    'journal_type' => 'SYSTEM_AR',
                    'source_module' => 'AR',
                    'source_document_type' => 'SALES_INVOICE',
                    'source_document_id' => $inv->invoice_id,
                    'source_document_number' => $inv->invoice_number,
                    'currency_code' => $inv->currency_code,
                    'exchange_rate' => (float) $inv->exchange_rate,
                    'is_system_generated' => true,
                    'description' => "Sales Invoice {$inv->invoice_number} to {$customer->customer_name}",
                    'acted_by' => $actor,
                ], $glLines, 'POSTED');

                $inv->journal_id = $journal->journal_id;
                $inv->save();

                // Increment customer current balance
                $customer->current_balance = round((float) $customer->current_balance + $grandTotal, 4);
                $customer->save();

                if (!empty($inv->sales_order_id)) {
                    $this->syncSalesOrderPaymentState((string) $inv->sales_order_id);
                }
            }

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: $inv->branch_id,
                module: 'AR',
                documentType: 'SALES_INVOICE',
                documentId: $inv->invoice_id,
                documentNumber: $inv->invoice_number,
                action: $targetStatus === 'POSTED' ? 'POSTED' : ($inv->wasRecentlyCreated ? 'CREATED' : 'UPDATED'),
                summary: "Sales Invoice {$inv->invoice_number} saved as {$targetStatus} — ₱" . number_format($inv->total_amount, 2),
                actedBy: $actor
            );

            return $inv->fresh(['lines']);
        });
    }

    /**
     * Record a Customer Collection Receipt (Official Receipt), apply CWT deduction (2307 received), or deposit mode.
     */
    public function recordCollectionReceipt(array $receiptData, array $invoiceAllocations): AcctCollectionReceipt
    {
        $companyId = (string) ($receiptData['company_id'] ?? '1');
        $branchId = (string) ($receiptData['branch_id'] ?? 'BR-01');
        $actor = $receiptData['acted_by'] ?? (Auth::user()->name ?? 'Collections Officer');

        if (empty($receiptData['customer_id'])) {
            throw new InvalidArgumentException('Customer is required for collection receipt.');
        }

        return DB::transaction(function () use ($receiptData, $invoiceAllocations, $companyId, $branchId, $actor) {
            $customer = AcctCustomer::where('customer_id', $receiptData['customer_id'])->lockForUpdate()->firstOrFail();
            $receiptNumber = $this->numberingService->generateNextNumber($companyId, 'CR', 'CR');
            $method = strtoupper((string) ($receiptData['payment_method'] ?? 'CASH'));
            $isDepositMode = (bool) ($receiptData['is_deposit_mode'] ?? empty($invoiceAllocations));

            $depositAccId = $receiptData['deposit_account_id'] ?? null;
            if (!$depositAccId) {
                $role = match ($method) {
                    'CHECK', 'PDC' => !empty($receiptData['check_maturity_date']) && Carbon::parse($receiptData['check_maturity_date'])->isFuture() ? 'PDC_RECEIVABLE' : 'CASH_IN_BANK',
                    'CASH' => 'CASH_ON_HAND',
                    default => 'CASH_IN_BANK',
                };
                $acc = $this->postingService->resolveSystemRoleAccount($companyId, $role);
                $depositAccId = $acc->account_id;
            }

            $grossSum = 0.0;
            $cwtSum = 0.0;
            $allocatedLines = [];

            if ($isDepositMode) {
                $grossSum = (float) ($receiptData['total_amount'] ?? $receiptData['gross_amount'] ?? $receiptData['net_amount'] ?? 0.0);
                $cwtSum = (float) ($receiptData['total_cwt_amount'] ?? $receiptData['cwt_amount'] ?? 0.0);
            } else {
                foreach ($invoiceAllocations as $alloc) {
                    $invId = $alloc['sales_invoice_id'];
                    $inv = AcctSalesInvoice::where('invoice_id', $invId)->lockForUpdate()->firstOrFail();

                    $allocated = max(0.01, (float) ($alloc['allocated_amount'] ?? 0.0));
                    $cwtRate = (float) ($alloc['cwt_rate_pct'] ?? 0.0);
                    $cwtTaxCode = $alloc['cwt_tax_code'] ?? 'WC158';
                    $cwtWithheld = isset($alloc['cwt_amount']) ? (float) $alloc['cwt_amount'] : ($cwtRate > 0 ? round($allocated * ($cwtRate / 100), 4) : 0.0);

                    $grossSum += $allocated;
                    $cwtSum += $cwtWithheld;

                    $allocatedLines[] = [
                        'sales_invoice_id' => $invId,
                        'invoice_number' => $inv->invoice_number,
                        'invoice_amount' => (float) $inv->total_amount,
                        'balance_before' => (float) $inv->balance_due,
                        'allocated_amount' => $allocated,
                        'cwt_withheld' => $cwtWithheld,
                        'cwt_tax_code' => $cwtTaxCode,
                        'cwt_rate_pct' => $cwtRate,
                        'invoice_model' => $inv,
                    ];
                }

                if ($grossSum === 0.0 && isset($receiptData['total_amount'])) {
                    $grossSum = (float) $receiptData['total_amount'];
                }
                if ($cwtSum === 0.0 && isset($receiptData['total_cwt_amount'])) {
                    $cwtSum = (float) $receiptData['total_cwt_amount'];
                }
            }

            $netAmount = round($grossSum - $cwtSum, 4);

            $receipt = AcctCollectionReceipt::create([
                'receipt_number' => $receiptNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'receipt_date' => $receiptData['receipt_date'] ?? now()->toDateString(),
                'payment_method' => $method,
                'deposit_account_id' => $depositAccId,
                'currency_code' => $receiptData['currency_code'] ?? 'PHP',
                'exchange_rate' => (float) ($receiptData['exchange_rate'] ?? 1.0),
                'gross_amount' => $grossSum,
                'cwt_amount' => $cwtSum,
                'net_amount' => $netAmount,
                'total_amount' => $grossSum,
                'total_cwt_amount' => $cwtSum,
                'total_net_amount' => $netAmount,
                'check_number' => $receiptData['check_number'] ?? null,
                'check_date' => $receiptData['check_date'] ?? null,
                'check_maturity_date' => $receiptData['check_maturity_date'] ?? null,
                'bank_name' => $receiptData['bank_name'] ?? null,
                'reference_number' => $receiptData['reference_number'] ?? null,
                'is_deposit_mode' => $isDepositMode,
                'status' => 'POSTED',
                'notes' => $receiptData['notes'] ?? null,
                'created_by' => $actor,
                'posted_by' => $actor,
                'posted_at' => now(),
            ]);

            // Save line allocations and update sales invoice balances
            foreach ($allocatedLines as $a) {
                AcctCollectionReceiptLine::create([
                    'receipt_id' => $receipt->receipt_id,
                    'sales_invoice_id' => $a['sales_invoice_id'],
                    'invoice_number' => $a['invoice_number'],
                    'invoice_amount' => $a['invoice_amount'],
                    'balance_before' => $a['balance_before'],
                    'allocated_amount' => $a['allocated_amount'],
                    'cwt_withheld' => $a['cwt_withheld'],
                    'cwt_tax_code' => $a['cwt_tax_code'],
                    'cwt_rate_pct' => $a['cwt_rate_pct'],
                ]);

                $inv = $a['invoice_model'];
                $newPaid = round((float) $inv->paid_amount + $a['allocated_amount'], 4);
                $newBalance = max(0.0, round((float) $inv->total_amount - $newPaid, 4));
                $inv->paid_amount = $newPaid;
                $inv->balance_due = $newBalance;
                $inv->status = ($newBalance <= 0.05) ? 'PAID' : 'PARTIAL';
                $inv->save();

                if (!empty($inv->sales_order_id)) {
                    $this->syncSalesOrderPaymentState((string) $inv->sales_order_id);
                }
            }

            // Decrement customer open balance
            $customer->current_balance = max(0.0, round((float) $customer->current_balance - $grossSum, 4));
            $customer->save();

            // Post General Ledger Journal for Collection Receipt
            $arAccount = $this->postingService->resolveSystemRoleAccount($companyId, $isDepositMode ? 'CUSTOMER_DEPOSITS' : 'ACCOUNTS_RECEIVABLE');
            $cwtAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'CWT_RECEIVABLE');

            $glLines = [
                [
                    'account_id' => $depositAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => $netAmount,
                    'transaction_credit' => 0.0,
                    'description' => "Collection from {$customer->customer_name} via {$method} ({$receipt->receipt_number})",
                ],
            ];

            if ($cwtSum > 0.0001) {
                $glLines[] = [
                    'account_id' => $cwtAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $cwtSum,
                    'transaction_credit' => 0.0,
                    'description' => "BIR 2307 CWT Withheld by Customer on {$receipt->receipt_number}",
                ];
            }

            $glLines[] = [
                'account_id' => $arAccount->account_id,
                'branch_id' => $branchId,
                'partner_type' => 'CUSTOMER',
                'partner_id' => $customer->customer_id,
                'transaction_debit' => 0.0,
                'transaction_credit' => $grossSum,
                'description' => "Credit to AR for Collection {$receipt->receipt_number}",
            ];

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $receipt->receipt_date->toDateString(),
                'journal_type' => 'SYSTEM_AR',
                'source_module' => 'AR',
                'source_document_type' => 'COLLECTION_RECEIPT',
                'source_document_id' => $receipt->receipt_id,
                'source_document_number' => $receipt->receipt_number,
                'currency_code' => $receipt->currency_code,
                'exchange_rate' => (float) $receipt->exchange_rate,
                'is_system_generated' => true,
                'description' => "Collection Receipt {$receipt->receipt_number} from {$customer->customer_name}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $receipt->journal_id = $journal->journal_id;
            $receipt->save();

            // Track 2307 Certificate Received
            if ($cwtSum > 0.0001) {
                AcctBir2307Certificate::create([
                    'certificate_number' => 'CWT-' . $receipt->receipt_number,
                    'company_id' => $companyId,
                    'party_type' => 'CUSTOMER',
                    'party_id' => $customer->customer_id,
                    'party_name' => $customer->customer_name,
                    'source_document_type' => 'COLLECTION_RECEIPT',
                    'source_document_id' => $receipt->receipt_id,
                    'source_document_number' => $receipt->receipt_number,
                    'period_from' => Carbon::parse($receipt->receipt_date)->startOfMonth()->toDateString(),
                    'period_to' => Carbon::parse($receipt->receipt_date)->endOfMonth()->toDateString(),
                    'tax_code' => $allocatedLines[0]['cwt_tax_code'] ?? 'WC158',
                    'atc_code' => 'WC158',
                    'income_payment_amount' => $grossSum,
                    'tax_withheld_amount' => $cwtSum,
                    'status' => 'RECEIVED',
                    'signed_by' => $customer->customer_name,
                    'issued_at' => now(),
                ]);
            }

            // Register Check / PDC if check payment
            if (in_array($method, ['CHECK', 'PDC'], true) && !empty($receipt->check_number)) {
                $isMaturityFuture = !empty($receipt->check_maturity_date) && Carbon::parse($receipt->check_maturity_date)->isFuture();
                AcctCheckRegister::create([
                    'company_id' => $companyId,
                    'direction' => 'INCOMING',
                    'party_type' => 'CUSTOMER',
                    'party_id' => $customer->customer_id,
                    'party_name' => $customer->customer_name,
                    'bank_name' => $receiptData['bank_name'] ?? 'Issuing Bank',
                    'check_number' => $receipt->check_number,
                    'check_date' => $receipt->check_date ?: $receipt->receipt_date,
                    'maturity_date' => $receipt->check_maturity_date ?: ($receipt->check_date ?: $receipt->receipt_date),
                    'amount' => $netAmount,
                    'source_document_type' => 'COLLECTION_RECEIPT',
                    'source_document_id' => $receipt->receipt_id,
                    'status' => $isMaturityFuture ? 'RECEIVED' : 'DEPOSITED',
                    'notes' => "Check received for {$receipt->receipt_number}",
                ]);
            }

            return $receipt->fresh(['lines']);
        });
    }

    /**
     * Create and post a Customer Credit or Debit Memo.
     */
    public function createCustomerMemo(array $memoData): AcctCustomerMemo
    {
        $companyId = (string) ($memoData['company_id'] ?? '1');
        $branchId = (string) ($memoData['branch_id'] ?? 'BR-01');
        $actor = $memoData['acted_by'] ?? (Auth::user()->name ?? 'Collections Specialist');

        return DB::transaction(function () use ($memoData, $companyId, $branchId, $actor) {
            $customer = AcctCustomer::where('customer_id', $memoData['customer_id'])->lockForUpdate()->firstOrFail();
            $memoNumber = $this->numberingService->generateNextNumber($companyId, 'CMEMO', 'CCM');
            $memoType = strtoupper((string) ($memoData['memo_type'] ?? 'CREDIT'));
            $amount = max(0.01, (float) ($memoData['amount'] ?? 0.0));
            $taxAmount = max(0.0, (float) ($memoData['tax_amount'] ?? 0.0));
            $totalAmount = round($amount + $taxAmount, 4);

            $memo = AcctCustomerMemo::create([
                'memo_number' => $memoNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'memo_type' => $memoType,
                'memo_date' => $memoData['memo_date'] ?? now()->toDateString(),
                'sales_invoice_id' => $memoData['sales_invoice_id'] ?? null,
                'invoice_number' => $memoData['invoice_number'] ?? null,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'unapplied_amount' => $totalAmount,
                'reason' => $memoData['reason'] ?? 'Sales Return / Discount Allowance',
                'status' => 'POSTED',
                'notes' => $memoData['notes'] ?? null,
                'created_by' => $actor,
                'posted_by' => $actor,
                'posted_at' => now(),
            ]);

            $arAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'ACCOUNTS_RECEIVABLE');
            $salesAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'SALES_REVENUE');
            $vatOutputAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'OUTPUT_VAT');

            $glLines = $memoType === 'CREDIT' ? [
                [
                    'account_id' => $salesAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $amount,
                    'transaction_credit' => 0.0,
                    'description' => "Sales Return/Allowance for Customer Memo {$memo->memo_number}",
                ],
                [
                    'account_id' => $arAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'CUSTOMER',
                    'partner_id' => $customer->customer_id,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $totalAmount,
                    'description' => "Credit memo deduction on AR ({$memo->memo_number})",
                ],
            ] : [
                [
                    'account_id' => $arAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'CUSTOMER',
                    'partner_id' => $customer->customer_id,
                    'transaction_debit' => $totalAmount,
                    'transaction_credit' => 0.0,
                    'description' => "Debit memo charge on AR ({$memo->memo_number})",
                ],
                [
                    'account_id' => $salesAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $totalAmount,
                    'description' => "Debit charge revenue ({$memo->memo_number})",
                ],
            ];

            if ($memoType === 'CREDIT' && $taxAmount > 0.0001) {
                $glLines[] = [
                    'account_id' => $vatOutputAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $taxAmount,
                    'transaction_credit' => 0.0,
                    'description' => "Output VAT adjustment on Memo {$memo->memo_number}",
                ];
            }

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $memo->memo_date->toDateString(),
                'journal_type' => 'SYSTEM_AR',
                'source_module' => 'AR',
                'source_document_type' => 'CUSTOMER_MEMO',
                'source_document_id' => $memo->memo_id,
                'source_document_number' => $memo->memo_number,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Customer {$memoType} Memo {$memo->memo_number}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $memo->journal_id = $journal->journal_id;
            $memo->save();

            // Adjust customer balance
            if ($memoType === 'CREDIT') {
                $customer->current_balance = max(0.0, round((float) $customer->current_balance - $totalAmount, 4));
            } else {
                $customer->current_balance = round((float) $customer->current_balance + $totalAmount, 4);
            }
            $customer->save();

            if (!empty($memo->sales_invoice_id)) {
                $linkedInv = AcctSalesInvoice::where('invoice_id', $memo->sales_invoice_id)->first();
                if ($linkedInv && !empty($linkedInv->sales_order_id)) {
                    $this->syncSalesOrderPaymentState((string) $linkedInv->sales_order_id);
                }
            }

            return $memo;
        });
    }

    /**
     * Handle DAIF bounced check: reverse collection receipt allocation, reopen invoice, charge bank penalty.
     */
    public function handleBouncedCheck(string $checkId, string $bounceReason, float $penaltyFee = 500.0): AcctCheckRegister
    {
        $actor = Auth::user()->name ?? 'Collections Supervisor';

        return DB::transaction(function () use ($checkId, $bounceReason, $penaltyFee, $actor) {
            $check = AcctCheckRegister::where('check_id', $checkId)->lockForUpdate()->firstOrFail();
            if ($check->status === 'BOUNCED_DAIF') {
                throw new DomainException("Check [{$check->check_number}] has already been tagged as BOUNCED_DAIF.");
            }

            $check->status = 'BOUNCED_DAIF';
            $check->bounced_at = now();
            $check->bounce_reason = $bounceReason;
            $check->bounce_penalty_amount = $penaltyFee;

            $companyId = (string) $check->company_id;

            // If incoming from Customer Collection Receipt, reopen invoices & reverse
            if ($check->direction === 'INCOMING' && $check->source_document_type === 'COLLECTION_RECEIPT') {
                $receipt = AcctCollectionReceipt::with('lines.invoice')->where('receipt_id', $check->source_document_id)->first();
                if ($receipt) {
                    $receipt->status = 'CANCELLED';
                    $receipt->save();

                    // Reopen invoices
                    foreach ($receipt->lines as $line) {
                        $inv = $line->invoice;
                        if ($inv) {
                            $inv->paid_amount = max(0.0, round((float) $inv->paid_amount - (float) $line->allocated_amount, 4));
                            $inv->balance_due = round((float) $inv->total_amount - (float) $inv->paid_amount, 4);
                            $inv->status = 'POSTED';
                            $inv->save();
                        }
                    }

                    // Restore customer open balance
                    $customer = AcctCustomer::where('customer_id', $check->party_id)->first();
                    if ($customer) {
                        $customer->current_balance = round((float) $customer->current_balance + (float) $receipt->gross_amount, 4);
                        $customer->save();
                    }

                    // Post Bank Penalty & Bounce Reversal Journal
                    $penaltyAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'BANK_PENALTY_EXPENSE');
                    $bankAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_IN_BANK');
                    $arAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'ACCOUNTS_RECEIVABLE');

                    $bounceGlLines = [
                        [
                            'account_id' => $arAccount->account_id,
                            'branch_id' => 'BR-01',
                            'partner_type' => 'CUSTOMER',
                            'partner_id' => $check->party_id,
                            'transaction_debit' => (float) $check->amount,
                            'transaction_credit' => 0.0,
                            'description' => "Reopen AR on Bounced Check {$check->check_number}",
                        ],
                        [
                            'account_id' => $bankAccount->account_id,
                            'branch_id' => 'BR-01',
                            'transaction_debit' => 0.0,
                            'transaction_credit' => (float) $check->amount + $penaltyFee,
                            'description' => "Reverse check deposit {$check->check_number} + penalty",
                        ],
                    ];

                    if ($penaltyFee > 0.0001) {
                        $bounceGlLines[] = [
                            'account_id' => $penaltyAccount->account_id,
                            'branch_id' => 'BR-01',
                            'transaction_debit' => $penaltyFee,
                            'transaction_credit' => 0.0,
                            'description' => "Bank DAIF Penalty on Check {$check->check_number}",
                        ];
                    }

                    $bounceJournal = $this->postingService->saveDraftOrPostJournal([
                        'company_id' => $companyId,
                        'branch_id' => 'BR-01',
                        'journal_date' => now()->toDateString(),
                        'journal_type' => 'SYSTEM_AR',
                        'source_module' => 'AR',
                        'source_document_type' => 'CHECK_BOUNCE',
                        'source_document_id' => $check->check_id,
                        'source_document_number' => $check->check_number,
                        'currency_code' => 'PHP',
                        'exchange_rate' => 1.0,
                        'is_system_generated' => true,
                        'description' => "DAIF Bounce Reversal on Check {$check->check_number} ({$bounceReason})",
                        'acted_by' => $actor,
                    ], $bounceGlLines, 'POSTED');

                    $check->bounce_journal_id = $bounceJournal->journal_id;
                }
            }

            $check->save();

            return $check;
        });
    }

    /**
     * Get AR customer subledger, open invoices, and aging buckets (Current, 1-30, 31-60, 61-90, 90+ days).
     */
    public function getCustomerLedgerAndAging(string $companyId, ?string $customerId = null): array
    {
        $invQ = AcctSalesInvoice::where('company_id', $companyId)
            ->whereIn('status', ['POSTED', 'PARTIAL']);
        if ($customerId) {
            $invQ->where('customer_id', $customerId);
        }
        $openInvoices = $invQ->orderBy('due_date')->get();

        $aging = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_over_90' => 0.0,
            'total_open' => 0.0,
        ];

        $today = now()->startOfDay();
        foreach ($openInvoices as $inv) {
            $due = Carbon::parse($inv->due_date)->startOfDay();
            $openAmt = (float) $inv->balance_due;
            if ($openAmt <= 0) continue;

            $aging['total_open'] += $openAmt;
            if ($due >= $today) {
                $aging['current'] += $openAmt;
            } else {
                $diffDays = $due->diffInDays($today);
                if ($diffDays <= 30) $aging['days_1_30'] += $openAmt;
                elseif ($diffDays <= 60) $aging['days_31_60'] += $openAmt;
                elseif ($diffDays <= 90) $aging['days_61_90'] += $openAmt;
                else $aging['days_over_90'] += $openAmt;
            }
        }

        $openCredits = AcctCustomerMemo::where('company_id', $companyId)
            ->where('memo_type', 'CREDIT')
            ->where('status', 'POSTED')
            ->where('unapplied_amount', '>', 0)
            ->sum('unapplied_amount');

        return [
            'aging' => $aging,
            'open_invoices_count' => $openInvoices->count(),
            'total_open_receivables' => round($aging['total_open'], 2),
            'total_open_credits' => round((float) $openCredits, 2),
            'net_receivable_due' => round($aging['total_open'] - (float) $openCredits, 2),
        ];
    }

    /**
     * Synchronize linked Sales Order billed, paid, balance due, and lifecycle state
     * whenever a sales invoice posts or receives collections.
     */
    public function syncSalesOrderPaymentState(?string $salesOrderId): void
    {
        if (!$salesOrderId || !Schema::hasTable('sales_orders')) {
            return;
        }

        $order = SalesOrder::where('sales_order_id', $salesOrderId)
            ->lockForUpdate()
            ->first();

        if (!$order) {
            return;
        }

        $invoices = AcctSalesInvoice::where('sales_order_id', $salesOrderId)
            ->whereIn('status', ['POSTED', 'PARTIAL', 'PAID'])
            ->get();

        $amountBilled = round((float) $invoices->sum('total_amount'), 4);
        $amountPaid = round((float) $invoices->sum('paid_amount'), 4);
        $balanceDue = max(0.0, round($amountBilled - $amountPaid, 4));

        if ($amountBilled <= 0.0001) {
            $paymentStatus = 'UNBILLED';
        } elseif ($balanceDue <= 0.05) {
            $paymentStatus = 'PAID';
        } elseif ($amountPaid > 0.0001) {
            $paymentStatus = 'PARTIAL';
        } else {
            $paymentStatus = 'UNPAID';
        }

        $order->amount_billed = $amountBilled;
        $order->amount_paid = $amountPaid;
        $order->balance_due = $balanceDue;
        $order->payment_status = $paymentStatus;

        $fulfillment = strtoupper((string) $order->fulfillment_status);
        $currentStatus = strtoupper((string) $order->status);

        if ($currentStatus !== 'CANCELLED' && $currentStatus !== 'ARCHIVED') {
            if ($fulfillment === 'DELIVERED' && $paymentStatus === 'PAID') {
                $order->status = 'CLOSED';
            } elseif ($amountBilled > 0.0001 && in_array($currentStatus, ['APPROVED', 'PARTIALLY_DELIVERED', 'DELIVERED', 'BILLED', 'CLOSED'], true)) {
                $order->status = 'BILLED';
            }
        }

        $order->save();

        // Sync line invoiced quantities from all posted/partial/paid invoice lines
        if (Schema::hasTable('sales_order_lines')) {
            $invoicedByLine = AcctSalesInvoiceLine::whereIn('invoice_id', $invoices->pluck('invoice_id')->all())
                ->whereNotNull('sales_order_line_id')
                ->get(['sales_order_line_id', 'quantity'])
                ->groupBy('sales_order_line_id')
                ->map(fn ($rows) => round((float) $rows->sum('quantity'), 4));

            $order->load('lines');
            foreach ($order->lines as $soLine) {
                $qtyInv = (float) ($invoicedByLine->get($soLine->sales_order_line_id) ?? 0.0);
                if (abs((float) $soLine->qty_invoiced - $qtyInv) > 0.0001) {
                    $soLine->qty_invoiced = $qtyInv;
                    $soLine->save();
                }
            }
        }
    }
}

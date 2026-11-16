<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctBankAccount;
use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctCheckRegister;
use App\Models\Accounting\AcctTaxCode;
use App\Models\Accounting\AcctVendorMemo;
use App\Models\Accounting\AcctVendorPayment;
use App\Models\Accounting\AcctVendorPaymentLine;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Accounting\VendorBillLine;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountingApService
{
    public function __construct(
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Create or update a Vendor Bill (with optional 3-way match) and post to GL if targetStatus is POSTED.
     */
    public function saveDraftOrPostBill(array $headerData, array $linesData, string $targetStatus = 'DRAFT'): VendorBillHeader
    {
        $targetStatus = strtoupper(trim($targetStatus));
        $companyId = (string) ($headerData['company_id'] ?? '1');
        $actor = $headerData['acted_by'] ?? (Auth::user()->name ?? 'Accounts Payable Specialist');

        if (empty($headerData['vendor_id'])) {
            throw new InvalidArgumentException('Vendor is required for Vendor Bill.');
        }
        if (empty($headerData['vendor_invoice_number'])) {
            throw new InvalidArgumentException('Vendor Invoice / Reference Number is required.');
        }
        if (empty($linesData)) {
            throw new InvalidArgumentException('Vendor Bill must contain at least one line item.');
        }

        return DB::transaction(function () use ($headerData, $linesData, $targetStatus, $companyId, $actor) {
            $bill = null;
            if (!empty($headerData['vendor_bill_id'])) {
                $bill = VendorBillHeader::where('vendor_bill_id', $headerData['vendor_bill_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array(strtoupper($bill->vendor_bill_status), ['POSTED', 'PAID'], true)) {
                    throw new DomainException("Immutable Record: Cannot modify {$bill->vendor_bill_status} bill [{$bill->vendor_bill_number}].");
                }
            }

            $billNumber = $bill
                ? $bill->vendor_bill_number
                : ($headerData['vendor_bill_number'] ?? $this->numberingService->generateNextNumber($companyId, 'BILL', 'BILL'));

            $currency = strtoupper((string) ($headerData['bill_currency_code'] ?? 'PHP'));
            $rate = max(0.000001, (float) ($headerData['bill_exchange_rate'] ?? 1.0));

            // Compute lines, taxes, and 3-way match status
            $subtotal = 0.0;
            $taxTotal = 0.0;
            $normalizedLines = [];
            $lineNo = 1;
            $hasGrnLink = false;
            $grnCostSum = 0.0;

            foreach ($linesData as $raw) {
                $qty = max(0.0001, (float) ($raw['bill_quantity'] ?? $raw['quantity'] ?? 1.0));
                $unitPrice = max(0.0, (float) ($raw['bill_unit_price'] ?? $raw['unit_price'] ?? 0.0));
                $lineSubtotal = round($qty * $unitPrice, 4);

                $taxCodeStr = strtoupper((string) ($raw['tax_code'] ?? 'VAT_12'));
                $taxCodeObj = AcctTaxCode::where('company_id', $companyId)->where('tax_code', $taxCodeStr)->first();
                $isZeroTax = in_array($taxCodeStr, ['EXEMPT', 'ZERO_RATED', 'VAT_EXEMPT', 'VAT_ZERO'], true);
                $taxRate = $taxCodeObj ? (float) $taxCodeObj->rate_pct : ($isZeroTax ? 0.0 : 12.0);
                $lineTax = ($isZeroTax || $taxRate <= 0) ? 0.0 : round($lineSubtotal * ($taxRate / 100), 4);
                $lineTotal = round($lineSubtotal + $lineTax, 4);

                if (!empty($raw['stock_in_line_id'])) {
                    $hasGrnLink = true;
                    $grnCostSum += (float) ($raw['grn_unit_cost'] ?? $unitPrice) * $qty;
                }

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $normalizedLines[] = [
                    'vendor_bill_line_id' => $raw['vendor_bill_line_id'] ?? (string) Str::uuid(),
                    'line_number' => $lineNo++,
                    'item_id' => $raw['item_id'] ?? (string) Str::uuid(),
                    'po_line_id' => $raw['po_line_id'] ?? null,
                    'stock_in_line_id' => $raw['stock_in_line_id'] ?? null,
                    'bill_line_description' => $raw['bill_line_description'] ?? ($raw['description'] ?? 'Bill Item'),
                    'bill_quantity' => $qty,
                    'purchase_uom_code' => $raw['purchase_uom_code'] ?? ($raw['uom'] ?? 'Unit'),
                    'bill_unit_price' => $unitPrice,
                    'tax_code' => $taxCodeStr,
                    'bill_line_tax_amount' => $lineTax,
                    'bill_line_subtotal' => $lineSubtotal,
                    'bill_line_total' => $lineTotal,
                    'gl_expense_account_id' => $raw['gl_expense_account_id'] ?? null,
                ];
            }

            $shipping = max(0.0, (float) ($headerData['bill_shipping_amount'] ?? 0.0));
            $grandTotal = round($subtotal + $taxTotal + $shipping, 4);

            // Determine 3-Way Match Status
            $poId = $headerData['purchase_order_id'] ?? ($bill?->purchase_order_id);
            $matchStatus = 'DIRECT_EXPENSE';
            if (!empty($poId) && $hasGrnLink) {
                $variance = round(abs($subtotal - $grnCostSum), 2);
                $matchStatus = $variance <= 0.05 ? 'MATCHED' : 'DISCREPANCY';
            } elseif (!empty($poId)) {
                $matchStatus = 'PO_ONLY';
            }

            $creditApplied = max(0.0, (float) ($headerData['credit_applied_amount'] ?? 0.0));
            $existingPaid = $bill ? (float) $bill->bill_paid_amount : 0.0;

            $payload = [
                'vendor_bill_number' => $billNumber,
                'vendor_invoice_number' => trim((string) $headerData['vendor_invoice_number']),
                'company_id' => $companyId,
                'branch_id' => (string) ($headerData['branch_id'] ?? 'BR-01'),
                'vendor_id' => (string) $headerData['vendor_id'],
                'purchase_order_id' => $poId,
                'vendor_invoice_date' => $headerData['vendor_invoice_date'] ?? now()->toDateString(),
                'bill_received_date' => $headerData['bill_received_date'] ?? now()->toDateString(),
                'bill_due_date' => $headerData['bill_due_date'] ?? now()->addDays(30)->toDateString(),
                'bill_payment_terms_code' => $headerData['bill_payment_terms_code'] ?? 'NET_30',
                'bill_currency_code' => $currency,
                'bill_exchange_rate' => $rate,
                'bill_subtotal_amount' => $subtotal,
                'bill_tax_amount' => $taxTotal,
                'bill_shipping_amount' => $shipping,
                'bill_total_amount' => $grandTotal,
                'bill_paid_amount' => $existingPaid,
                'bill_match_status' => $matchStatus,
                'vendor_bill_status' => $targetStatus,
                'vendor_bill_notes' => $headerData['vendor_bill_notes'] ?? null,
                'vendor_bill_created_by' => $bill ? $bill->vendor_bill_created_by : (string) (Auth::id() ?? Str::uuid()),
            ];

            if ($targetStatus === 'POSTED') {
                $payload['vendor_bill_posted_at'] = now();
                $payload['vendor_bill_posted_by'] = (string) (Auth::id() ?? Str::uuid());
            }

            if ($bill) {
                $bill->fill($payload);
                $bill->save();
                VendorBillLine::where('vendor_bill_id', $bill->vendor_bill_id)->delete();
            } else {
                $bill = VendorBillHeader::create($payload);
            }

            foreach ($normalizedLines as $lineRow) {
                $lineRow['vendor_bill_id'] = $bill->vendor_bill_id;
                VendorBillLine::create($lineRow);
            }

            // If targetStatus === 'POSTED', generate dual-entry General Ledger voucher and settle credits/PO advances
            if ($targetStatus === 'POSTED') {
                $this->postBillToGeneralLedger($bill, $normalizedLines, $grnCostSum, $hasGrnLink, $actor);

                $linkedPo = !empty($poId) && Schema::hasTable('purchase_order_header')
                    ? $this->findPurchaseOrderRecord($poId)
                    : null;

                $actualCreditUsed = 0.0;
                if ($creditApplied > 0.0001) {
                    $vendorKeys = $this->resolveVendorKeyVariants((string) $bill->vendor_id);
                    $actualCreditUsed = $this->applyVendorCredits(
                        $companyId,
                        $vendorKeys,
                        min($creditApplied, max(0.0, $grandTotal - $existingPaid)),
                        $bill,
                        $linkedPo?->po_number,
                        $actor
                    );
                }

                $poAdvanceUsed = 0.0;
                if ($linkedPo && $existingPaid <= 0.0001 && (float) $linkedPo->amount_paid > 0.0001) {
                    $otherBillsPaid = (float) VendorBillHeader::where('company_id', $companyId)
                        ->where('purchase_order_id', (string) $linkedPo->id)
                        ->where('vendor_bill_id', '!=', $bill->vendor_bill_id)
                        ->sum('bill_paid_amount');
                    $unallocatedPoAdvance = max(0.0, (float) $linkedPo->amount_paid - $otherBillsPaid);
                    $poAdvanceUsed = min($unallocatedPoAdvance, max(0.0, $grandTotal - $existingPaid - $actualCreditUsed));
                }

                $newPaidAmount = min($grandTotal, round($existingPaid + $actualCreditUsed + $poAdvanceUsed, 4));
                if ($newPaidAmount > $existingPaid + 0.0001) {
                    $bill->bill_paid_amount = $newPaidAmount;
                    $bill->vendor_bill_status = ($newPaidAmount >= $grandTotal - 0.05) ? 'PAID' : 'PARTIAL';
                    $bill->save();
                }

                if (!empty($poId)) {
                    $this->syncPurchaseOrderPaymentState($poId);
                }
            }

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: $bill->branch_id,
                module: 'AP',
                documentType: 'VENDOR_BILL',
                documentId: $bill->vendor_bill_id,
                documentNumber: $bill->vendor_bill_number,
                action: $targetStatus === 'POSTED' ? 'POSTED' : ($bill->wasRecentlyCreated ? 'CREATED' : 'UPDATED'),
                summary: "Vendor Bill {$bill->vendor_bill_number} (Inv #{$bill->vendor_invoice_number}) saved as {$bill->vendor_bill_status} — ₱" . number_format($bill->bill_total_amount, 2),
                actedBy: $actor
            );

            return $bill->fresh(['lines']);
        });
    }

    /**
     * Post a Vendor Bill into General Ledger (Dr GRNI or Expense, Dr Input VAT, Cr EWT, Cr AP Trade).
     */
    protected function postBillToGeneralLedger(VendorBillHeader $bill, array $lines, float $grnCostSum, bool $hasGrnLink, string $actor): void
    {
        $companyId = (string) $bill->company_id;
        $branchId = (string) ($bill->branch_id ?: 'BR-01');

        $apAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'ACCOUNTS_PAYABLE');
        $vatInputAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'INPUT_VAT');

        $glLines = [];

        // Debit side: GRNI clearing (if inventory GRN linked) or direct Expense accounts
        if ($hasGrnLink) {
            $grniAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'GRNI_ACCRUED_PURCHASES');
            $grnAmount = round($grnCostSum > 0 ? $grnCostSum : (float) $bill->bill_subtotal_amount, 4);

            $glLines[] = [
                'account_id' => $grniAccount->account_id,
                'branch_id' => $branchId,
                'transaction_debit' => $grnAmount,
                'transaction_credit' => 0.0,
                'description' => "Clearing GRNI for Bill {$bill->vendor_bill_number} (PO #{$bill->purchase_order_id})",
            ];

            // Purchase Price Variance (when Bill unit price differs from GRN cost)
            $variance = round((float) $bill->bill_subtotal_amount - $grnAmount, 4);
            if (abs($variance) > 0.0001) {
                $ppvAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'PURCHASE_PRICE_VARIANCE');
                $glLines[] = [
                    'account_id' => $ppvAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $variance > 0 ? $variance : 0.0,
                    'transaction_credit' => $variance < 0 ? abs($variance) : 0.0,
                    'description' => "Purchase Price Variance on Bill {$bill->vendor_bill_number}",
                ];
            }
        } else {
            // Direct expense lines
            $fallbackExpAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'COGS');
            foreach ($lines as $l) {
                $expId = $l['gl_expense_account_id'] ?: $fallbackExpAccount->account_id;
                $glLines[] = [
                    'account_id' => $expId,
                    'branch_id' => $branchId,
                    'transaction_debit' => (float) $l['bill_line_subtotal'],
                    'transaction_credit' => 0.0,
                    'description' => $l['bill_line_description'],
                ];
            }
        }

        // Debit Input VAT
        if ((float) $bill->bill_tax_amount > 0) {
            $glLines[] = [
                'account_id' => $vatInputAccount->account_id,
                'branch_id' => $branchId,
                'transaction_debit' => (float) $bill->bill_tax_amount,
                'transaction_credit' => 0.0,
                'description' => "Input VAT on Bill {$bill->vendor_bill_number}",
            ];
        }

        // Debit Freight / Shipping Inward (if applicable)
        if ((float) $bill->bill_shipping_amount > 0) {
            $shippingAccount = null;
            try {
                $shippingAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'COGS');
            } catch (\Throwable $e) {
                $shippingAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'GENERAL_EXPENSE');
            }

            $glLines[] = [
                'account_id' => $shippingAccount->account_id,
                'branch_id' => $branchId,
                'transaction_debit' => (float) $bill->bill_shipping_amount,
                'transaction_credit' => 0.0,
                'description' => "Freight / Shipping on Bill {$bill->vendor_bill_number}",
            ];
        }

        // Credit Purchase Discounts (if applicable)
        if ((float) $bill->bill_discount_amount > 0) {
            $discAccount = null;
            try {
                $discAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'PURCHASE_DISCOUNT');
            } catch (\Throwable $e) {
                $discAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'COGS');
            }

            $glLines[] = [
                'account_id' => $discAccount->account_id,
                'branch_id' => $branchId,
                'transaction_debit' => 0.0,
                'transaction_credit' => (float) $bill->bill_discount_amount,
                'description' => "Purchase Discount on Bill {$bill->vendor_bill_number}",
            ];
        }

        // Credit Accounts Payable (Net total payable to vendor)
        $glLines[] = [
            'account_id' => $apAccount->account_id,
            'branch_id' => $branchId,
            'partner_type' => 'VENDOR',
            'partner_id' => $bill->vendor_id,
            'transaction_debit' => 0.0,
            'transaction_credit' => (float) $bill->bill_total_amount,
            'description' => "AP Trade for Vendor Bill {$bill->vendor_bill_number}",
        ];

        // Save & Post Journal
        $this->postingService->saveDraftOrPostJournal([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'journal_date' => $bill->vendor_invoice_date->toDateString(),
            'journal_type' => 'SYSTEM_AP',
            'source_module' => 'AP',
            'source_document_type' => 'VENDOR_BILL',
            'source_document_id' => $bill->vendor_bill_id,
            'source_document_number' => $bill->vendor_bill_number,
            'currency_code' => $bill->bill_currency_code,
            'exchange_rate' => (float) $bill->bill_exchange_rate,
            'is_system_generated' => true,
            'description' => "Vendor Bill {$bill->vendor_bill_number} (Inv #{$bill->vendor_invoice_number})",
            'acted_by' => $actor,
        ], $glLines, 'POSTED');
    }

    /**
     * Record a Vendor Payment / Voucher disbursement, apply to bills, withhold EWT, and issue BIR Form 2307.
     */
    public function recordVendorPayment(array $paymentData, array $billAllocations): AcctVendorPayment
    {
        $companyId = (string) ($paymentData['company_id'] ?? '1');
        $branchId = (string) ($paymentData['branch_id'] ?? 'BR-01');
        $actor = $paymentData['acted_by'] ?? (Auth::user()->name ?? 'Disbursement Officer');

        if (empty($paymentData['vendor_id'])) {
            throw new InvalidArgumentException('Vendor is required for payment voucher.');
        }

        return DB::transaction(function () use ($paymentData, $billAllocations, $companyId, $branchId, $actor) {
            $paymentNumber = $this->numberingService->generateNextNumber($companyId, 'PV', 'PV');
            $method = strtoupper((string) ($paymentData['payment_method'] ?? 'BANK_TRANSFER'));
            $isPrepayment = (bool) ($paymentData['is_prepayment'] ?? empty($billAllocations));

            $vendorRecord = $this->resolveVendorRecord((string) $paymentData['vendor_id'], $paymentData['vendor_name'] ?? null);
            $resolvedVendorName = $paymentData['vendor_name']
                ?? ($vendorRecord ? ($vendorRecord->trade_name ?: $vendorRecord->legal_name) : 'Vendor');

            $maturityDate = $paymentData['check_maturity_date'] ?? $paymentData['pdc_maturity_date'] ?? null;

            $disbursementAccId = $paymentData['disbursement_account_id'] ?? null;
            $resolvedBankName = $paymentData['bank_name'] ?? null;
            if (!empty($paymentData['bank_account_id'])) {
                $bankAcc = AcctBankAccount::find($paymentData['bank_account_id']);
                if ($bankAcc) {
                    $resolvedBankName = $resolvedBankName ?: ($bankAcc->bank_name ?: $bankAcc->account_name);
                    if (!$disbursementAccId && !empty($bankAcc->gl_account_id)) {
                        $disbursementAccId = $bankAcc->gl_account_id;
                    }
                } elseif (!$disbursementAccId) {
                    $glAcc = ChartOfAccount::find($paymentData['bank_account_id']);
                    if ($glAcc) {
                        $disbursementAccId = $glAcc->account_id;
                    }
                }
            }
            if (!$disbursementAccId) {
                $role = match ($method) {
                    'CASH' => 'CASH_ON_HAND',
                    'CHECK', 'PDC' => !empty($maturityDate) && Carbon::parse($maturityDate)->isFuture() ? 'PDC_PAYABLE' : 'CASH_IN_BANK',
                    default => 'CASH_IN_BANK',
                };
                $acc = $this->postingService->resolveSystemRoleAccount($companyId, $role);
                $disbursementAccId = $acc->account_id;
            }

            $grossSum = 0.0;
            $allocGrossSum = 0.0;
            $totalAppliedToBills = 0.0;
            $ewtSum = 0.0;
            $allocatedLines = [];
            $linkedPoIds = [];

            if ($isPrepayment) {
                $grossSum = (float) ($paymentData['total_amount'] ?? $paymentData['gross_amount'] ?? $paymentData['net_amount'] ?? 0.0);
                $ewtSum = (float) ($paymentData['total_ewt_amount'] ?? $paymentData['ewt_amount'] ?? 0.0);
            } else {
                foreach ($billAllocations as $alloc) {
                    $billId = $alloc['vendor_bill_id'] ?? null;
                    if (!$billId && (!empty($alloc['purchase_order_id']) || !empty($alloc['po_number']))) {
                        $poTarget = $this->findPurchaseOrderRecord($alloc['purchase_order_id'] ?? $alloc['po_number']);
                        if ($poTarget) {
                            $ensuredBill = $this->ensureBillForPurchaseOrder($poTarget, $companyId, $branchId);
                            $billId = $ensuredBill?->vendor_bill_id;
                        }
                    }
                    if (!$billId) {
                        continue;
                    }

                    $bill = VendorBillHeader::where('vendor_bill_id', $billId)->lockForUpdate()->firstOrFail();

                    $balanceBefore = max(0.0, round((float) $bill->bill_total_amount - (float) $bill->bill_paid_amount, 4));
                    $rawAllocated = max(0.0, (float) ($alloc['allocated_amount'] ?? 0.0));
                    if ($rawAllocated <= 0.0001 && count($billAllocations) > 1) {
                        continue;
                    }
                    $rawAllocated = max(0.01, $rawAllocated);

                    // Cap bill settlement at open balance; any excess automatically rolls into AP Vendor Credit
                    $appliedToBill = $balanceBefore > 0.0001 ? min($rawAllocated, $balanceBefore) : 0.0;

                    $ewtRate = (float) ($alloc['ewt_rate_pct'] ?? 1.0);
                    $ewtTaxCode = $alloc['ewt_tax_code'] ?? 'WC158';
                    $ewtWithheld = isset($alloc['ewt_amount']) ? (float) $alloc['ewt_amount'] : round($rawAllocated * ($ewtRate / 100), 4);

                    $allocGrossSum += $rawAllocated;
                    $totalAppliedToBills += $appliedToBill;
                    $ewtSum += $ewtWithheld;

                    $allocatedLines[] = [
                        'vendor_bill_id' => $billId,
                        'bill_number' => $bill->vendor_bill_number,
                        'bill_amount' => (float) $bill->bill_total_amount,
                        'balance_before' => $balanceBefore,
                        'allocated_amount' => $appliedToBill,
                        'raw_allocated_amount' => $rawAllocated,
                        'ewt_withheld' => $ewtWithheld,
                        'ewt_tax_code' => $ewtTaxCode,
                        'ewt_rate_pct' => $ewtRate,
                        'is_prepayment' => false,
                        'bill_model' => $bill,
                    ];
                }

                $requestedGrossTotal = (float) ($paymentData['total_amount'] ?? $paymentData['gross_amount'] ?? 0.0);
                $grossSum = max($requestedGrossTotal, $allocGrossSum);
                if ($ewtSum === 0.0 && isset($paymentData['total_ewt_amount'])) {
                    $ewtSum = (float) $paymentData['total_ewt_amount'];
                }
            }

            $excessCreditAmount = (!empty($allocatedLines) || (!$isPrepayment && $grossSum > 0.009))
                ? max(0.0, round($grossSum - $totalAppliedToBills, 4))
                : 0.0;

            $netAmount = round($grossSum - $ewtSum, 4);
            $refNumber = $paymentData['bank_reference'] ?? $paymentData['reference_number'] ?? null;
            $checkNumber = $paymentData['check_number'] ?? (in_array($method, ['CHECK', 'PDC'], true) ? $refNumber : null);

            $paymentAttributes = [
                'payment_number' => $paymentNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'vendor_id' => (string) $paymentData['vendor_id'],
                'vendor_name' => $resolvedVendorName,
                'payment_date' => $paymentData['payment_date'] ?? now()->toDateString(),
                'payment_method' => $method,
                'disbursement_account_id' => $disbursementAccId,
                'currency_code' => $paymentData['currency_code'] ?? 'PHP',
                'exchange_rate' => (float) ($paymentData['exchange_rate'] ?? 1.0),
                'gross_amount' => $grossSum,
                'ewt_amount' => $ewtSum,
                'net_amount' => $netAmount,
                'check_number' => $checkNumber,
                'check_date' => $paymentData['check_date'] ?? null,
                'check_maturity_date' => $maturityDate,
                'bank_reference' => $refNumber,
                'is_prepayment' => $isPrepayment,
                'status' => 'POSTED',
                'notes' => $paymentData['notes'] ?? null,
                'created_by' => $actor,
                'posted_by' => $actor,
                'posted_at' => now(),
            ];

            if (Schema::hasColumn('acct_vendor_payments', 'total_amount')) {
                $paymentAttributes['total_amount'] = $grossSum;
                $paymentAttributes['total_ewt_amount'] = $ewtSum;
                $paymentAttributes['total_net_amount'] = $netAmount;
            }

            $payment = AcctVendorPayment::create($paymentAttributes);

            // Save line allocations and update vendor bills
            foreach ($allocatedLines as $a) {
                $bill = $a['bill_model'];
                if ($a['allocated_amount'] > 0.0001) {
                    AcctVendorPaymentLine::create([
                        'payment_id' => $payment->payment_id,
                        'vendor_bill_id' => $a['vendor_bill_id'],
                        'bill_number' => $a['bill_number'],
                        'bill_amount' => $a['bill_amount'],
                        'balance_before' => $a['balance_before'],
                        'allocated_amount' => $a['allocated_amount'],
                        'ewt_withheld' => $a['ewt_withheld'],
                        'ewt_tax_code' => $a['ewt_tax_code'],
                        'ewt_rate_pct' => $a['ewt_rate_pct'],
                        'is_prepayment' => false,
                    ]);

                    $newPaid = min((float) $bill->bill_total_amount, round((float) $bill->bill_paid_amount + $a['allocated_amount'], 4));
                    $bill->bill_paid_amount = $newPaid;
                    $bill->vendor_bill_status = ($newPaid >= (float) $bill->bill_total_amount - 0.05) ? 'PAID' : 'PARTIAL';
                    $bill->save();
                }

                if (!empty($bill->purchase_order_id)) {
                    $linkedPoIds[] = $bill->purchase_order_id;
                }
            }

            if ($excessCreditAmount > 0.009) {
                AcctVendorPaymentLine::create([
                    'payment_id' => $payment->payment_id,
                    'vendor_bill_id' => null,
                    'bill_number' => 'OVERPAYMENT-CREDIT',
                    'bill_amount' => 0.0,
                    'balance_before' => 0.0,
                    'allocated_amount' => $excessCreditAmount,
                    'ewt_withheld' => 0.0,
                    'ewt_tax_code' => 'NONE',
                    'ewt_rate_pct' => 0.0,
                    'is_prepayment' => true,
                ]);
            }

            foreach (array_unique($linkedPoIds) as $poRef) {
                $this->syncPurchaseOrderPaymentState(
                    $poRef,
                    $method,
                    $refNumber ?: $checkNumber ?: $paymentNumber,
                    $payment->payment_date?->toDateString()
                );
            }

            // Post General Ledger Journal for Vendor Payment
            $apRole = ($isPrepayment && empty($allocatedLines)) ? 'VENDOR_PREPAYMENTS' : 'ACCOUNTS_PAYABLE';
            $apAccount = $this->postingService->resolveSystemRoleAccount($companyId, $apRole);
            $ewtAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'EWT_PAYABLE');

            $glLines = [
                [
                    'account_id' => $apAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'VENDOR',
                    'partner_id' => $payment->vendor_id,
                    'transaction_debit' => $grossSum,
                    'transaction_credit' => 0.0,
                    'description' => $excessCreditAmount > 0.009
                        ? "Disbursement & Overpayment Credit for {$payment->vendor_name} ({$payment->payment_number})"
                        : "Disbursement for Vendor {$payment->vendor_name} ({$payment->payment_number})",
                ],
                [
                    'account_id' => $disbursementAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $netAmount,
                    'description' => "Net payment via {$method} ({$payment->payment_number})",
                ],
            ];

            if ($ewtSum > 0.0001) {
                $glLines[] = [
                    'account_id' => $ewtAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $ewtSum,
                    'description' => "EWT Withheld on Payment {$payment->payment_number}",
                ];
            }

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $payment->payment_date->toDateString(),
                'journal_type' => 'SYSTEM_AP',
                'source_module' => 'AP',
                'source_document_type' => 'VENDOR_PAYMENT',
                'source_document_id' => $payment->payment_id,
                'source_document_number' => $payment->payment_number,
                'currency_code' => $payment->currency_code,
                'exchange_rate' => (float) $payment->exchange_rate,
                'is_system_generated' => true,
                'description' => "Vendor Payment Voucher {$payment->payment_number} to {$payment->vendor_name}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $payment->journal_id = $journal->journal_id;
            $payment->save();

            // Automatically record any overpayment / unallocated excess as an AP Vendor Credit Memo
            $generatedCreditMemo = null;
            if ($excessCreditAmount > 0.009) {
                $memoNumber = $this->numberingService->generateNextNumber($companyId, 'VMEMO', 'VCM');
                $firstBill = $allocatedLines[0] ?? null;
                $generatedCreditMemo = AcctVendorMemo::create([
                    'memo_number' => $memoNumber,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'vendor_id' => (string) $payment->vendor_id,
                    'vendor_name' => $payment->vendor_name,
                    'memo_type' => 'CREDIT',
                    'memo_date' => $payment->payment_date->toDateString(),
                    'vendor_bill_id' => $firstBill['vendor_bill_id'] ?? null,
                    'vendor_bill_number' => $firstBill['bill_number'] ?? null,
                    'amount' => $excessCreditAmount,
                    'tax_amount' => 0.0,
                    'total_amount' => $excessCreditAmount,
                    'unapplied_amount' => $excessCreditAmount,
                    'reason' => "Overpayment Credit from Payment Voucher {$payment->payment_number}",
                    'status' => 'POSTED',
                    'journal_id' => $journal->journal_id,
                    'notes' => "Automatic vendor credit balance generated from overpayment on {$payment->payment_number}.",
                    'created_by' => $actor,
                    'posted_by' => $actor,
                    'posted_at' => now(),
                ]);
            }

            // Automatic BIR Form 2307 Certificate generation if EWT was withheld
            if ($ewtSum > 0.0001) {
                $vendorModel = ProcurementVendor::find($payment->vendor_id);
                $vendorTin = $vendorModel?->tax_id ?: '000-000-000-000';

                // Determine base income payment net of VAT
                $incomePaymentBase = $grossSum;
                if (!empty($allocatedLines)) {
                    $firstBill = $allocatedLines[0]['bill_model'] ?? null;
                    if ($firstBill && (float) $firstBill->bill_subtotal_amount > 0) {
                        $incomePaymentBase = (float) $firstBill->bill_subtotal_amount;
                    }
                }

                $rawAtc = (string) ($allocatedLines[0]['ewt_tax_code'] ?? 'WC158');
                $resolvedAtc = in_array(strtoupper($rawAtc), ['NONE', 'CUSTOM', ''], true) ? 'WC158' : $rawAtc;

                $certAttributes = [
                    'certificate_number' => '2307-' . $payment->payment_number,
                    'company_id' => $companyId,
                    'party_type' => 'VENDOR',
                    'party_id' => $payment->vendor_id,
                    'party_name' => $payment->vendor_name,
                    'party_tin' => $vendorTin,
                    'source_document_type' => 'VENDOR_PAYMENT',
                    'source_document_id' => $payment->payment_id,
                    'source_document_number' => $payment->payment_number,
                    'period_from' => Carbon::parse($payment->payment_date)->startOfMonth()->toDateString(),
                    'period_to' => Carbon::parse($payment->payment_date)->endOfMonth()->toDateString(),
                    'tax_code' => $resolvedAtc,
                    'atc_code' => $resolvedAtc,
                    'income_payment_amount' => $incomePaymentBase,
                    'tax_withheld_amount' => $ewtSum,
                    'status' => 'ISSUED',
                    'signed_by' => $actor,
                    'issued_at' => now(),
                ];

                if (Schema::hasColumn('acct_bir_2307_certificates', 'payee_tin')) {
                    $certAttributes['payee_tin'] = $vendorTin;
                }
                if (Schema::hasColumn('acct_bir_2307_certificates', 'vendor_payment_id')) {
                    $certAttributes['vendor_payment_id'] = $payment->payment_id;
                }
                if (Schema::hasColumn('acct_bir_2307_certificates', 'gross_income_payment')) {
                    $certAttributes['gross_income_payment'] = $incomePaymentBase;
                }
                if (Schema::hasColumn('acct_bir_2307_certificates', 'amount_withheld')) {
                    $certAttributes['amount_withheld'] = $ewtSum;
                }

                AcctBir2307Certificate::create($certAttributes);
            }

            // Register Check / PDC if payment was made via check
            if (in_array($method, ['CHECK', 'PDC'], true) && !empty($payment->check_number)) {
                $isMaturityFuture = !empty($payment->check_maturity_date) && Carbon::parse($payment->check_maturity_date)->isFuture();
                AcctCheckRegister::create([
                    'company_id' => $companyId,
                    'direction' => 'OUTGOING',
                    'party_type' => 'VENDOR',
                    'party_id' => $payment->vendor_id,
                    'party_name' => $payment->vendor_name,
                    'bank_name' => $resolvedBankName ?: 'Company Operating Bank',
                    'check_number' => $payment->check_number,
                    'check_date' => $payment->check_date ?: $payment->payment_date,
                    'maturity_date' => $payment->check_maturity_date ?: ($payment->check_date ?: $payment->payment_date),
                    'amount' => $netAmount,
                    'source_document_type' => 'VENDOR_PAYMENT',
                    'source_document_id' => $payment->payment_id,
                    'status' => $isMaturityFuture ? 'ISSUED' : 'CLEARED',
                    'cleared_at' => $isMaturityFuture ? null : now(),
                    'notes' => "Check disbursement for {$payment->payment_number}",
                ]);
            }

            $freshPayment = $payment->fresh(['lines']);
            $freshPayment->setAttribute('generated_credit_memo', $generatedCreditMemo);
            $freshPayment->setAttribute('excess_credit_amount', round($excessCreditAmount, 2));

            return $freshPayment;
        });
    }

    /**
     * Create and post a Vendor Credit or Debit Memo.
     */
    public function createVendorMemo(array $memoData): AcctVendorMemo
    {
        $companyId = (string) ($memoData['company_id'] ?? '1');
        $branchId = (string) ($memoData['branch_id'] ?? 'BR-01');
        $actor = $memoData['acted_by'] ?? (Auth::user()->name ?? 'Accounts Payable Officer');

        return DB::transaction(function () use ($memoData, $companyId, $branchId, $actor) {
            $rawType = strtoupper((string) ($memoData['memo_type'] ?? 'CREDIT'));
            $isCredit = in_array($rawType, ['CREDIT', 'CREDIT_MEMO'], true);
            $memoType = $isCredit ? 'CREDIT' : 'DEBIT';

            $memoNumber = $this->numberingService->generateNextNumber($companyId, 'VMEMO', $isCredit ? 'VCM' : 'VDM');
            $amount = max(0.01, (float) ($memoData['amount'] ?? 0.0));
            $taxAmount = max(0.0, (float) ($memoData['tax_amount'] ?? 0.0));
            $totalAmount = round($amount + $taxAmount, 4);

            $vendorRecord = $this->resolveVendorRecord((string) $memoData['vendor_id'], $memoData['vendor_name'] ?? null);
            $resolvedVendorName = $memoData['vendor_name']
                ?? ($vendorRecord ? ($vendorRecord->trade_name ?: $vendorRecord->legal_name) : 'Vendor');
            $targetBillId = $memoData['vendor_bill_id'] ?? $memoData['target_vendor_bill_id'] ?? null;
            $targetBillNumber = $memoData['vendor_bill_number'] ?? null;
            if ($targetBillId && !$targetBillNumber) {
                $targetBillNumber = VendorBillHeader::where('vendor_bill_id', $targetBillId)->value('vendor_bill_number');
            }

            $memo = AcctVendorMemo::create([
                'memo_number' => $memoNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'vendor_id' => (string) $memoData['vendor_id'],
                'vendor_name' => $resolvedVendorName,
                'memo_type' => $memoType,
                'memo_date' => $memoData['memo_date'] ?? now()->toDateString(),
                'vendor_bill_id' => $targetBillId,
                'vendor_bill_number' => $targetBillNumber,
                'stock_in_id' => $memoData['stock_in_id'] ?? null,
                'grn_number' => $memoData['grn_number'] ?? null,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'unapplied_amount' => $totalAmount,
                'reason' => $memoData['reason'] ?? ($memoData['reason_description'] ?? 'Goods Return / Allowance'),
                'status' => 'POSTED',
                'notes' => $memoData['notes'] ?? null,
                'created_by' => $actor,
                'posted_by' => $actor,
                'posted_at' => now(),
            ]);

            $apAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'ACCOUNTS_PAYABLE');
            $creditTargetAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'INVENTORY_ASSET');

            $glLines = $memoType === 'CREDIT' ? [
                [
                    'account_id' => $apAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'VENDOR',
                    'partner_id' => $memo->vendor_id,
                    'transaction_debit' => $totalAmount,
                    'transaction_credit' => 0.0,
                    'description' => "Vendor Credit Memo {$memo->memo_number}: {$memo->reason}",
                ],
                [
                    'account_id' => $creditTargetAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $totalAmount,
                    'description' => "Return / Offset for Memo {$memo->memo_number}",
                ],
            ] : [
                [
                    'account_id' => $creditTargetAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $totalAmount,
                    'transaction_credit' => 0.0,
                    'description' => "Vendor Debit Memo {$memo->memo_number}",
                ],
                [
                    'account_id' => $apAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'VENDOR',
                    'partner_id' => $memo->vendor_id,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $totalAmount,
                    'description' => "Debit adjustment for {$memo->vendor_name}",
                ],
            ];

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => $memo->memo_date->toDateString(),
                'journal_type' => 'SYSTEM_AP',
                'source_module' => 'AP',
                'source_document_type' => 'VENDOR_MEMO',
                'source_document_id' => $memo->memo_id,
                'source_document_number' => $memo->memo_number,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Vendor {$memoType} Memo {$memo->memo_number}",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $memo->journal_id = $journal->journal_id;
            $memo->save();

            return $memo;
        });
    }

    /**
     * Get AP vendor subledger, open bills, and aging buckets (Current, 1-30, 31-60, 61-90, 90+ days).
     */
    public function getVendorLedgerAndAging(string $companyId, ?string $vendorId = null): array
    {
        $billsQ = VendorBillHeader::where('company_id', $companyId)
            ->whereIn('vendor_bill_status', ['POSTED', 'PARTIAL']);
        if ($vendorId) {
            $billsQ->where('vendor_id', $vendorId);
        }
        $openBills = $billsQ->orderBy('bill_due_date')->get();

        $aging = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_over_90' => 0.0,
            'total_open' => 0.0,
        ];

        $today = now()->startOfDay();
        foreach ($openBills as $b) {
            $due = Carbon::parse($b->bill_due_date)->startOfDay();
            $openAmt = round((float) $b->bill_total_amount - (float) $b->bill_paid_amount, 2);
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

        $openCreditsQuery = AcctVendorMemo::where('company_id', $companyId)
            ->whereIn('memo_type', ['CREDIT', 'CREDIT_MEMO'])
            ->where('status', 'POSTED')
            ->where('unapplied_amount', '>', 0);
        if ($vendorId) {
            $openCreditsQuery->whereIn('vendor_id', $this->resolveVendorKeyVariants($vendorId));
        }
        $openCredits = $openCreditsQuery->sum('unapplied_amount');

        return [
            'aging' => $aging,
            'open_bills_count' => $openBills->count(),
            'total_open_payables' => round($aging['total_open'], 2),
            'total_open_credits' => round((float) $openCredits, 2),
            'net_payable_due' => round($aging['total_open'] - (float) $openCredits, 2),
        ];
    }

    /**
     * Resolve a ProcurementVendor record by numeric id, vendor_code, trade_name, or legal_name.
     */
    public function resolveVendorRecord(?string $vendorRef, ?string $vendorName = null): ?ProcurementVendor
    {
        if (!Schema::hasTable('vendor_master')) {
            return null;
        }

        if ($vendorRef !== null && trim($vendorRef) !== '') {
            $ref = trim($vendorRef);
            if (is_numeric($ref)) {
                $byId = ProcurementVendor::find((int) $ref);
                if ($byId) return $byId;
            }
            $byCode = ProcurementVendor::where('vendor_code', $ref)->first();
            if ($byCode) return $byCode;
        }

        if ($vendorName !== null && trim($vendorName) !== '') {
            $name = trim($vendorName);
            return ProcurementVendor::where('trade_name', $name)
                ->orWhere('legal_name', $name)
                ->first();
        }

        return null;
    }

    /**
     * Collect all matching vendor identifier keys (numeric id + vendor_code) so PO and AP cross-match reliably.
     */
    public function resolveVendorKeyVariants(?string $vendorRef, ?string $vendorName = null): array
    {
        $keys = [];
        if ($vendorRef !== null && trim($vendorRef) !== '') {
            $keys[] = trim($vendorRef);
        }

        $vendor = $this->resolveVendorRecord($vendorRef, $vendorName);
        if ($vendor) {
            $keys[] = (string) $vendor->id;
            if (!empty($vendor->vendor_code)) {
                $keys[] = (string) $vendor->vendor_code;
            }
        }

        return array_values(array_unique(array_filter($keys, fn($k) => $k !== '')));
    }

    protected function findPurchaseOrderRecord(string|int $poRef): ?PurchaseOrder
    {
        if (!Schema::hasTable('purchase_order_header')) {
            return null;
        }

        if (is_numeric($poRef)) {
            $po = PurchaseOrder::where('id', (int) $poRef)->lockForUpdate()->first();
            if ($po) return $po;
        }

        return PurchaseOrder::where('po_number', (string) $poRef)->lockForUpdate()->first();
    }

    /**
     * Deduct up to $amountToApply from open posted Credit Memos for a vendor.
     */
    protected function applyVendorCredits(
        string $companyId,
        array $vendorKeys,
        float $amountToApply,
        ?VendorBillHeader $targetBill = null,
        ?string $poNumber = null,
        ?string $actor = null
    ): float {
        if ($amountToApply <= 0.0001 || empty($vendorKeys)) {
            return 0.0;
        }

        $memos = AcctVendorMemo::where('company_id', $companyId)
            ->whereIn('vendor_id', $vendorKeys)
            ->whereIn('memo_type', ['CREDIT', 'CREDIT_MEMO'])
            ->where('status', 'POSTED')
            ->where('unapplied_amount', '>', 0)
            ->orderBy('memo_date')
            ->lockForUpdate()
            ->get();

        $remaining = round($amountToApply, 4);
        $totalApplied = 0.0;

        foreach ($memos as $memo) {
            if ($remaining <= 0.0001) break;
            $avail = (float) $memo->unapplied_amount;
            if ($avail <= 0.0001) continue;

            $consume = min($avail, $remaining);
            $memo->unapplied_amount = round($avail - $consume, 4);
            if ($targetBill && empty($memo->vendor_bill_id)) {
                $memo->vendor_bill_id = $targetBill->vendor_bill_id;
                $memo->vendor_bill_number = $targetBill->vendor_bill_number;
            }
            $memo->save();

            $remaining = round($remaining - $consume, 4);
            $totalApplied = round($totalApplied + $consume, 4);

            if ($targetBill) {
                $this->postingService->recordAudit(
                    companyId: $companyId,
                    branchId: $targetBill->branch_id,
                    module: 'AP',
                    documentType: 'VENDOR_CREDIT_APPLIED',
                    documentId: $targetBill->vendor_bill_id,
                    documentNumber: $memo->memo_number,
                    action: 'CREDITED',
                    summary: "Applied Vendor Credit {$memo->memo_number} (₱" . number_format($consume, 2) . ") to Bill {$targetBill->vendor_bill_number}" . ($poNumber ? " (PO: {$poNumber})" : ''),
                    actedBy: $actor ?? 'Accounts Payable Specialist',
                    diff: [
                        'memo_id' => $memo->memo_id,
                        'memo_number' => $memo->memo_number,
                        'vendor_bill_id' => $targetBill->vendor_bill_id,
                        'vendor_bill_number' => $targetBill->vendor_bill_number,
                        'po_number' => $poNumber,
                        'applied_amount' => $consume,
                    ]
                );
            }
        }

        return $totalApplied;
    }

    /**
     * Synchronize linked Purchase Order's paid / balance_due / payment_status from AP Vendor Bills.
     */
    public function syncPurchaseOrderPaymentState(string|int $poRef, ?string $method = null, ?string $ref = null, ?string $date = null): void
    {
        $po = $this->findPurchaseOrderRecord($poRef);
        if (!$po) return;

        $bills = VendorBillHeader::where('purchase_order_id', (string) $po->id)->get();
        if ($bills->isEmpty()) return;

        $totalBilledPaid = round((float) $bills->sum('bill_paid_amount'), 2);
        $grossTotal = (float) $po->gross_total > 0 ? (float) $po->gross_total : round((float) $bills->sum('bill_total_amount'), 2);

        $newPaid = max((float) $po->amount_paid, $totalBilledPaid);
        $newPaid = min($grossTotal > 0 ? $grossTotal : $newPaid, round($newPaid, 2));
        $newBalance = max(0.0, round($grossTotal - $newPaid, 2));

        if ($newPaid >= $grossTotal - 0.05 && $grossTotal > 0) {
            $po->payment_status = 'Paid in Full / Cash Out';
        } elseif ($newPaid > 0) {
            $po->payment_status = 'Partial Payment';
        } else {
            $po->payment_status = 'Unpaid / Credit';
        }

        $po->amount_paid = $newPaid;
        $po->balance_due = $newBalance;
        if ($method) $po->payment_method = $method;
        if ($ref) $po->payment_reference = $ref;
        if ($date) $po->payment_date = $date;
        $po->save();
    }

    /**
     * Automatically ensure a POSTED VendorBillHeader exists for an approved/issued Purchase Order.
     */
    public function ensureBillForPurchaseOrder(
        PurchaseOrder $po,
        string $companyId = '1',
        string $branchId = 'BR-01',
        ?string $invoiceNoOverride = null,
        ?string $invoiceDateOverride = null
    ): ?VendorBillHeader {
        if (!Schema::hasTable('vendor_bill_header') || !Schema::hasTable('chart_of_accounts')) {
            return null;
        }

        $statusUpper = strtoupper(trim((string) $po->status));
        if (in_array($statusUpper, ['DRAFT PO', 'DRAFT', 'PENDING APPROVAL', 'PENDING_APPROVAL', 'CANCELLED', 'REFUSED', 'ARCHIVED', ''], true)) {
            return null;
        }

        $poRefs = array_values(array_filter([(string) $po->id, (string) $po->po_number]));
        $existing = VendorBillHeader::where('company_id', $companyId)
            ->whereIn('purchase_order_id', $poRefs)
            ->first();
        if ($existing) {
            return $existing;
        }

        app(AccountingSeedService::class)->ensureSeeded($companyId);
        $po->loadMissing('items');

        $billLines = [];
        foreach ($po->items as $item) {
            $qty = max(0.0001, (float) $item->quantity);
            $unitPrice = max(0.0, (float) $item->unit_price);
            $billLines[] = [
                'po_line_id' => (string) $item->id,
                'bill_line_description' => ($item->sku ? "[{$item->sku}] " : '') . ($item->item_name ?: 'PO Item'),
                'quantity' => $qty,
                'uom' => $item->uom ?: 'Unit',
                'unit_price' => $unitPrice,
                'tax_code' => 'VAT_EXEMPT',
            ];
        }

        if (empty($billLines)) {
            $fallbackTotal = round((float) ($po->gross_total > 0 ? $po->gross_total : $po->subtotal), 4);
            if ($fallbackTotal <= 0.0001) {
                return null;
            }
            $billLines[] = [
                'bill_line_description' => "PO {$po->po_number} Obligation",
                'quantity' => 1,
                'uom' => 'Lot',
                'unit_price' => $fallbackTotal,
                'tax_code' => 'VAT_EXEMPT',
            ];
        }

        $vendorModel = $this->resolveVendorRecord(
            $po->vendor_id ? (string) $po->vendor_id : null,
            $po->vendor_trade_name ?: $po->vendor_name
        );
        $apVendorId = $vendorModel
            ? (string) $vendorModel->id
            : (string) ($po->vendor_id ?: ('PO-' . $po->id));

        // Resolve a valid open fiscal period date so automated syncing never trips on historical PO dates
        $candidateDate = $invoiceDateOverride ?: ($po->order_date ? Carbon::parse($po->order_date)->toDateString() : now()->toDateString());
        $hasOpenForCandidate = \App\Models\Accounting\AcctFiscalPeriod::where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->whereDate('start_date', '<=', $candidateDate)
            ->whereDate('end_date', '>=', $candidateDate)
            ->exists();

        $invoiceDate = $candidateDate;
        if (!$hasOpenForCandidate) {
            $todayStr = now()->toDateString();
            $todayOpen = \App\Models\Accounting\AcctFiscalPeriod::where('company_id', $companyId)
                ->where('status', 'OPEN')
                ->whereDate('start_date', '<=', $todayStr)
                ->whereDate('end_date', '>=', $todayStr)
                ->exists();
            if ($todayOpen) {
                $invoiceDate = $todayStr;
            } else {
                $latestOpen = \App\Models\Accounting\AcctFiscalPeriod::where('company_id', $companyId)
                    ->where('status', 'OPEN')
                    ->orderByDesc('end_date')
                    ->first();
                if ($latestOpen) {
                    $invoiceDate = Carbon::parse($latestOpen->start_date)->toDateString();
                }
            }
        }

        $dueDate = Carbon::parse($invoiceDate)->addDays(30)->toDateString();
        $invoiceNo = $invoiceNoOverride ? trim($invoiceNoOverride) : ('INV-' . $po->po_number);
        $actor = Auth::user()->name ?? 'Automated PO Sync';

        return $this->saveDraftOrPostBill([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'vendor_id' => $apVendorId,
            'purchase_order_id' => (string) $po->id,
            'vendor_invoice_number' => $invoiceNo,
            'vendor_invoice_date' => $invoiceDate,
            'bill_due_date' => $dueDate,
            'vendor_bill_notes' => "Auto-generated AP Obligation from Purchase Order {$po->po_number}",
            'acted_by' => $actor,
        ], $billLines, 'POSTED');
    }

    /**
     * Batch-synchronize all active approved Purchase Orders into Vendor Bills if not yet registered.
     */
    public function syncAllPurchaseOrderBills(string $companyId = '1'): void
    {
        if (
            !Schema::hasTable('purchase_order_header')
            || !Schema::hasTable('vendor_bill_header')
            || !Schema::hasTable('chart_of_accounts')
        ) {
            return;
        }

        $existingPoRefs = VendorBillHeader::where('company_id', $companyId)
            ->whereNotNull('purchase_order_id')
            ->pluck('purchase_order_id')
            ->flip();

        $activePos = PurchaseOrder::with('items')
            ->where('gross_total', '>', 0)
            ->whereNotIn('status', ['Draft PO', 'DRAFT', 'Pending Approval', 'PENDING_APPROVAL', 'Cancelled', 'REFUSED', 'Archived'])
            ->get();

        foreach ($activePos as $po) {
            if ($existingPoRefs->has((string) $po->id) || $existingPoRefs->has((string) $po->po_number)) {
                continue;
            }
            try {
                $this->ensureBillForPurchaseOrder($po, $companyId);
            } catch (\Throwable $e) {
                // Skip non-syncable historical POs (e.g. closed period environments) without failing page render
            }
        }
    }

    /**
     * Record a Purchase Order payment tranche or Vendor Credit application directly into Accounting AP.
     * Creates/updates the linked VendorBillHeader, AcctVendorPayment, and AcctVendorMemo balances atomically.
     */
    public function recordPoPaymentSettlement(array $payload): array
    {
        $companyId = (string) ($payload['company_id'] ?? '1');
        $branchId = (string) ($payload['branch_id'] ?? 'BR-01');
        $actor = $payload['acted_by'] ?? (Auth::user()->name ?? 'Procurement & AP Officer');

        return DB::transaction(function () use ($payload, $companyId, $branchId, $actor) {
            $po = null;
            if (!empty($payload['purchase_order_id'])) {
                $po = $this->findPurchaseOrderRecord($payload['purchase_order_id']);
            }
            if (!$po && !empty($payload['po_number'])) {
                $po = $this->findPurchaseOrderRecord((string) $payload['po_number']);
            }

            if (!$po && !empty($payload['po_number'])) {
                $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
                $gross = (float) ($payload['po_gross_total'] ?? 0.0);
                if ($gross <= 0 && !empty($items)) {
                    foreach ($items as $it) {
                        $gross += ((float) ($it['quantity'] ?? 0) * (float) ($it['unitPrice'] ?? $it['unit_price'] ?? 0));
                    }
                }
                $po = PurchaseOrder::create([
                    'po_number' => (string) $payload['po_number'],
                    'po_type' => $payload['po_type'] ?? 'vendor',
                    'vendor_id' => $payload['vendor_id'] ?? null,
                    'vendor_name' => $payload['vendor_name'] ?? 'Supplier Partner',
                    'vendor_trade_name' => $payload['vendor_trade_name'] ?? ($payload['vendor_name'] ?? 'Supplier Partner'),
                    'order_date' => now()->toDateString(),
                    'status' => $payload['po_status'] ?? 'Approved / Issued',
                    'subtotal' => $gross,
                    'gross_total' => $gross,
                    'balance_due' => $gross,
                    'amount_paid' => 0,
                    'payment_status' => 'Unpaid / Credit',
                ]);
                foreach ($items as $it) {
                    $qty = max(0.01, (float) ($it['quantity'] ?? 1));
                    $price = max(0.0, (float) ($it['unitPrice'] ?? $it['unit_price'] ?? 0));
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'sku' => $it['sku'] ?? null,
                        'item_name' => $it['name'] ?? ($it['item_name'] ?? 'Ordered Item'),
                        'specs' => $it['specs'] ?? null,
                        'category' => $it['category'] ?? 'Raw Ingredients',
                        'uom' => $it['unit'] ?? ($it['uom'] ?? 'PCS'),
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_amount' => round($qty * $price, 2),
                        'status' => 'PENDING',
                    ]);
                }
            }

            if (!$po) {
                throw new InvalidArgumentException('Purchase Order could not be resolved for settlement.');
            }

            $po->load('items');
            $vendorModel = $this->resolveVendorRecord(
                $po->vendor_id ? (string) $po->vendor_id : ($payload['vendor_id'] ?? null),
                $po->vendor_trade_name ?: $po->vendor_name
            );
            $apVendorId = $vendorModel ? (string) $vendorModel->id : (string) ($po->vendor_id ?: ('PO-' . $po->id));
            $apVendorName = $vendorModel ? ($vendorModel->trade_name ?: $vendorModel->legal_name) : ($po->vendor_trade_name ?: $po->vendor_name);
            $vendorKeys = $this->resolveVendorKeyVariants($apVendorId, $apVendorName);

            // Find or auto-generate the linked VendorBillHeader via shared helper
            $bill = VendorBillHeader::where('company_id', $companyId)
                ->whereIn('purchase_order_id', array_filter([(string) $po->id, (string) $po->po_number]))
                ->lockForUpdate()
                ->first();

            if (!$bill) {
                $bill = $this->ensureBillForPurchaseOrder(
                    $po,
                    $companyId,
                    $branchId,
                    $payload['bill_ref'] ?? null,
                    $payload['payment_date'] ?? null
                );
            }

            if (!$bill) {
                throw new InvalidArgumentException("Purchase Order {$po->po_number} has no payable obligation to settle.");
            }

            $openBillBalance = max(0.0, round((float) $bill->bill_total_amount - (float) $bill->bill_paid_amount, 4));
            $requestedAmount = max(0.0, (float) ($payload['amount'] ?? 0.0));
            $isCreditMode = (bool) ($payload['is_credit_application'] ?? false)
                || strcasecmp((string) ($payload['tranche_type'] ?? ''), 'Vendor Credit Application') === 0
                || strcasecmp((string) ($payload['payment_method'] ?? ''), 'VENDOR_CREDIT') === 0;

            $creditAmountRequested = $isCreditMode
                ? ($requestedAmount > 0 ? $requestedAmount : (float) ($payload['credit_amount'] ?? $openBillBalance))
                : max(0.0, (float) ($payload['credit_amount'] ?? 0.0));

            $appliedCredit = 0.0;
            if ($creditAmountRequested > 0.0001 && $openBillBalance > 0.0001) {
                $appliedCredit = $this->applyVendorCredits(
                    $companyId,
                    $vendorKeys,
                    min($creditAmountRequested, $openBillBalance),
                    $bill,
                    $po->po_number,
                    $actor
                );
                if ($appliedCredit > 0.0001) {
                    $newPaid = min((float) $bill->bill_total_amount, round((float) $bill->bill_paid_amount + $appliedCredit, 4));
                    $bill->bill_paid_amount = $newPaid;
                    $bill->vendor_bill_status = ($newPaid >= (float) $bill->bill_total_amount - 0.05) ? 'PAID' : 'PARTIAL';
                    $bill->save();
                    $openBillBalance = max(0.0, round((float) $bill->bill_total_amount - (float) $bill->bill_paid_amount, 4));
                }
            }

            $cashPayRequested = $isCreditMode ? max(0.0, $requestedAmount - $appliedCredit) : $requestedAmount;
            $payment = null;
            if ($cashPayRequested > 0.0001) {
                $rawMethod = strtoupper((string) ($payload['payment_method'] ?? 'BANK_TRANSFER'));
                $normalizedMethod = str_contains($rawMethod, 'CASH') || str_contains($rawMethod, 'PETTY')
                    ? 'CASH'
                    : (str_contains($rawMethod, 'PDC') ? 'PDC' : (str_contains($rawMethod, 'CHECK') ? 'CHECK' : 'BANK_TRANSFER'));

                $ewtRate = (float) ($payload['ewt_rate_pct'] ?? 0.0);
                $ewtAmount = isset($payload['ewt_amount']) && (float) $payload['ewt_amount'] > 0
                    ? (float) $payload['ewt_amount']
                    : round($cashPayRequested * ($ewtRate / 100), 4);

                $payment = $this->recordVendorPayment([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'vendor_id' => $apVendorId,
                    'vendor_name' => $apVendorName,
                    'payment_date' => $payload['payment_date'] ?? now()->toDateString(),
                    'payment_method' => $normalizedMethod,
                    'bank_account_id' => $payload['bank_account_id'] ?? null,
                    'disbursement_account_id' => $payload['disbursement_account_id'] ?? null,
                    'bank_reference' => $payload['reference'] ?? ('PO-' . $po->po_number),
                    'check_number' => in_array($normalizedMethod, ['CHECK', 'PDC'], true) ? ($payload['reference'] ?? null) : null,
                    'total_amount' => $cashPayRequested,
                    'total_ewt_amount' => $ewtAmount,
                    'total_net_amount' => round($cashPayRequested - $ewtAmount, 4),
                    'notes' => trim(($payload['tranche_type'] ?? 'PO Payment') . ' — ' . ($payload['remarks'] ?? "Settled via PO {$po->po_number}")),
                    'acted_by' => $actor,
                ], [
                    [
                        'vendor_bill_id' => $bill->vendor_bill_id,
                        'allocated_amount' => $cashPayRequested,
                        'ewt_rate_pct' => $ewtRate,
                        'ewt_amount' => $ewtAmount,
                        'ewt_tax_code' => $payload['ewt_tax_code'] ?? ($ewtRate > 0 ? 'WC158' : 'NONE'),
                    ],
                ]);
            }

            $this->syncPurchaseOrderPaymentState(
                $po->id,
                $payload['payment_method'] ?? ($isCreditMode ? 'Vendor Credit Application' : 'Bank Transfer / Electronic'),
                $payment?->payment_number ?: ($payload['reference'] ?? 'AP-CREDIT'),
                $payload['payment_date'] ?? now()->toDateString()
            );

            $remainingCredit = (float) AcctVendorMemo::where('company_id', $companyId)
                ->whereIn('vendor_id', $vendorKeys)
                ->whereIn('memo_type', ['CREDIT', 'CREDIT_MEMO'])
                ->where('status', 'POSTED')
                ->where('unapplied_amount', '>', 0)
                ->sum('unapplied_amount');

            return [
                'po' => $po->fresh('items'),
                'bill' => $bill->fresh(['lines', 'paymentAllocations.payment']),
                'payment' => $payment?->fresh(['lines']),
                'applied_credit' => round($appliedCredit, 2),
                'remaining_vendor_credit' => round($remainingCredit, 2),
                'generated_credit_memo' => $payment?->getAttribute('generated_credit_memo'),
                'excess_credit_amount' => (float) ($payment?->getAttribute('excess_credit_amount') ?? 0.0),
            ];
        });
    }
}

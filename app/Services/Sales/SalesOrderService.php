<?php

namespace App\Services\Sales;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\Sales\SalesQuote;
use App\Models\Sales\SalesQuoteLine;
use App\Services\Accounting\AccountingArService;
use App\Services\Accounting\AccountingNumberingService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalesOrderService
{
    public function __construct(
        protected SalesPricingService $pricingService,
        protected SalesCreditControlService $creditService,
        protected SalesInventoryService $inventoryService,
        protected SalesPromotionService $promotionService,
        protected AccountingNumberingService $numberingService,
        protected AccountingSeedService $seedService,
        protected AccountingArService $arService,
        protected AccountingPostingService $postingService
    ) {
    }

    public function saveQuoteDraft(array $data, string $actor): SalesQuote
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $branchId = (string) ($data['branch_id'] ?? 'BR-01');
        $this->seedService->ensureSeeded($companyId);

        return DB::transaction(function () use ($data, $companyId, $branchId, $actor) {
            $customer = AcctCustomer::where('customer_id', $data['customer_id'])->firstOrFail();

            $quote = null;
            if (!empty($data['quote_id'])) {
                $quote = SalesQuote::where('quote_id', $data['quote_id'])->lockForUpdate()->firstOrFail();
                if (in_array(strtoupper((string) $quote->status), ['CONVERTED', 'CANCELLED'], true)) {
                    throw new DomainException("Quote [{$quote->quote_number}] is {$quote->status} and cannot be edited.");
                }
            }

            $pricing = $this->pricingService->calculateDocument(
                $companyId,
                $data['lines'] ?? [],
                (string) ($data['discount_type'] ?? 'NONE'),
                (float) ($data['discount_value'] ?? 0.0)
            );

            $quoteNumber = $quote
                ? $quote->quote_number
                : (!empty($data['quote_number']) ? $data['quote_number'] : $this->numberingService->generateNextNumber($companyId, 'SQ', 'SQ'));

            $payload = [
                'quote_number' => $quoteNumber,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'source_warehouse_id' => $data['source_warehouse_id'] ?? null,
                'source_warehouse_name' => $data['source_warehouse_name'] ?? 'Central Commissary & Dry Warehouse',
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'customer_contact' => $data['customer_contact'] ?? $customer->contact_person,
                'customer_email' => $data['customer_email'] ?? $customer->email,
                'customer_phone' => $data['customer_phone'] ?? $customer->phone,
                'quote_date' => $data['quote_date'] ?? now()->toDateString(),
                'valid_until_date' => $data['valid_until_date'] ?? now()->addDays(14)->toDateString(),
                'requested_delivery_date' => $data['requested_delivery_date'] ?? now()->addDays(3)->toDateString(),
                'payment_terms_code' => $data['payment_terms_code'] ?? ($customer->payment_terms_code ?: 'NET_30'),
                'currency_code' => strtoupper((string) ($data['currency_code'] ?? 'PHP')),
                'exchange_rate' => max(0.000001, (float) ($data['exchange_rate'] ?? 1.0)),
                'subtotal_amount' => $pricing['subtotal_amount'],
                'discount_type' => $pricing['discount_type'],
                'discount_value' => $pricing['discount_value'],
                'discount_amount' => $pricing['discount_amount'],
                'tax_amount' => $pricing['tax_amount'],
                'gross_total' => $pricing['gross_total'],
                'estimated_cost_total' => $pricing['estimated_cost_total'],
                'estimated_margin_pct' => $pricing['estimated_margin_pct'],
                'status' => $quote ? $quote->status : 'DRAFT',
                'customer_reference' => $data['customer_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'updated_by' => $actor,
            ];

            if ($quote) {
                $quote->fill($payload);
                $quote->save();
                SalesQuoteLine::where('quote_id', $quote->quote_id)->delete();
            } else {
                $payload['created_by'] = $actor;
                $quote = SalesQuote::create($payload);
            }

            foreach ($pricing['lines'] as $lineData) {
                SalesQuoteLine::create(array_merge($lineData, [
                    'quote_id' => $quote->quote_id,
                ]));
            }

            return $quote->fresh(['lines', 'customer']);
        });
    }

    public function transitionQuoteStatus(string $quoteId, string $targetStatus, array $meta, string $actor): SalesQuote
    {
        $target = strtoupper(trim($targetStatus));
        $allowedTargets = ['DRAFT', 'SENT', 'CONFIRMED', 'EXPIRED', 'CANCELLED', 'ARCHIVED'];
        if (!in_array($target, $allowedTargets, true)) {
            throw new DomainException("Invalid Sales Quote target status [{$target}].");
        }

        return DB::transaction(function () use ($quoteId, $target, $meta, $actor) {
            $quote = SalesQuote::with('lines')->where('quote_id', $quoteId)->lockForUpdate()->firstOrFail();
            $current = strtoupper((string) $quote->status);

            if ($current === 'CONVERTED') {
                throw new DomainException("Quote [{$quote->quote_number}] has already been converted to Sales Order [{$quote->sales_order_number}].");
            }

            if ($target === 'CONFIRMED') {
                if ($quote->valid_until_date && Carbon::parse($quote->valid_until_date)->lt(now()->startOfDay())) {
                    $quote->status = 'EXPIRED';
                    $quote->save();
                    throw new DomainException("Quote [{$quote->quote_number}] expired on {$quote->valid_until_date->toDateString()}. Please clone a new Revision (v" . ($quote->revision_no + 1) . ") before confirming.");
                }
                if ($quote->lines->isEmpty()) {
                    throw new DomainException("Quote [{$quote->quote_number}] must contain at least one line item before confirmation.");
                }
                $quote->confirmed_at = now();
                $quote->confirmed_by = $actor;
            }

            if ($target === 'CANCELLED' && !empty($meta['rejection_reason'])) {
                $quote->rejection_reason = $meta['rejection_reason'];
            }

            $quote->status = $target;
            $quote->updated_by = $actor;
            $quote->save();

            return $quote->fresh(['lines', 'customer']);
        });
    }

    public function createQuoteRevision(string $quoteId, string $actor): SalesQuote
    {
        return DB::transaction(function () use ($quoteId, $actor) {
            $source = SalesQuote::with('lines')->where('quote_id', $quoteId)->lockForUpdate()->firstOrFail();
            $companyId = (string) ($source->company_id ?: '1');
            $this->seedService->ensureSeeded($companyId);

            $maxRevision = (int) SalesQuote::where('quote_group_id', $source->quote_group_id)->max('revision_no');
            $nextRevision = max($source->revision_no + 1, $maxRevision + 1);
            $newNumber = $this->numberingService->generateNextNumber($companyId, 'SQ', 'SQ');

            $rev = SalesQuote::create([
                'quote_number' => $newNumber,
                'quote_group_id' => $source->quote_group_id ?: $source->quote_id,
                'revision_no' => $nextRevision,
                'company_id' => $companyId,
                'branch_id' => $source->branch_id,
                'source_warehouse_id' => $source->source_warehouse_id,
                'source_warehouse_name' => $source->source_warehouse_name,
                'customer_id' => $source->customer_id,
                'customer_name' => $source->customer_name,
                'customer_contact' => $source->customer_contact,
                'customer_email' => $source->customer_email,
                'customer_phone' => $source->customer_phone,
                'quote_date' => now()->toDateString(),
                'valid_until_date' => now()->addDays(14)->toDateString(),
                'requested_delivery_date' => now()->addDays(3)->toDateString(),
                'payment_terms_code' => $source->payment_terms_code,
                'currency_code' => $source->currency_code,
                'exchange_rate' => $source->exchange_rate,
                'subtotal_amount' => $source->subtotal_amount,
                'discount_type' => $source->discount_type,
                'discount_value' => $source->discount_value,
                'discount_amount' => $source->discount_amount,
                'tax_amount' => $source->tax_amount,
                'gross_total' => $source->gross_total,
                'estimated_cost_total' => $source->estimated_cost_total,
                'estimated_margin_pct' => $source->estimated_margin_pct,
                'status' => 'DRAFT',
                'customer_reference' => $source->customer_reference,
                'notes' => $source->notes,
                'terms_and_conditions' => $source->terms_and_conditions,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]);

            foreach ($source->lines as $line) {
                SalesQuoteLine::create([
                    'quote_id' => $rev->quote_id,
                    'line_number' => $line->line_number,
                    'inventory_item_id' => $line->inventory_item_id,
                    'sku' => $line->sku,
                    'item_name' => $line->item_name,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'uom' => $line->uom,
                    'uom_conversion_factor' => $line->uom_conversion_factor,
                    'unit_cost_snapshot' => $line->unit_cost_snapshot,
                    'unit_price' => $line->unit_price,
                    'discount_pct' => $line->discount_pct,
                    'discount_amount' => $line->discount_amount,
                    'tax_code' => $line->tax_code,
                    'tax_rate_pct' => $line->tax_rate_pct,
                    'tax_amount' => $line->tax_amount,
                    'line_subtotal' => $line->line_subtotal,
                    'line_total' => $line->line_total,
                    'line_notes' => $line->line_notes,
                ]);
            }

            return $rev->fresh(['lines', 'customer']);
        });
    }

    public function convertQuoteToSalesOrder(string $quoteId, array $overrides, string $actor, bool $autoConfirm = false): SalesOrder
    {
        return DB::transaction(function () use ($quoteId, $overrides, $actor, $autoConfirm) {
            $quote = SalesQuote::with(['lines', 'customer'])
                ->where('quote_id', $quoteId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!empty($quote->sales_order_id) || strtoupper((string) $quote->status) === 'CONVERTED') {
                throw new DomainException("Quote [{$quote->quote_number}] was already converted to Sales Order [{$quote->sales_order_number}].");
            }

            if ($quote->valid_until_date && Carbon::parse($quote->valid_until_date)->lt(now()->startOfDay())) {
                $quote->status = 'EXPIRED';
                $quote->save();
                throw new DomainException("Cannot convert expired quote [{$quote->quote_number}]. Clone a revision first.");
            }

            if (strtoupper((string) $quote->status) !== 'CONFIRMED') {
                throw new DomainException("Quote [{$quote->quote_number}] must be CONFIRMED by customer before converting to a Sales Order.");
            }

            $companyId = (string) ($quote->company_id ?: '1');
            $this->seedService->ensureSeeded($companyId);
            $customer = $quote->customer ?: AcctCustomer::where('customer_id', $quote->customer_id)->firstOrFail();
            $soNumber = $this->numberingService->generateNextNumber($companyId, 'SORD', 'SLS');

            $order = SalesOrder::create([
                'order_number' => $soNumber,
                'quote_id' => $quote->quote_id,
                'quote_number' => $quote->quote_number,
                'company_id' => $companyId,
                'branch_id' => $quote->branch_id ?: 'BR-01',
                'source_warehouse_id' => $quote->source_warehouse_id,
                'source_warehouse_name' => $quote->source_warehouse_name,
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'customer_trade_name' => $customer->trade_name ?: $customer->customer_name,
                'customer_tin' => $customer->tin,
                'customer_contact' => $quote->customer_contact ?: $customer->contact_person,
                'customer_email' => $quote->customer_email ?: $customer->email,
                'customer_phone' => $quote->customer_phone ?: $customer->phone,
                'billing_address' => $customer->address,
                'shipping_address' => $overrides['shipping_address'] ?? $customer->shipping_address ?? $customer->address,
                'customer_po_number' => $overrides['customer_po_number'] ?? $quote->customer_reference,
                'order_date' => now()->toDateString(),
                'requested_delivery_date' => $overrides['requested_delivery_date'] ?? ($quote->requested_delivery_date?->toDateString() ?? now()->addDays(3)->toDateString()),
                'payment_terms_code' => $overrides['payment_terms_code'] ?? $quote->payment_terms_code,
                'currency_code' => $quote->currency_code,
                'exchange_rate' => $quote->exchange_rate,
                'subtotal_amount' => $quote->subtotal_amount,
                'discount_type' => $quote->discount_type,
                'discount_value' => $quote->discount_value,
                'discount_amount' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'gross_total' => $quote->gross_total,
                'total_cost_snapshot' => $quote->estimated_cost_total,
                'amount_billed' => 0.0,
                'amount_paid' => 0.0,
                'balance_due' => 0.0,
                'payment_status' => 'UNBILLED',
                'fulfillment_status' => 'UNFULFILLED',
                'credit_review_status' => 'NOT_EVALUATED',
                'status' => 'DRAFT',
                'salesperson_name' => $overrides['salesperson_name'] ?? $customer->sales_rep_name ?? $actor,
                'special_notes' => $overrides['special_notes'] ?? $quote->notes,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]);

            foreach ($quote->lines as $qLine) {
                SalesOrderLine::create([
                    'sales_order_id' => $order->sales_order_id,
                    'quote_line_id' => $qLine->quote_line_id,
                    'line_number' => $qLine->line_number,
                    'inventory_item_id' => $qLine->inventory_item_id,
                    'sku' => $qLine->sku,
                    'item_name' => $qLine->item_name,
                    'description' => $qLine->description,
                    'uom' => $qLine->uom,
                    'uom_conversion_factor' => $qLine->uom_conversion_factor,
                    'qty_ordered' => $qLine->quantity,
                    'qty_reserved' => 0.0,
                    'qty_delivered' => 0.0,
                    'qty_invoiced' => 0.0,
                    'unit_cost_snapshot' => $qLine->unit_cost_snapshot,
                    'unit_price' => $qLine->unit_price,
                    'discount_pct' => $qLine->discount_pct,
                    'discount_amount' => $qLine->discount_amount,
                    'tax_code' => $qLine->tax_code,
                    'tax_rate_pct' => $qLine->tax_rate_pct,
                    'tax_amount' => $qLine->tax_amount,
                    'line_subtotal' => $qLine->line_subtotal,
                    'line_total' => $qLine->line_total,
                    'line_notes' => $qLine->line_notes,
                ]);
            }

            $quote->status = 'CONVERTED';
            $quote->sales_order_id = $order->sales_order_id;
            $quote->sales_order_number = $order->order_number;
            $quote->updated_by = $actor;
            $quote->save();

            if ($autoConfirm) {
                return $this->confirmSalesOrder($order->sales_order_id, $actor);
            }

            return $order->fresh(['lines', 'customer']);
        });
    }

    /**
     * Create or update a Unified Quote or Direct Sales Order draft.
     */
    public function saveSalesOrderDraft(array $data, string $actor): SalesOrder
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $branchId = (string) ($data['branch_id'] ?? 'BR-01');
        $this->seedService->ensureSeeded($companyId);

        return DB::transaction(function () use ($data, $companyId, $branchId, $actor) {
            $customer = AcctCustomer::where('customer_id', $data['customer_id'])->firstOrFail();

            $order = null;
            if (!empty($data['sales_order_id'])) {
                $order = SalesOrder::where('sales_order_id', $data['sales_order_id'])->lockForUpdate()->firstOrFail();
                if (!in_array(strtoupper((string) $order->status), ['DRAFT', 'QUOTE_DRAFT', 'QUOTE_SENT'], true)) {
                    throw new DomainException("Record [{$order->order_number}] is in [{$order->status}] status and can no longer be edited as a draft.");
                }
            }

            $docStage = strtoupper(trim((string) ($data['document_stage'] ?? ($order?->document_stage ?? 'ORDER'))));
            if (!in_array($docStage, ['QUOTE', 'ORDER'], true)) {
                $docStage = 'ORDER';
            }

            $rawLines = $data['lines'] ?? [];
            $discType = (string) ($data['discount_type'] ?? 'NONE');
            $discVal = (float) ($data['discount_value'] ?? 0.0);
            $discountId = !empty($data['discount_id']) ? $data['discount_id'] : null;
            $discountCode = null;
            $voucherId = null;
            $voucherCode = !empty($data['voucher_code']) ? strtoupper(trim((string) $data['voucher_code'])) : null;
            $voucherDiscountAmt = 0.0;

            // Pre-compute raw lines subtotal to evaluate discount rules & vouchers accurately
            $prePricing = $this->pricingService->calculateDocument($companyId, $rawLines, 'NONE', 0.0);
            $linesRawSub = (float) $prePricing['subtotal_amount'];

            if (!empty($discountId)) {
                $discEval = $this->promotionService->evaluateDiscountRule($companyId, $discountId, $customer, $linesRawSub);
                $discType = $discEval['discount_type'];
                $discVal = $discEval['discount_value'];
                $discountCode = $discEval['discount_code'];

                if (!empty($discEval['vat_exempt_override'])) {
                    foreach ($rawLines as &$rl) {
                        $rl['tax_code'] = 'VAT_EXEMPT';
                    }
                    unset($rl);
                }
            }

            $pricing = $this->pricingService->calculateDocument($companyId, $rawLines, $discType, $discVal);
            if (!empty($discountId) && isset($discEval)) {
                // Enforce cap if discount rule has max_discount_cap
                $cappedAmt = min((float) $pricing['discount_amount'], (float) $discEval['discount_amount']);
                if (abs($cappedAmt - (float) $pricing['discount_amount']) > 0.0001) {
                    $pricing = $this->pricingService->calculateDocument($companyId, $rawLines, 'FIXED', $cappedAmt);
                }
            }

            if (!empty($voucherCode)) {
                $vEval = $this->promotionService->validateVoucher(
                    $companyId,
                    $voucherCode,
                    $customer,
                    (float) $pricing['subtotal_amount'],
                    $order?->sales_order_id
                );
                $voucherId = $vEval['voucher_id'];
                $voucherCode = $vEval['voucher_code'];
                $voucherDiscountAmt = min((float) $pricing['subtotal_amount'], (float) $vEval['discount_amount']);

                // Re-compute net subtotal & tax including voucher discount
                $totalCombinedDisc = round((float) $pricing['discount_amount'] + $voucherDiscountAmt, 4);
                $combinedPricing = $this->pricingService->calculateDocument($companyId, $rawLines, 'FIXED', $totalCombinedDisc);
                $pricing['tax_amount'] = $combinedPricing['tax_amount'];
                $pricing['subtotal_amount'] = $combinedPricing['subtotal_amount'];
                $pricing['gross_total'] = $combinedPricing['gross_total'];
            }

            if ($order) {
                $orderNumber = $order->order_number;
                $quoteNumber = $order->quote_number;
            } elseif ($docStage === 'QUOTE') {
                $quoteNumber = !empty($data['quote_number']) ? $data['quote_number'] : $this->numberingService->generateNextNumber($companyId, 'SQ', 'SQ');
                $orderNumber = $quoteNumber;
            } else {
                $quoteNumber = null;
                $orderNumber = !empty($data['order_number']) ? $data['order_number'] : $this->numberingService->generateNextNumber($companyId, 'SORD', 'SLS');
            }

            $payload = [
                'order_number' => $orderNumber,
                'quote_number' => $quoteNumber,
                'document_stage' => $docStage,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'source_warehouse_id' => $data['source_warehouse_id'] ?? null,
                'source_warehouse_name' => $data['source_warehouse_name'] ?? 'Central Commissary & Dry Warehouse',
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'customer_trade_name' => $customer->trade_name ?: $customer->customer_name,
                'customer_tin' => $customer->tin,
                'customer_contact' => $data['customer_contact'] ?? $customer->contact_person,
                'customer_email' => $data['customer_email'] ?? $customer->email,
                'customer_phone' => $data['customer_phone'] ?? $customer->phone,
                'billing_address' => $data['billing_address'] ?? $customer->address,
                'shipping_address' => $data['shipping_address'] ?? $customer->shipping_address ?? $customer->address,
                'customer_po_number' => $data['customer_po_number'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'valid_until_date' => $data['valid_until_date'] ?? ($docStage === 'QUOTE' ? now()->addDays(14)->toDateString() : null),
                'requested_delivery_date' => $data['requested_delivery_date'] ?? now()->addDays(3)->toDateString(),
                'payment_terms_code' => $data['payment_terms_code'] ?? ($customer->payment_terms_code ?: 'NET_30'),
                'currency_code' => strtoupper((string) ($data['currency_code'] ?? 'PHP')),
                'exchange_rate' => max(0.000001, (float) ($data['exchange_rate'] ?? 1.0)),
                'subtotal_amount' => $pricing['subtotal_amount'],
                'discount_id' => $discountId,
                'discount_code' => $discountCode,
                'discount_type' => $pricing['discount_type'],
                'discount_value' => $pricing['discount_value'],
                'discount_amount' => max(0.0, round((float) $pricing['discount_amount'], 4)),
                'voucher_id' => $voucherId,
                'voucher_code' => $voucherCode,
                'voucher_discount_amount' => $voucherDiscountAmt,
                'tax_amount' => $pricing['tax_amount'],
                'gross_total' => $pricing['gross_total'],
                'total_cost_snapshot' => $pricing['estimated_cost_total'],
                'salesperson_employee_id' => $data['salesperson_employee_id'] ?? null,
                'salesperson_name' => $data['salesperson_name'] ?? $customer->sales_rep_name ?? $actor,
                'special_notes' => $data['special_notes'] ?? null,
                'updated_by' => $actor,
            ];

            if ($order) {
                $order->fill($payload);
                $order->save();
                SalesOrderLine::where('sales_order_id', $order->sales_order_id)->delete();
            } else {
                $payload['status'] = $docStage === 'QUOTE' ? 'QUOTE_DRAFT' : 'DRAFT';
                $payload['revision_no'] = 1;
                $payload['quote_group_id'] = $docStage === 'QUOTE' ? (string) Str::uuid() : null;
                $payload['payment_status'] = 'UNBILLED';
                $payload['fulfillment_status'] = 'UNFULFILLED';
                $payload['credit_review_status'] = 'NOT_EVALUATED';
                $payload['created_by'] = $actor;
                $order = SalesOrder::create($payload);
            }

            foreach ($pricing['lines'] as $idx => $lineData) {
                $rawLine = $rawLines[$idx] ?? [];
                SalesOrderLine::create([
                    'sales_order_id' => $order->sales_order_id,
                    'quote_line_id' => $lineData['quote_line_id'] ?? null,
                    'line_number' => $lineData['line_number'],
                    'inventory_item_id' => $lineData['inventory_item_id'],
                    'sku' => $lineData['sku'],
                    'item_name' => $lineData['item_name'],
                    'description' => $lineData['description'],
                    'uom' => $lineData['uom'],
                    'uom_conversion_factor' => $lineData['uom_conversion_factor'],
                    'qty_ordered' => $lineData['quantity'],
                    'qty_reserved' => 0.0,
                    'qty_delivered' => 0.0,
                    'qty_invoiced' => 0.0,
                    'unit_cost_snapshot' => $lineData['unit_cost_snapshot'],
                    'unit_price' => $lineData['unit_price'],
                    'discount_pct' => $lineData['discount_pct'],
                    'discount_amount' => $lineData['discount_amount'],
                    'tax_code' => $lineData['tax_code'],
                    'tax_rate_pct' => $lineData['tax_rate_pct'],
                    'tax_amount' => $lineData['tax_amount'],
                    'line_subtotal' => $lineData['line_subtotal'],
                    'line_total' => $lineData['line_total'],
                    'line_notes' => $lineData['line_notes'],
                    'bundle_id' => $rawLine['bundle_id'] ?? null,
                    'bundle_code' => $rawLine['bundle_code'] ?? null,
                    'bundle_name' => $rawLine['bundle_name'] ?? null,
                ]);
            }

            return $order->fresh(['lines', 'customer']);
        });
    }

    /**
     * Transition a Unified Sales Quote stage (QUOTE_DRAFT -> QUOTE_SENT -> QUOTE_CONFIRMED).
     */
    public function transitionUnifiedQuoteStage(string $salesOrderId, string $targetStatus, string $actor): SalesOrder
    {
        $target = strtoupper(trim($targetStatus));
        if (!in_array($target, ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED'], true)) {
            throw new DomainException("Invalid unified quote stage [{$target}].");
        }

        return DB::transaction(function () use ($salesOrderId, $target, $actor) {
            $order = SalesOrder::with('lines')->where('sales_order_id', $salesOrderId)->lockForUpdate()->firstOrFail();

            if ($target === 'QUOTE_CONFIRMED') {
                if ($order->valid_until_date && Carbon::parse($order->valid_until_date)->lt(now()->startOfDay())) {
                    throw new DomainException("Quote [{$order->quote_number}] expired on {$order->valid_until_date->toDateString()}. Clone a new Revision before confirming.");
                }
                if ($order->lines->isEmpty()) {
                    throw new DomainException("Quote must contain at least one line item before customer confirmation.");
                }
                $order->confirmed_at = now();
                $order->confirmed_by = $actor;
            }

            $order->status = $target;
            $order->updated_by = $actor;
            $order->save();

            return $order->fresh(['lines', 'customer']);
        });
    }

    /**
     * Clone a new revision (v2, v3...) of a unified Sales Quote inside sales_orders.
     */
    public function cloneUnifiedQuoteRevision(string $salesOrderId, string $actor): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId, $actor) {
            $src = SalesOrder::with('lines')->where('sales_order_id', $salesOrderId)->lockForUpdate()->firstOrFail();
            $companyId = (string) ($src->company_id ?: '1');
            $this->seedService->ensureSeeded($companyId);

            $groupId = $src->quote_group_id ?: $src->sales_order_id;
            $maxRev = (int) SalesOrder::where('quote_group_id', $groupId)->max('revision_no');
            $nextRev = max((int) $src->revision_no + 1, $maxRev + 1);
            $newSqNo = $this->numberingService->generateNextNumber($companyId, 'SQ', 'SQ');

            $clone = SalesOrder::create([
                'order_number' => $newSqNo,
                'quote_number' => $newSqNo,
                'quote_group_id' => $groupId,
                'revision_no' => $nextRev,
                'document_stage' => 'QUOTE',
                'company_id' => $companyId,
                'branch_id' => $src->branch_id,
                'source_warehouse_id' => $src->source_warehouse_id,
                'source_warehouse_name' => $src->source_warehouse_name,
                'customer_id' => $src->customer_id,
                'customer_name' => $src->customer_name,
                'customer_trade_name' => $src->customer_trade_name,
                'customer_tin' => $src->customer_tin,
                'customer_contact' => $src->customer_contact,
                'customer_email' => $src->customer_email,
                'customer_phone' => $src->customer_phone,
                'billing_address' => $src->billing_address,
                'shipping_address' => $src->shipping_address,
                'customer_po_number' => $src->customer_po_number,
                'order_date' => now()->toDateString(),
                'valid_until_date' => now()->addDays(14)->toDateString(),
                'requested_delivery_date' => now()->addDays(3)->toDateString(),
                'payment_terms_code' => $src->payment_terms_code,
                'currency_code' => $src->currency_code,
                'exchange_rate' => $src->exchange_rate,
                'subtotal_amount' => $src->subtotal_amount,
                'discount_id' => $src->discount_id,
                'discount_code' => $src->discount_code,
                'discount_type' => $src->discount_type,
                'discount_value' => $src->discount_value,
                'discount_amount' => $src->discount_amount,
                'voucher_id' => $src->voucher_id,
                'voucher_code' => $src->voucher_code,
                'voucher_discount_amount' => $src->voucher_discount_amount,
                'tax_amount' => $src->tax_amount,
                'gross_total' => $src->gross_total,
                'total_cost_snapshot' => $src->total_cost_snapshot,
                'payment_status' => 'UNBILLED',
                'fulfillment_status' => 'UNFULFILLED',
                'credit_review_status' => 'NOT_EVALUATED',
                'status' => 'QUOTE_DRAFT',
                'salesperson_employee_id' => $src->salesperson_employee_id,
                'salesperson_name' => $src->salesperson_name,
                'special_notes' => $src->special_notes,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]);

            foreach ($src->lines as $l) {
                SalesOrderLine::create([
                    'sales_order_id' => $clone->sales_order_id,
                    'line_number' => $l->line_number,
                    'inventory_item_id' => $l->inventory_item_id,
                    'sku' => $l->sku,
                    'item_name' => $l->item_name,
                    'description' => $l->description,
                    'uom' => $l->uom,
                    'uom_conversion_factor' => $l->uom_conversion_factor,
                    'qty_ordered' => $l->qty_ordered,
                    'qty_reserved' => 0,
                    'qty_delivered' => 0,
                    'qty_invoiced' => 0,
                    'unit_cost_snapshot' => $l->unit_cost_snapshot,
                    'unit_price' => $l->unit_price,
                    'discount_pct' => $l->discount_pct,
                    'discount_amount' => $l->discount_amount,
                    'tax_code' => $l->tax_code,
                    'tax_rate_pct' => $l->tax_rate_pct,
                    'tax_amount' => $l->tax_amount,
                    'line_subtotal' => $l->line_subtotal,
                    'line_total' => $l->line_total,
                    'line_notes' => $l->line_notes,
                    'bundle_id' => $l->bundle_id,
                    'bundle_code' => $l->bundle_code,
                    'bundle_name' => $l->bundle_name,
                ]);
            }

            return $clone->fresh(['lines', 'customer']);
        });
    }

    /**
     * Confirm a DRAFT or QUOTE_CONFIRMED Sales Order, redeem any voucher atomically,
     * and evaluate Strict Manual Credit Review rules.
     */
    public function confirmSalesOrder(string $salesOrderId, string $actor): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId, $actor) {
            $order = SalesOrder::with('lines')
                ->where('sales_order_id', $salesOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = strtoupper((string) $order->status);
            if (!in_array($currentStatus, ['DRAFT', 'QUOTE_CONFIRMED'], true)) {
                throw new DomainException("Sales Order [{$order->order_number}] cannot be confirmed from status [{$order->status}].");
            }

            if ($order->lines->isEmpty()) {
                throw new DomainException("Sales Order [{$order->order_number}] must contain at least one line item.");
            }

            // If promoting from Unified Quote stage, assign official SLS-YYYY-XXXX Sales Order number
            if ($currentStatus === 'QUOTE_CONFIRMED' || strtoupper((string) $order->document_stage) === 'QUOTE') {
                if ($order->valid_until_date && Carbon::parse($order->valid_until_date)->lt(now()->startOfDay())) {
                    throw new DomainException("Quote [{$order->quote_number}] has expired. Clone a revision before converting to a Sales Order.");
                }
                if (empty($order->quote_number)) {
                    $order->quote_number = $order->order_number;
                }
                $order->order_number = $this->numberingService->generateNextNumber((string) ($order->company_id ?: '1'), 'SORD', 'SLS');
                $order->document_stage = 'ORDER';
                $order->order_date = now()->toDateString();
                $order->confirmed_at = $order->confirmed_at ?: now();
                $order->confirmed_by = $order->confirmed_by ?: $actor;
            }

            $customer = AcctCustomer::where('customer_id', $order->customer_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Atomic Voucher Redemption + Discount/Bundle Telemetry
            if (!empty($order->voucher_id)) {
                $this->promotionService->redeemVoucherForOrder($order, $actor);
            }
            $this->promotionService->logOrderPromoUsages($order, $actor);

            $eval = $this->creditService->evaluateCustomerCredit(
                $customer,
                (float) $order->gross_total,
                (string) $order->payment_terms_code,
                $order->sales_order_id
            );

            $order->credit_limit_snapshot = $eval['credit_limit'];
            $order->credit_exposure_snapshot = $eval['projected_exposure'];
            $order->credit_breach_amount = $eval['breach_amount'];
            $order->credit_review_flags_json = $eval['flags'];
            $order->updated_by = $actor;

            if ($eval['requires_review']) {
                $order->status = 'CREDIT_HOLD';
                $order->credit_review_status = 'PENDING_REVIEW';
                $order->save();

                $this->creditService->createCreditReviewTicket($order, $eval, $actor);
            } else {
                $order->status = 'APPROVED';
                $order->credit_review_status = 'AUTO_CLEARED';
                $order->credit_reviewed_by = 'SYSTEM (COD/PREPAID)';
                $order->credit_reviewed_at = now();
                $order->credit_review_remarks = 'Auto-cleared under Cash/COD/Advance terms.';
                $order->save();

                $this->inventoryService->reserveOrderStock($order);
            }

            return $order->fresh(['lines', 'customer', 'reservations']);
        });
    }

    /**
     * Create a new Sales Invoice or Post an existing auto-drafted Sales Invoice for a Sales Order.
     * Passes override_credit_limit=true when Accounting already approved the Sales Order credit review.
     */
    public function createOrPostBillForOrder(
        string $salesOrderId,
        ?string $draftInvoiceId = null,
        string $targetStatus = 'POSTED',
        array $options = [],
        string $actor = 'Billing Specialist'
    ): AcctSalesInvoice {
        $targetStatus = strtoupper(trim($targetStatus));

        return DB::transaction(function () use ($salesOrderId, $draftInvoiceId, $targetStatus, $options, $actor) {
            $order = SalesOrder::with('lines')
                ->where('sales_order_id', $salesOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array(strtoupper((string) $order->status), ['DRAFT', 'CREDIT_HOLD', 'CANCELLED'], true)) {
                throw new DomainException("Cannot bill Sales Order [{$order->order_number}] in status [{$order->status}].");
            }

            $companyId = (string) ($order->company_id ?: '1');
            $branchId = (string) ($order->branch_id ?: 'BR-01');
            $this->seedService->ensureSeeded($companyId);

            $isPreCleared = in_array(strtoupper((string) $order->credit_review_status), ['AUTO_CLEARED', 'APPROVED', 'APPROVED_OVERRIDE'], true);

            if ($draftInvoiceId) {
                $draftInv = AcctSalesInvoice::with('lines')
                    ->where('invoice_id', $draftInvoiceId)
                    ->where('sales_order_id', $order->sales_order_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (strtoupper((string) $draftInv->status) !== 'DRAFT') {
                    throw new DomainException("Invoice [{$draftInv->invoice_number}] is already [{$draftInv->status}].");
                }

                $linesPayload = $draftInv->lines->map(fn ($l) => [
                    'invoice_line_id' => $l->invoice_line_id,
                    'item_code' => $l->item_code,
                    'description' => $l->description,
                    'quantity' => (float) $l->quantity,
                    'uom' => $l->uom,
                    'unit_price' => (float) $l->unit_price,
                    'tax_code' => $l->tax_code,
                    'sales_order_line_id' => $l->sales_order_line_id,
                ])->all();

                $posted = $this->arService->saveDraftOrPostInvoice([
                    'invoice_id' => $draftInv->invoice_id,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'customer_id' => $draftInv->customer_id,
                    'invoice_date' => $options['invoice_date'] ?? $draftInv->invoice_date->toDateString(),
                    'due_date' => $options['due_date'] ?? $draftInv->due_date->toDateString(),
                    'payment_terms_code' => $draftInv->payment_terms_code,
                    'currency_code' => $draftInv->currency_code,
                    'exchange_rate' => (float) $draftInv->exchange_rate,
                    'discount_amount' => (float) $draftInv->discount_amount,
                    'sales_order_id' => $order->sales_order_id,
                    'sales_order_number' => $order->order_number,
                    'stock_out_order_ref_id' => $draftInv->stock_out_order_ref_id,
                    'stock_out_number' => $draftInv->stock_out_number,
                    'override_credit_limit' => $isPreCleared,
                    'notes' => $options['notes'] ?? $draftInv->notes,
                    'acted_by' => $actor,
                ], $linesPayload, $targetStatus);

                if ($targetStatus === 'POSTED') {
                    $this->syncLineInvoicedQuantities($order, $posted);
                    $this->arService->syncSalesOrderPaymentState($order->sales_order_id);
                }

                return $posted;
            }

            $billMode = strtolower((string) ($options['bill_mode'] ?? 'delivered'));
            $linesPayload = [];

            foreach ($order->lines as $soLine) {
                $eligibleQty = $billMode === 'full_order'
                    ? max(0.0, round((float) $soLine->qty_ordered - (float) $soLine->qty_invoiced, 4))
                    : max(0.0, round((float) $soLine->qty_delivered - (float) $soLine->qty_invoiced, 4));

                if ($eligibleQty <= 0.0001) {
                    continue;
                }

                $netUnitPrice = (float) $soLine->qty_ordered > 0
                    ? round((float) $soLine->line_subtotal / (float) $soLine->qty_ordered, 4)
                    : (float) $soLine->unit_price;

                $linesPayload[] = [
                    'sales_order_line_id' => $soLine->sales_order_line_id,
                    'item_code' => $soLine->sku,
                    'description' => ($soLine->sku ? "[{$soLine->sku}] " : '') . $soLine->item_name,
                    'quantity' => $eligibleQty,
                    'uom' => $soLine->uom ?: 'PCS',
                    'unit_price' => $netUnitPrice,
                    'tax_code' => $soLine->tax_code ?: 'VAT12_OUT',
                ];
            }

            if (empty($linesPayload)) {
                throw new DomainException("No unbilled quantities available for Sales Order [{$order->order_number}].");
            }

            $linesGrossSum = collect($linesPayload)->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
            $orderLinesGrossSum = (float) $order->lines->sum('line_subtotal');
            $totalHeaderDisc = (float) $order->discount_amount + (float) $order->voucher_discount_amount;
            $proRatedDisc = ($orderLinesGrossSum > 0.0001 && $totalHeaderDisc > 0.0001)
                ? round($totalHeaderDisc * min(1.0, $linesGrossSum / $orderLinesGrossSum), 4)
                : 0.0;

            $dueDays = $this->creditService->resolveDueDays($companyId, $order->payment_terms_code);

            $inv = $this->arService->saveDraftOrPostInvoice([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $order->customer_id,
                'invoice_date' => $options['invoice_date'] ?? now()->toDateString(),
                'due_date' => $options['due_date'] ?? now()->addDays($dueDays)->toDateString(),
                'payment_terms_code' => $order->payment_terms_code ?: 'NET_30',
                'currency_code' => $order->currency_code ?: 'PHP',
                'exchange_rate' => (float) ($order->exchange_rate ?: 1.0),
                'discount_amount' => $proRatedDisc,
                'sales_order_id' => $order->sales_order_id,
                'sales_order_number' => $order->order_number,
                'override_credit_limit' => $isPreCleared,
                'notes' => $options['notes'] ?? "Sales Invoice for Order {$order->order_number}",
                'acted_by' => $actor,
            ], $linesPayload, $targetStatus);

            if ($targetStatus === 'POSTED') {
                $this->syncLineInvoicedQuantities($order, $inv);
                $this->arService->syncSalesOrderPaymentState($order->sales_order_id);
            }

            return $inv;
        });
    }

    protected function syncLineInvoicedQuantities(SalesOrder $order, AcctSalesInvoice $inv): void
    {
        $inv->loadMissing('lines');
        $soLines = $order->lines->keyBy('sales_order_line_id');
        foreach ($inv->lines as $invLine) {
            if (!empty($invLine->sales_order_line_id) && $soLines->has($invLine->sales_order_line_id)) {
                $soLine = $soLines->get($invLine->sales_order_line_id);
                $soLine->qty_invoiced = round((float) $soLine->qty_invoiced + (float) $invLine->quantity, 4);
                $soLine->save();
            }
        }
    }

    public function cancelSalesOrder(string $salesOrderId, ?string $reason, string $actor): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId, $reason, $actor) {
            $order = SalesOrder::with('lines')->where('sales_order_id', $salesOrderId)->lockForUpdate()->firstOrFail();

            if ($order->lines->some(fn (SalesOrderLine $l) => (float) $l->qty_delivered > 0.0001)) {
                throw new DomainException("Sales Order [{$order->order_number}] has already been partially or fully delivered. Use a Customer Credit Memo / Return instead of cancelling.");
            }

            $postedInvoiceExists = AcctSalesInvoice::where('sales_order_id', $order->sales_order_id)
                ->whereIn('status', ['POSTED', 'PARTIAL', 'PAID'])
                ->exists();
            if ($postedInvoiceExists) {
                throw new DomainException("Sales Order [{$order->order_number}] has posted invoices and cannot be cancelled.");
            }

            $this->inventoryService->releaseOrderReservations($order);
            $this->promotionService->releaseVoucherForOrder($order, $actor);

            $order->status = 'CANCELLED';
            $order->cancellation_reason = $reason ?: 'Cancelled by user';
            $order->updated_by = $actor;
            $order->save();

            if (!empty($order->approval_request_id) && Schema::hasTable('approval_requests')) {
                DB::table('approval_requests')
                    ->where('approval_request_id', $order->approval_request_id)
                    ->where('approval_request_status', 'PENDING')
                    ->update([
                        'approval_request_status' => 'CANCELLED',
                        'approval_completed_at' => now(),
                        'approval_request_updated_at' => now(),
                    ]);
            }

            return $order->fresh(['lines', 'reservations']);
        });
    }
}

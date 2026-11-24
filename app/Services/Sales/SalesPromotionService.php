<?php

namespace App\Services\Sales;

use App\Models\Accounting\AcctCustomer;
use App\Models\Inventory\BillOfMaterials;
use App\Models\Sales\SalesBundle;
use App\Models\Sales\SalesDiscount;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\Sales\SalesPromoAuditLog;
use App\Models\Sales\SalesVoucher;
use App\Models\Sales\SalesVoucherRedemption;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesPromotionService
{
    public function __construct(
        protected SalesInventoryService $inventoryService
    ) {
    }

    /**
     * Evaluate a configured Discount Rule (Commercial or BIR Statutory Senior/PWD).
     */
    public function evaluateDiscountRule(
        string $companyId,
        string $discountId,
        AcctCustomer $customer,
        float $linesSubtotal
    ): array {
        $rule = SalesDiscount::where('company_id', $companyId)
            ->where('discount_id', $discountId)
            ->firstOrFail();

        if (!$rule->is_active) {
            throw new DomainException("Discount rule [{$rule->discount_code}] is inactive.");
        }

        $today = now()->startOfDay();
        if ($rule->valid_from && Carbon::parse($rule->valid_from)->gt($today)) {
            throw new DomainException("Discount rule [{$rule->discount_code}] is not yet active until {$rule->valid_from->toDateString()}.");
        }
        if ($rule->valid_until && Carbon::parse($rule->valid_until)->lt($today)) {
            throw new DomainException("Discount rule [{$rule->discount_code}] expired on {$rule->valid_until->toDateString()}.");
        }

        $targetTier = strtoupper((string) ($rule->applicable_customer_tier ?: 'ALL'));
        $custTier = strtoupper((string) ($customer->customer_tier ?: 'STANDARD'));
        if ($targetTier !== 'ALL' && $targetTier !== $custTier) {
            throw new DomainException("Discount [{$rule->discount_code}] is restricted to {$targetTier} tier customers (customer is {$custTier}).");
        }

        if ((float) $rule->min_order_amount > 0.0001 && $linesSubtotal < (float) $rule->min_order_amount) {
            throw new DomainException(sprintf(
                'Order subtotal ₱%s is below minimum spend ₱%s required for discount [%s].',
                number_format($linesSubtotal, 2),
                number_format((float) $rule->min_order_amount, 2),
                $rule->discount_code
            ));
        }

        $type = strtoupper((string) $rule->discount_type);
        $val = (float) $rule->discount_value;
        $discountAmt = $type === 'PERCENT'
            ? round($linesSubtotal * (min(100.0, max(0.0, $val)) / 100.0), 4)
            : min($linesSubtotal, max(0.0, round($val, 4)));

        if ($rule->max_discount_cap !== null && (float) $rule->max_discount_cap > 0.0001) {
            $discountAmt = min($discountAmt, (float) $rule->max_discount_cap);
        }

        return [
            'discount' => $rule,
            'discount_id' => $rule->discount_id,
            'discount_code' => $rule->discount_code,
            'discount_type' => $type,
            'discount_value' => $val,
            'discount_amount' => round($discountAmt, 4),
            'vat_exempt_override' => (bool) $rule->vat_exempt_override,
            'requires_manager_approval' => (bool) $rule->requires_manager_approval,
        ];
    }

    /**
     * Validate a Voucher Code against quotas, expiry, customer lock, and min spend.
     */
    public function validateVoucher(
        string $companyId,
        string $voucherCode,
        ?AcctCustomer $customer,
        float $orderSubtotal,
        ?string $excludeSalesOrderId = null
    ): array {
        $code = strtoupper(trim($voucherCode));
        $voucher = SalesVoucher::where('company_id', $companyId)
            ->where('voucher_code', $code)
            ->first();

        if (!$voucher) {
            throw new DomainException("Voucher code [{$code}] does not exist.");
        }

        if (strtoupper((string) $voucher->status) !== 'ACTIVE') {
            throw new DomainException("Voucher [{$code}] is {$voucher->status} and cannot be applied.");
        }

        $today = now()->startOfDay();
        if ($voucher->valid_from && Carbon::parse($voucher->valid_from)->gt($today)) {
            throw new DomainException("Voucher [{$code}] is not valid until {$voucher->valid_from->toDateString()}.");
        }
        if ($voucher->valid_until && Carbon::parse($voucher->valid_until)->lt($today)) {
            throw new DomainException("Voucher [{$code}] expired on {$voucher->valid_until->toDateString()}.");
        }

        if (!empty($voucher->customer_id) && (!$customer || $voucher->customer_id !== $customer->customer_id)) {
            throw new DomainException("Voucher [{$code}] is locked to a specific customer account.");
        }

        if ((float) $voucher->min_spend_amount > 0.0001 && $orderSubtotal < (float) $voucher->min_spend_amount) {
            throw new DomainException(sprintf(
                'Order subtotal ₱%s does not meet the ₱%s minimum spend for voucher [%s].',
                number_format($orderSubtotal, 2),
                number_format((float) $voucher->min_spend_amount, 2),
                $code
            ));
        }

        if ($voucher->max_total_uses !== null && (int) $voucher->current_uses_count >= (int) $voucher->max_total_uses) {
            throw new DomainException("Voucher [{$code}] has reached its maximum total redemptions ({$voucher->max_total_uses}).");
        }

        if ($customer && $voucher->max_uses_per_customer !== null) {
            $custUses = SalesVoucherRedemption::where('voucher_id', $voucher->voucher_id)
                ->where('customer_id', $customer->customer_id)
                ->where('status', 'REDEEMED')
                ->when($excludeSalesOrderId, fn ($q) => $q->where('sales_order_id', '!=', $excludeSalesOrderId))
                ->count();
            if ($custUses >= (int) $voucher->max_uses_per_customer) {
                throw new DomainException("Customer [{$customer->customer_name}] has already used voucher [{$code}] ({$custUses}/{$voucher->max_uses_per_customer}).");
            }
        }

        $type = strtoupper((string) $voucher->discount_type);
        $val = (float) $voucher->discount_value;
        $discAmt = $type === 'PERCENT'
            ? round($orderSubtotal * (min(100.0, max(0.0, $val)) / 100.0), 4)
            : min($orderSubtotal, max(0.0, round($val, 4)));

        if ($voucher->max_discount_amount !== null && (float) $voucher->max_discount_amount > 0.0001) {
            $discAmt = min($discAmt, (float) $voucher->max_discount_amount);
        }

        return [
            'voucher' => $voucher,
            'voucher_id' => $voucher->voucher_id,
            'voucher_code' => $voucher->voucher_code,
            'voucher_name' => $voucher->voucher_name,
            'discount_type' => $type,
            'discount_value' => $val,
            'discount_amount' => round($discAmt, 4),
        ];
    }

    /**
     * Atomically lock and redeem a voucher when a Sales Order is confirmed.
     */
    public function redeemVoucherForOrder(SalesOrder $order, string $actor): ?SalesVoucherRedemption
    {
        if (empty($order->voucher_id)) {
            return null;
        }

        $existing = SalesVoucherRedemption::where('sales_order_id', $order->sales_order_id)
            ->where('status', 'REDEEMED')
            ->first();
        if ($existing) {
            return $existing;
        }

        $voucher = SalesVoucher::where('voucher_id', $order->voucher_id)
            ->lockForUpdate()
            ->firstOrFail();

        $customer = AcctCustomer::where('customer_id', $order->customer_id)->first();
        $validated = $this->validateVoucher(
            (string) $order->company_id,
            (string) $voucher->voucher_code,
            $customer,
            max(0.0, (float) $order->subtotal_amount + (float) $order->discount_amount),
            $order->sales_order_id
        );

        $voucher->current_uses_count = (int) $voucher->current_uses_count + 1;
        if ($voucher->max_total_uses !== null && $voucher->current_uses_count >= (int) $voucher->max_total_uses) {
            $voucher->status = 'DEPLETED';
        }
        $voucher->save();

        $discountAmt = (float) ($order->voucher_discount_amount > 0 ? $order->voucher_discount_amount : $validated['discount_amount']);

        $redemption = SalesVoucherRedemption::create([
            'company_id' => $order->company_id ?: '1',
            'voucher_id' => $voucher->voucher_id,
            'sales_order_id' => $order->sales_order_id,
            'customer_id' => $order->customer_id,
            'discount_applied' => $discountAmt,
            'status' => 'REDEEMED',
            'redeemed_at' => now(),
        ]);

        $this->logAudit(
            (string) ($order->company_id ?: '1'),
            'VOUCHER',
            $voucher->voucher_id,
            $voucher->voucher_code,
            $voucher->voucher_name,
            'REDEEMED',
            sprintf('Redeemed on %s (%s) for ₱%s.', $order->order_number, $order->customer_name, number_format($discountAmt, 2)),
            $actor,
            ['sales_order_id' => $order->sales_order_id, 'order_number' => $order->order_number, 'discount_applied' => $discountAmt]
        );

        return $redemption;
    }

    /**
     * Release voucher redemption back to pool if a Sales Order is cancelled.
     */
    public function releaseVoucherForOrder(SalesOrder $order, string $actor = 'System'): void
    {
        if (empty($order->voucher_id) || !Schema::hasTable('sales_voucher_redemptions')) {
            return;
        }

        $redemptions = SalesVoucherRedemption::where('sales_order_id', $order->sales_order_id)
            ->where('status', 'REDEEMED')
            ->lockForUpdate()
            ->get();

        if ($redemptions->isEmpty()) {
            return;
        }

        $voucher = SalesVoucher::where('voucher_id', $order->voucher_id)
            ->lockForUpdate()
            ->first();

        foreach ($redemptions as $red) {
            $red->status = 'RELEASED';
            $red->released_at = now();
            $red->save();

            if ($voucher && $voucher->current_uses_count > 0) {
                $voucher->current_uses_count = (int) $voucher->current_uses_count - 1;
            }
        }

        if ($voucher) {
            if ($voucher->status === 'DEPLETED' && ($voucher->max_total_uses === null || $voucher->current_uses_count < (int) $voucher->max_total_uses)) {
                $voucher->status = 'ACTIVE';
            }
            $voucher->save();

            $this->logAudit(
                (string) ($order->company_id ?: '1'),
                'VOUCHER',
                $voucher->voucher_id,
                $voucher->voucher_code,
                $voucher->voucher_name,
                'RELEASED',
                sprintf('Quota released back to pool after cancelling %s (%s).', $order->order_number, $order->customer_name),
                $actor,
                ['sales_order_id' => $order->sales_order_id, 'order_number' => $order->order_number]
            );
        }
    }

    /**
     * Enrich Bundles with real-time Inventory component ATP, BOM Production availability,
     * regular retail value, cost total, and gross margin %.
     */
    public function getEnrichedBundles(string $companyId): Collection
    {
        if (!Schema::hasTable('sales_bundles')) {
            return collect();
        }

        $bundles = SalesBundle::with(['items.item'])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get();

        $allItemIds = $bundles->flatMap(fn (SalesBundle $b) => $b->items->pluck('inventory_item_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $atpMap = $this->inventoryService->getAtpMap($allItemIds);
        $bomItemIds = Schema::hasTable('bom_header') && !empty($allItemIds)
            ? BillOfMaterials::where('is_active', true)
                ->whereIn('finished_item_id', $allItemIds)
                ->pluck('bom_code', 'finished_item_id')
                ->toArray()
            : [];

        $bundleTelemetry = $this->getBundleModuleTelemetry($companyId);
        $bundleSummary = $bundleTelemetry['usage_summary'] ?? [];

        return $bundles->map(function (SalesBundle $bundle) use ($atpMap, $bomItemIds, $bundleSummary) {
            $componentCost = 0.0;
            $regularRetail = 0.0;
            $minBundlesAvailable = null;

            foreach ($bundle->items as $bi) {
                $inv = $bi->item;
                $qty = max(0.0001, (float) $bi->quantity);
                $cost = $inv ? (float) $inv->cost_price : 0.0;
                $sell = $inv ? (float) $inv->selling_price : 0.0;
                $atp = $inv ? (float) ($atpMap[$inv->id]['atp_qty'] ?? $inv->current_stock) : 0.0;

                $componentCost += ($qty * $cost);
                $regularRetail += ($qty * $sell);

                $possible = (int) floor(max(0.0, $atp) / $qty);
                $minBundlesAvailable = $minBundlesAvailable === null ? $possible : min($minBundlesAvailable, $possible);

                $bi->setAttribute('atp_qty', round($atp, 2));
                $bi->setAttribute('unit_cost', round($cost, 2));
                $bi->setAttribute('unit_retail', round($sell, 2));
                $bi->setAttribute('bom_code', $inv ? ($bomItemIds[$inv->id] ?? null) : null);
            }

            $price = (float) $bundle->bundle_price;
            $savings = max(0.0, round($regularRetail - $price, 2));
            $savingsPct = $regularRetail > 0.01 ? round(($savings / $regularRetail) * 100, 1) : 0.0;
            $marginPct = $price > 0.01 ? round((($price - $componentCost) / $price) * 100, 1) : 0.0;
            $dur = $this->computeDurationMeta($bundle->valid_from, $bundle->valid_until, $bundle->duration_mode ?? null);
            $usage = $bundleSummary[$bundle->bundle_id] ?? ['uses_count' => 0, 'total_revenue' => 0.0, 'last_used_at' => null];

            $bundle->setAttribute('component_cost_total', round($componentCost, 2));
            $bundle->setAttribute('regular_retail_total', round($regularRetail, 2));
            $bundle->setAttribute('savings_amount', $savings);
            $bundle->setAttribute('savings_pct', $savingsPct);
            $bundle->setAttribute('margin_pct', $marginPct);
            $bundle->setAttribute('bundle_atp', $minBundlesAvailable ?? 0);
            $bundle->setAttribute('duration_mode', $dur['duration_mode']);
            $bundle->setAttribute('duration_days', $dur['duration_days']);
            $bundle->setAttribute('schedule_status', $dur['schedule_status']);
            $bundle->setAttribute('duration_label', $dur['duration_label']);
            $bundle->setAttribute('uses_count', (int) ($usage['uses_count'] ?? 0));
            $bundle->setAttribute('total_revenue', round((float) ($usage['total_revenue'] ?? 0.0), 2));
            $bundle->setAttribute('last_used_at', $usage['last_used_at'] ?? null);

            return $bundle;
        })->values();
    }

    /**
     * Explode a Bundle Promotion into pro-rated component lines for a Sales Quote/Order
     * so each SKU reserves ATP, deducts via Stock-Out, and posts exact component COGS.
     */
    public function explodeBundleToOrderLines(string $companyId, string $bundleId, float $bundleQty = 1.0): array
    {
        $bundle = SalesBundle::with(['items.item'])
            ->where('company_id', $companyId)
            ->where('bundle_id', $bundleId)
            ->firstOrFail();

        if (!$bundle->is_active) {
            throw new DomainException("Bundle [{$bundle->bundle_code}] is inactive.");
        }

        $today = now()->startOfDay();
        if ($bundle->valid_from && Carbon::parse($bundle->valid_from)->gt($today)) {
            throw new DomainException("Bundle [{$bundle->bundle_code}] is not active until {$bundle->valid_from->toDateString()}.");
        }
        if ($bundle->valid_until && Carbon::parse($bundle->valid_until)->lt($today)) {
            throw new DomainException("Bundle [{$bundle->bundle_code}] expired on {$bundle->valid_until->toDateString()}.");
        }

        if ($bundle->items->isEmpty()) {
            throw new DomainException("Bundle [{$bundle->bundle_code}] has no component items.");
        }

        $bundleQty = max(0.01, $bundleQty);
        $targetTotal = round((float) $bundle->bundle_price * $bundleQty, 4);

        $weights = [];
        $weightSum = 0.0;
        foreach ($bundle->items as $bi) {
            $inv = $bi->item;
            $qty = max(0.0001, (float) $bi->quantity * $bundleQty);
            $retail = $inv && (float) $inv->selling_price > 0 ? (float) $inv->selling_price : 1.0;
            $w = $qty * $retail;
            $weights[] = $w;
            $weightSum += $w;
        }

        $exploded = [];
        $allocatedSum = 0.0;
        $count = $bundle->items->count();

        foreach ($bundle->items as $idx => $bi) {
            $inv = $bi->item;
            $qty = round((float) $bi->quantity * $bundleQty, 4);

            if ($idx === ($count - 1)) {
                $lineTargetSub = max(0.0, round($targetTotal - $allocatedSum, 4));
            } else {
                $share = $weightSum > 0.0001 ? ($weights[$idx] / $weightSum) : (1.0 / $count);
                $lineTargetSub = round($targetTotal * $share, 4);
                $allocatedSum += $lineTargetSub;
            }

            $proRatedUnitPrice = $qty > 0.0001 ? round($lineTargetSub / $qty, 4) : 0.0;

            $exploded[] = [
                'inventory_item_id' => $bi->inventory_item_id,
                'sku' => $bi->sku ?: $inv?->sku,
                'item_name' => $bi->item_name ?: ($inv?->name ?? 'Bundle Component'),
                'quantity' => $qty,
                'uom' => $bi->uom ?: ($inv?->uom ?? 'PCS'),
                'unit_cost_snapshot' => $inv ? (float) $inv->cost_price : 0.0,
                'unit_price' => $proRatedUnitPrice,
                'discount_pct' => 0.0,
                'tax_code' => $bundle->tax_code ?: 'VAT12_OUT',
                'bundle_id' => $bundle->bundle_id,
                'bundle_code' => $bundle->bundle_code,
                'bundle_name' => $bundle->bundle_name,
                'line_notes' => "Bundle: {$bundle->bundle_code} ({$bundle->bundle_name})",
            ];
        }

        return [
            'bundle' => $bundle,
            'lines' => $exploded,
            'bundle_target_subtotal' => $targetTotal,
        ];
    }

    /**
     * Compute normalized promo duration mode, inclusive day span, and live schedule status.
     */
    public function computeDurationMeta($validFrom, $validUntil, ?string $durationMode = null): array
    {
        $from = !empty($validFrom) ? Carbon::parse($validFrom)->startOfDay() : null;
        $until = !empty($validUntil) ? Carbon::parse($validUntil)->startOfDay() : null;
        $isScheduled = strtoupper((string) $durationMode) === 'SCHEDULED' || $from !== null || $until !== null;

        if (!$isScheduled) {
            return [
                'duration_mode' => 'ALWAYS',
                'duration_days' => null,
                'schedule_status' => 'ALWAYS_ACTIVE',
                'duration_label' => 'Always Active',
            ];
        }

        $days = ($from && $until) ? ((int) $from->diffInDays($until) + 1) : null;
        $today = now()->startOfDay();

        if ($from && $from->gt($today)) {
            $startsIn = (int) $today->diffInDays($from);
            $label = ($days ? "{$days}d • " : '') . ($startsIn === 1 ? 'Starts tomorrow' : "Starts in {$startsIn}d");
            return [
                'duration_mode' => 'SCHEDULED',
                'duration_days' => $days,
                'schedule_status' => 'SCHEDULED',
                'duration_label' => $label,
            ];
        }

        if ($until && $until->lt($today)) {
            $ago = (int) $until->diffInDays($today);
            $label = ($days ? "{$days}d • " : '') . "Expired {$ago}d ago";
            return [
                'duration_mode' => 'SCHEDULED',
                'duration_days' => $days,
                'schedule_status' => 'EXPIRED',
                'duration_label' => $label,
            ];
        }

        if ($until) {
            $rem = (int) $today->diffInDays($until);
            $endStr = $rem === 0 ? 'Ends today' : "Ends in {$rem}d";
            $label = ($days ? "{$days}d • " : '') . $endStr;
            return [
                'duration_mode' => 'SCHEDULED',
                'duration_days' => $days,
                'schedule_status' => 'ACTIVE_NOW',
                'duration_label' => $label,
            ];
        }

        return [
            'duration_mode' => 'SCHEDULED',
            'duration_days' => $days,
            'schedule_status' => 'ACTIVE_NOW',
            'duration_label' => $from ? 'Active since ' . $from->format('M j, Y') : 'Scheduled',
        ];
    }

    /**
     * Record an audit event for a Discount Rule, Voucher, or Bundle Promo.
     */
    public function logAudit(
        string $companyId,
        string $module,
        string $promoId,
        string $promoCode,
        string $promoName,
        string $action,
        string $summary,
        string $actedBy,
        ?array $changes = null
    ): void {
        if (!Schema::hasTable('sales_promo_audit_logs')) {
            return;
        }

        SalesPromoAuditLog::create([
            'company_id' => $companyId,
            'promo_module' => strtoupper(trim($module)),
            'promo_id' => $promoId,
            'promo_code' => strtoupper(trim($promoCode)),
            'promo_name' => trim($promoName),
            'action' => strtoupper(trim($action)),
            'summary' => $summary,
            'changes_json' => $changes,
            'acted_by' => $actedBy ?: 'System',
            'acted_at' => now(),
        ]);
    }

    /**
     * Log discount rule & bundle usages when a Sales Order is confirmed.
     */
    public function logOrderPromoUsages(SalesOrder $order, string $actor): void
    {
        $companyId = (string) ($order->company_id ?: '1');

        if (!empty($order->discount_id)) {
            $disc = SalesDiscount::where('discount_id', $order->discount_id)->first();
            $this->logAudit(
                $companyId,
                'DISCOUNT',
                (string) $order->discount_id,
                (string) ($order->discount_code ?: ($disc?->discount_code ?? 'DISC')),
                (string) ($disc?->discount_name ?? $order->discount_code ?? 'Discount Rule'),
                'APPLIED_TO_ORDER',
                sprintf(
                    'Applied on %s (%s) — Discount ₱%s.',
                    $order->order_number,
                    $order->customer_name,
                    number_format((float) $order->discount_amount, 2)
                ),
                $actor,
                ['sales_order_id' => $order->sales_order_id, 'order_number' => $order->order_number, 'discount_amount' => (float) $order->discount_amount]
            );
        }

        $order->loadMissing('lines');
        $bundleGroups = $order->lines->filter(fn (SalesOrderLine $l) => !empty($l->bundle_id))->groupBy('bundle_id');
        foreach ($bundleGroups as $bundleId => $lines) {
            $first = $lines->first();
            $rev = round((float) $lines->sum('line_subtotal'), 2);
            $this->logAudit(
                $companyId,
                'BUNDLE',
                (string) $bundleId,
                (string) ($first->bundle_code ?: 'BUNDLE'),
                (string) ($first->bundle_name ?: 'Bundle Promo'),
                'APPLIED_TO_ORDER',
                sprintf(
                    'Sold on %s (%s) — %d component line(s), ₱%s.',
                    $order->order_number,
                    $order->customer_name,
                    $lines->count(),
                    number_format($rev, 2)
                ),
                $actor,
                ['sales_order_id' => $order->sales_order_id, 'order_number' => $order->order_number, 'revenue' => $rev]
            );
        }
    }

    /**
     * Attach duration & live usage metrics to Discount collections.
     */
    public function enrichDiscounts(Collection $discounts, array $telemetry): Collection
    {
        $summary = $telemetry['usage_summary'] ?? [];
        return $discounts->map(function (SalesDiscount $d) use ($summary) {
            $dur = $this->computeDurationMeta($d->valid_from, $d->valid_until, $d->duration_mode ?? null);
            $usage = $summary[$d->discount_id] ?? ['uses_count' => 0, 'total_discount_given' => 0.0, 'total_order_value' => 0.0, 'last_used_at' => null];
            $d->setAttribute('duration_mode', $dur['duration_mode']);
            $d->setAttribute('duration_days', $dur['duration_days']);
            $d->setAttribute('schedule_status', $dur['schedule_status']);
            $d->setAttribute('duration_label', $dur['duration_label']);
            $d->setAttribute('uses_count', (int) ($usage['uses_count'] ?? 0));
            $d->setAttribute('total_discount_given', round((float) ($usage['total_discount_given'] ?? 0.0), 2));
            $d->setAttribute('total_order_value', round((float) ($usage['total_order_value'] ?? 0.0), 2));
            $d->setAttribute('last_used_at', $usage['last_used_at'] ?? null);
            return $d;
        })->values();
    }

    /**
     * Attach duration & live usage metrics to Voucher collections.
     */
    public function enrichVouchers(Collection $vouchers, array $telemetry): Collection
    {
        $summary = $telemetry['usage_summary'] ?? [];
        return $vouchers->map(function (SalesVoucher $v) use ($summary) {
            $dur = $this->computeDurationMeta($v->valid_from, $v->valid_until, $v->duration_mode ?? null);
            $usage = $summary[$v->voucher_id] ?? ['uses_count' => (int) $v->current_uses_count, 'total_discount_given' => 0.0, 'last_used_at' => null];
            $v->setAttribute('duration_mode', $dur['duration_mode']);
            $v->setAttribute('duration_days', $dur['duration_days']);
            $v->setAttribute('schedule_status', $dur['schedule_status']);
            $v->setAttribute('duration_label', $dur['duration_label']);
            $v->setAttribute('uses_count', (int) ($usage['uses_count'] ?? $v->current_uses_count ?? 0));
            $v->setAttribute('total_discount_given', round((float) ($usage['total_discount_given'] ?? 0.0), 2));
            $v->setAttribute('last_used_at', $usage['last_used_at'] ?? null);
            return $v;
        })->values();
    }

    /**
     * Build per-module Usage & Audit Trail telemetry for Discount Rules.
     */
    public function getDiscountModuleTelemetry(string $companyId): array
    {
        $orders = Schema::hasTable('sales_orders')
            ? SalesOrder::where('company_id', $companyId)
                ->whereNotNull('discount_id')
                ->whereNotIn('status', ['DRAFT', 'QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'QUOTE_EXPIRED', 'CANCELLED', 'ARCHIVED'])
                ->orderByDesc('created_at')
                ->get(['sales_order_id', 'order_number', 'discount_id', 'discount_code', 'customer_id', 'customer_name', 'discount_amount', 'gross_total', 'status', 'created_by', 'created_at'])
            : collect();

        $usageSummary = [];
        foreach ($orders->groupBy('discount_id') as $discId => $rows) {
            $usageSummary[(string) $discId] = [
                'uses_count' => $rows->count(),
                'total_discount_given' => round((float) $rows->sum('discount_amount'), 2),
                'total_order_value' => round((float) $rows->sum('gross_total'), 2),
                'last_used_at' => optional($rows->first()->created_at)->toDateTimeString(),
            ];
        }

        $usageLogs = $orders->map(fn (SalesOrder $o) => [
            'sales_order_id' => $o->sales_order_id,
            'order_number' => $o->order_number,
            'promo_id' => $o->discount_id,
            'promo_code' => $o->discount_code ?: 'DISC',
            'customer_name' => $o->customer_name,
            'discount_amount' => round((float) $o->discount_amount, 2),
            'gross_total' => round((float) $o->gross_total, 2),
            'status' => $o->status,
            'used_at' => optional($o->created_at)->toDateTimeString(),
            'acted_by' => $o->created_by,
        ])->values()->all();

        $auditLogs = Schema::hasTable('sales_promo_audit_logs')
            ? SalesPromoAuditLog::where('company_id', $companyId)
                ->where('promo_module', 'DISCOUNT')
                ->orderByDesc('acted_at')
                ->limit(200)
                ->get()
                ->values()
                ->all()
            : [];

        return [
            'usage_summary' => $usageSummary,
            'usage_logs' => $usageLogs,
            'audit_logs' => $auditLogs,
        ];
    }

    /**
     * Build per-module Usage & Audit Trail telemetry for Promo Vouchers.
     */
    public function getVoucherModuleTelemetry(string $companyId): array
    {
        $redemptions = Schema::hasTable('sales_voucher_redemptions')
            ? DB::table('sales_voucher_redemptions as r')
                ->leftJoin('sales_vouchers as v', 'v.voucher_id', '=', 'r.voucher_id')
                ->leftJoin('sales_orders as o', 'o.sales_order_id', '=', 'r.sales_order_id')
                ->leftJoin('acct_customers as c', 'c.customer_id', '=', 'r.customer_id')
                ->where('r.company_id', $companyId)
                ->orderByDesc('r.redeemed_at')
                ->get([
                    'r.redemption_id',
                    'r.voucher_id',
                    'r.sales_order_id',
                    'r.customer_id',
                    'r.discount_applied',
                    'r.status',
                    'r.redeemed_at',
                    'r.released_at',
                    'v.voucher_code',
                    'v.voucher_name',
                    'o.order_number',
                    'c.customer_name',
                ])
            : collect();

        $usageSummary = [];
        foreach ($redemptions->where('status', 'REDEEMED')->groupBy('voucher_id') as $vId => $rows) {
            $usageSummary[(string) $vId] = [
                'uses_count' => $rows->count(),
                'total_discount_given' => round((float) $rows->sum('discount_applied'), 2),
                'last_used_at' => $rows->first()->redeemed_at ? Carbon::parse($rows->first()->redeemed_at)->toDateTimeString() : null,
            ];
        }

        $usageLogs = $redemptions->map(fn ($r) => [
            'redemption_id' => $r->redemption_id,
            'sales_order_id' => $r->sales_order_id,
            'order_number' => $r->order_number ?: '—',
            'promo_id' => $r->voucher_id,
            'promo_code' => $r->voucher_code ?: 'VOUCHER',
            'promo_name' => $r->voucher_name ?: '',
            'customer_name' => $r->customer_name ?: '—',
            'discount_amount' => round((float) $r->discount_applied, 2),
            'status' => $r->status,
            'used_at' => $r->redeemed_at ? Carbon::parse($r->redeemed_at)->toDateTimeString() : null,
            'released_at' => $r->released_at ? Carbon::parse($r->released_at)->toDateTimeString() : null,
        ])->values()->all();

        $auditLogs = Schema::hasTable('sales_promo_audit_logs')
            ? SalesPromoAuditLog::where('company_id', $companyId)
                ->where('promo_module', 'VOUCHER')
                ->orderByDesc('acted_at')
                ->limit(200)
                ->get()
                ->values()
                ->all()
            : [];

        return [
            'usage_summary' => $usageSummary,
            'usage_logs' => $usageLogs,
            'audit_logs' => $auditLogs,
        ];
    }

    /**
     * Build per-module Usage & Audit Trail telemetry for Bundle Promotions.
     */
    public function getBundleModuleTelemetry(string $companyId): array
    {
        $rows = Schema::hasTable('sales_order_lines') && Schema::hasTable('sales_orders')
            ? DB::table('sales_order_lines as l')
                ->join('sales_orders as o', 'o.sales_order_id', '=', 'l.sales_order_id')
                ->where('o.company_id', $companyId)
                ->whereNotNull('l.bundle_id')
                ->whereNotIn('o.status', ['DRAFT', 'QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'QUOTE_EXPIRED', 'CANCELLED', 'ARCHIVED'])
                ->orderByDesc('o.created_at')
                ->get([
                    'l.sales_order_id',
                    'l.bundle_id',
                    'l.bundle_code',
                    'l.bundle_name',
                    'l.line_subtotal',
                    'o.order_number',
                    'o.customer_name',
                    'o.status',
                    'o.created_by',
                    'o.created_at',
                ])
            : collect();

        $groupedByOrderBundle = $rows->groupBy(fn ($r) => $r->sales_order_id . ':' . $r->bundle_id);
        $usageLogs = $groupedByOrderBundle->map(function ($grp) {
            $first = $grp->first();
            return [
                'sales_order_id' => $first->sales_order_id,
                'order_number' => $first->order_number,
                'promo_id' => $first->bundle_id,
                'promo_code' => $first->bundle_code ?: 'BUNDLE',
                'promo_name' => $first->bundle_name ?: '',
                'customer_name' => $first->customer_name,
                'component_count' => $grp->count(),
                'revenue_amount' => round((float) $grp->sum('line_subtotal'), 2),
                'status' => $first->status,
                'used_at' => $first->created_at ? Carbon::parse($first->created_at)->toDateTimeString() : null,
                'acted_by' => $first->created_by,
            ];
        })->values();

        $usageSummary = [];
        foreach ($usageLogs->groupBy('promo_id') as $bundleId => $items) {
            $usageSummary[(string) $bundleId] = [
                'uses_count' => $items->count(),
                'total_revenue' => round((float) $items->sum('revenue_amount'), 2),
                'last_used_at' => $items->first()['used_at'] ?? null,
            ];
        }

        $auditLogs = Schema::hasTable('sales_promo_audit_logs')
            ? SalesPromoAuditLog::where('company_id', $companyId)
                ->where('promo_module', 'BUNDLE')
                ->orderByDesc('acted_at')
                ->limit(200)
                ->get()
                ->values()
                ->all()
            : [];

        return [
            'usage_summary' => $usageSummary,
            'usage_logs' => $usageLogs->all(),
            'audit_logs' => $auditLogs,
        ];
    }
}

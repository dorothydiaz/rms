<?php

namespace App\Services\Sales;

use App\Models\Accounting\AcctTaxCode;
use App\Models\Inventory\InventoryItem;
use App\Services\Accounting\AccountingSeedService;
use InvalidArgumentException;

class SalesPricingService
{
    public function __construct(
        protected AccountingSeedService $seedService
    ) {
    }

    /**
     * Compute normalized line totals, Output VAT, header discounts, cost snapshots,
     * gross margin, and below-cost alerts for a Sales Quote or Sales Order.
     */
    public function calculateDocument(
        string $companyId,
        array $rawLines,
        string $discountType = 'NONE',
        float $discountValue = 0.0
    ): array {
        if (empty($rawLines)) {
            throw new InvalidArgumentException('Document must contain at least one line item.');
        }

        $this->seedService->ensureSeeded($companyId);

        $taxCodes = AcctTaxCode::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($t) => strtoupper((string) $t->tax_code));

        $itemIds = collect($rawLines)->pluck('inventory_item_id')->filter()->unique()->values()->all();
        $skus = collect($rawLines)->pluck('sku')->filter()->unique()->values()->all();

        $itemsById = !empty($itemIds)
            ? InventoryItem::whereIn('id', $itemIds)->get()->keyBy('id')
            : collect();
        $itemsBySku = !empty($skus)
            ? InventoryItem::whereIn('sku', $skus)->get()->keyBy('sku')
            : collect();

        $normalizedLines = [];
        $belowCostItems = [];
        $linesSubtotalSum = 0.0;
        $estimatedCostTotal = 0.0;
        $lineNo = 1;

        foreach ($rawLines as $raw) {
            $invItem = !empty($raw['inventory_item_id'])
                ? ($itemsById->get((int) $raw['inventory_item_id']) ?? $itemsBySku->get($raw['sku'] ?? ''))
                : (!empty($raw['sku']) ? $itemsBySku->get($raw['sku']) : null);

            $qty = max(0.0001, (float) ($raw['quantity'] ?? ($raw['qty_ordered'] ?? 1.0)));
            $unitPrice = max(0.0, (float) ($raw['unit_price'] ?? ($invItem ? $invItem->selling_price : 0.0)));
            $unitCost = isset($raw['unit_cost_snapshot'])
                ? max(0.0, (float) $raw['unit_cost_snapshot'])
                : ($invItem ? (float) $invItem->cost_price : 0.0);

            $discPct = max(0.0, min(100.0, (float) ($raw['discount_pct'] ?? 0.0)));
            $grossLine = round($qty * $unitPrice, 4);
            $lineDiscountAmt = isset($raw['discount_amount']) && (float) $raw['discount_amount'] > 0 && $discPct <= 0
                ? min($grossLine, round((float) $raw['discount_amount'], 4))
                : round($grossLine * ($discPct / 100.0), 4);

            $netLineSubtotal = max(0.0, round($grossLine - $lineDiscountAmt, 4));
            $netUnitPrice = $qty > 0 ? round($netLineSubtotal / $qty, 4) : $unitPrice;

            $taxCodeStr = strtoupper(trim((string) ($raw['tax_code'] ?? 'VAT12_OUT')));
            if ($taxCodeStr === 'VAT_12') {
                $taxCodeStr = 'VAT12_OUT';
            }
            $taxObj = $taxCodes->get($taxCodeStr);
            $taxType = strtoupper((string) ($taxObj?->tax_type ?? ''));
            $isZeroOrExempt = in_array($taxCodeStr, ['EXEMPT', 'ZERO_RATED', 'VAT_EXEMPT', 'VAT_ZERO'], true)
                || in_array($taxType, ['EXEMPT', 'ZERO_RATED'], true);

            $taxRate = $isZeroOrExempt
                ? 0.0
                : ($taxObj ? (float) $taxObj->rate_pct : 12.0);
            $taxLabel = $taxObj?->tax_name ?: $taxCodeStr;

            $linesSubtotalSum += $netLineSubtotal;
            $estimatedCostTotal += round($qty * $unitCost, 4);

            if ($unitCost > 0.0001 && $netUnitPrice < $unitCost) {
                $belowCostItems[] = [
                    'line_number' => $lineNo,
                    'sku' => $raw['sku'] ?? ($invItem?->sku),
                    'item_name' => $raw['item_name'] ?? ($invItem?->name ?? 'Item'),
                    'net_unit_price' => $netUnitPrice,
                    'unit_cost' => $unitCost,
                ];
            }

            $normalizedLines[] = [
                'line_number' => $lineNo++,
                'quote_line_id' => $raw['quote_line_id'] ?? null,
                'inventory_item_id' => $invItem?->id ?? ($raw['inventory_item_id'] ?? null),
                'sku' => $raw['sku'] ?? ($invItem?->sku ?? null),
                'item_name' => $raw['item_name'] ?? ($invItem?->name ?? 'Sales Item'),
                'description' => $raw['description'] ?? ($invItem?->description ?? null),
                'quantity' => $qty,
                'uom' => $raw['uom'] ?? ($invItem?->uom ?? 'PCS'),
                'uom_conversion_factor' => max(0.000001, (float) ($raw['uom_conversion_factor'] ?? 1.0)),
                'unit_cost_snapshot' => $unitCost,
                'unit_price' => $unitPrice,
                'discount_pct' => $discPct,
                'discount_amount' => $lineDiscountAmt,
                'tax_code' => $taxCodeStr,
                'tax_name' => $taxLabel,
                'tax_rate_pct' => $taxRate,
                'line_subtotal' => $netLineSubtotal,
                'tax_amount' => 0.0,
                'line_total' => 0.0,
                'line_notes' => $raw['line_notes'] ?? null,
            ];
        }

        $discType = strtoupper(trim($discountType ?: 'NONE'));
        $discVal = max(0.0, $discountValue);
        $headerDiscountAmt = 0.0;
        if ($discType === 'PERCENT') {
            $discVal = min(100.0, $discVal);
            $headerDiscountAmt = round($linesSubtotalSum * ($discVal / 100.0), 4);
        } elseif ($discType === 'FIXED') {
            $headerDiscountAmt = min($linesSubtotalSum, round($discVal, 4));
        }

        $netSubtotalAfterHeaderDisc = max(0.0, round($linesSubtotalSum - $headerDiscountAmt, 4));
        $discountFactor = $linesSubtotalSum > 0.0001
            ? ($netSubtotalAfterHeaderDisc / $linesSubtotalSum)
            : 1.0;

        $taxTotal = 0.0;
        $taxBreakdownMap = [];
        foreach ($normalizedLines as &$line) {
            $taxableLineBase = round($line['line_subtotal'] * $discountFactor, 4);
            $lineTax = $line['tax_rate_pct'] > 0
                ? round($taxableLineBase * ($line['tax_rate_pct'] / 100.0), 4)
                : 0.0;
            $line['tax_amount'] = $lineTax;
            $line['line_total'] = round($line['line_subtotal'] + $lineTax, 4);
            $taxTotal += $lineTax;

            $tKey = $line['tax_code'];
            if (!isset($taxBreakdownMap[$tKey])) {
                $taxBreakdownMap[$tKey] = [
                    'tax_code' => $tKey,
                    'tax_name' => $line['tax_name'],
                    'rate_pct' => $line['tax_rate_pct'],
                    'taxable_base' => 0.0,
                    'tax_amount' => 0.0,
                ];
            }
            $taxBreakdownMap[$tKey]['taxable_base'] = round($taxBreakdownMap[$tKey]['taxable_base'] + $taxableLineBase, 4);
            $taxBreakdownMap[$tKey]['tax_amount'] = round($taxBreakdownMap[$tKey]['tax_amount'] + $lineTax, 4);
            unset($line['tax_name']);
        }
        unset($line);

        $taxTotal = round($taxTotal, 4);
        $grossTotal = round($netSubtotalAfterHeaderDisc + $taxTotal, 4);
        $estimatedCostTotal = round($estimatedCostTotal, 4);

        $marginPct = $netSubtotalAfterHeaderDisc > 0.0001
            ? round((($netSubtotalAfterHeaderDisc - $estimatedCostTotal) / $netSubtotalAfterHeaderDisc) * 100.0, 2)
            : 0.0;

        return [
            'lines' => $normalizedLines,
            'lines_gross_subtotal' => round($linesSubtotalSum, 4),
            'subtotal_amount' => $netSubtotalAfterHeaderDisc,
            'discount_type' => $discType,
            'discount_value' => $discVal,
            'discount_amount' => $headerDiscountAmt,
            'tax_amount' => $taxTotal,
            'tax_breakdown' => array_values($taxBreakdownMap),
            'gross_total' => $grossTotal,
            'estimated_cost_total' => $estimatedCostTotal,
            'estimated_margin_pct' => $marginPct,
            'below_cost_items' => $belowCostItems,
        ];
    }
}

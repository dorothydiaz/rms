<?php

namespace App\Services;

use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RfqAlternativeService
{
    /**
     * Create sibling Draft RFQs for selected alternative vendors.
     */
    public function createAlternatives(string $baseRfqNumber, array $vendorIds, ?string $userId = null): Collection
    {
        return DB::transaction(function () use ($baseRfqNumber, $vendorIds, $userId) {
            $baseRfq = RfqHeader::with('lines')->where('rfq_number', $baseRfqNumber)->firstOrFail();

            if ($baseRfq->lines->isEmpty()) {
                throw new InvalidArgumentException("Cannot create alternatives for an RFQ with no line items.");
            }

            // Ensure base RFQ has an rfq_group_id
            if (empty($baseRfq->rfq_group_id)) {
                $baseRfq->rfq_group_id = (string) Str::uuid();
                $baseRfq->alternative_label = 'A';
                $baseRfq->save();
            }

            // Determine existing vendors in the group to prevent duplicates
            $existingVendorIds = RfqHeader::where('rfq_group_id', $baseRfq->rfq_group_id)
                ->pluck('vendor_id')
                ->filter()
                ->map(fn($id) => (string)$id)
                ->toArray();

            // Next alternative label letter
            $existingCount = RfqHeader::where('rfq_group_id', $baseRfq->rfq_group_id)->count();
            $createdRfqs = collect();

            foreach ($vendorIds as $vendorId) {
                $strVendorId = (string) $vendorId;
                if (in_array($strVendorId, $existingVendorIds, true)) {
                    continue; // Skip duplicate vendor in the same bidding group
                }

                $label = chr(65 + $existingCount); // 65 = 'A', 66 = 'B', 67 = 'C', etc.
                $existingCount++;

                $siblingRfqNumber = $this->generateAlternativeRfqNumber($baseRfq->rfq_number, $label);
                
                // Fetch vendor details if available
                $vendor = ProcurementVendor::find($vendorId);
                if (!$vendor) {
                    $vendor = ProcurementVendor::where('vendor_code', $vendorId)->first();
                }

                $sibling = new RfqHeader();
                $sibling->rfq_id = (string) Str::uuid();
                $sibling->rfq_number = $siblingRfqNumber;
                $sibling->rfq_group_id = $baseRfq->rfq_group_id;
                $sibling->parent_rfq_id = $baseRfq->rfq_id;
                $sibling->is_alternative = true;
                $sibling->alternative_label = $label;
                $sibling->company_id = $baseRfq->company_id;
                $sibling->branch_id = $baseRfq->branch_id;
                $sibling->ship_to_warehouse_id = $baseRfq->ship_to_warehouse_id;
                $sibling->vendor_id = $vendor ? $vendor->id : $vendorId;
                $sibling->rfq_date = now()->toDateString();
                $sibling->submission_deadline = $baseRfq->submission_deadline;
                $sibling->quote_valid_until_date = $baseRfq->quote_valid_until_date;
                $sibling->rfq_requested_delivery_date = $baseRfq->rfq_requested_delivery_date;
                $sibling->rfq_currency_code = $baseRfq->rfq_currency_code ?? 'PHP';
                $sibling->rfq_payment_terms_code = $vendor->payment_terms ?? $baseRfq->rfq_payment_terms_code ?? 'NET30';
                $sibling->rfq_incoterms_code = $baseRfq->rfq_incoterms_code ?? 'FOB';
                $sibling->rfq_estimated_total_amount = $baseRfq->rfq_estimated_total_amount;
                $sibling->rfq_quoted_total_amount = 0.0000;
                $sibling->rfq_status = 'DRAFT';
                $sibling->rfq_approval_status = 'PENDING';
                $sibling->rfq_notes = "Alternative tender package linked to {$baseRfq->rfq_number}";
                $sibling->rfq_created_by = $userId;
                $sibling->save();

                // Clone line items (strictly with blank quoted values)
                foreach ($baseRfq->lines as $baseLine) {
                    RfqLine::create([
                        'rfq_line_id' => (string) Str::uuid(),
                        'rfq_id' => $sibling->rfq_id,
                        'line_number' => $baseLine->line_number,
                        'item_id' => $baseLine->item_id,
                        'requested_quantity' => $baseLine->requested_quantity,
                        'quoted_quantity' => null,
                        'purchase_uom_code' => $baseLine->purchase_uom_code,
                        'target_unit_price' => $baseLine->target_unit_price,
                        'quoted_unit_price' => 0.0000,
                        'quoted_line_total' => 0.0000,
                        'lead_time_days' => 0,
                        'is_awarded' => false,
                        'rfq_line_notes' => $baseLine->rfq_line_notes,
                        'rfq_line_created_by' => $userId,
                    ]);
                }

                $existingVendorIds[] = $strVendorId;
                $createdRfqs->push($sibling->load('lines', 'vendor'));
            }

            return $createdRfqs;
        });
    }

    /**
     * Compute the full side-by-side comparison matrix for an RFQ bidding group.
     */
    public function getComparisonMatrix(string $groupId): array
    {
        $rfqs = RfqHeader::with(['lines.item', 'vendor'])
            ->where('rfq_group_id', $groupId)
            ->orderBy('alternative_label')
            ->get();

        if ($rfqs->isEmpty()) {
            throw new InvalidArgumentException("No RFQ group found with ID {$groupId}");
        }

        // Aggregate unique item lines across the group
        $itemsMap = [];
        foreach ($rfqs as $rfq) {
            foreach ($rfq->lines as $line) {
                if (!isset($itemsMap[$line->item_id])) {
                    $itemsMap[$line->item_id] = [
                        'item_id' => $line->item_id,
                        'item_name' => $line->item ? $line->item->name : $line->item_id,
                        'purchase_uom_code' => $line->purchase_uom_code,
                        'requested_quantity' => (float) $line->requested_quantity,
                        'target_unit_price' => (float) $line->target_unit_price,
                        'budget_total' => (float) ($line->requested_quantity * $line->target_unit_price),
                        'quotes' => [],
                    ];
                }
                $quotedPrice = (float) $line->quoted_unit_price;
                $itemsMap[$line->item_id]['quotes'][$rfq->rfq_id] = [
                    'rfq_id' => $rfq->rfq_id,
                    'rfq_number' => $rfq->rfq_number,
                    'vendor_id' => $rfq->vendor_id,
                    'vendor_name' => $rfq->vendor ? ($rfq->vendor->trade_name ?? $rfq->vendor->legal_name) : 'Vendor',
                    'quoted_quantity' => (float) ($line->quoted_quantity ?: $line->requested_quantity),
                    'quoted_unit_price' => $quotedPrice,
                    'quoted_line_total' => (float) $line->quoted_line_total,
                    'lead_time_days' => (int) $line->lead_time_days,
                    'has_quote' => ($quotedPrice > 0),
                    'is_awarded' => (bool) $line->is_awarded,
                    'rfq_status' => $rfq->rfq_status,
                ];
            }
        }

        // Compute Lowest Price and Fastest Lead Time per item
        $totalBudgetSum = 0.0;
        $lowestCombinedSum = 0.0;

        foreach ($itemsMap as $itemId => &$itemData) {
            $totalBudgetSum += $itemData['budget_total'];

            $validQuotes = array_filter($itemData['quotes'], fn($q) => $q['has_quote'] && $q['quoted_unit_price'] > 0);
            
            if (!empty($validQuotes)) {
                $minPrice = min(array_column($validQuotes, 'quoted_unit_price'));
                $validLeadTimes = array_filter(array_column($validQuotes, 'lead_time_days'), fn($lt) => $lt > 0);
                $minLeadTime = !empty($validLeadTimes) ? min($validLeadTimes) : null;

                $lowestCombinedSum += ($minPrice * $itemData['requested_quantity']);

                foreach ($itemData['quotes'] as $rfqId => &$quote) {
                    $quote['is_lowest_price'] = ($quote['has_quote'] && $quote['quoted_unit_price'] === $minPrice);
                    $quote['is_fastest_delivery'] = ($quote['has_quote'] && $minLeadTime !== null && $quote['lead_time_days'] === $minLeadTime);
                    $quote['price_variance_vs_target'] = $itemData['target_unit_price'] > 0 
                        ? round((($quote['quoted_unit_price'] - $itemData['target_unit_price']) / $itemData['target_unit_price']) * 100, 2)
                        : 0;
                }
            } else {
                $lowestCombinedSum += $itemData['budget_total'];
            }
        }

        // Summary metrics per vendor
        $vendorSummaries = [];
        foreach ($rfqs as $rfq) {
            $vId = $rfq->vendor_id;
            $vendorName = $rfq->vendor ? ($rfq->vendor->trade_name ?? $rfq->vendor->legal_name) : 'Vendor';
            $quoteSum = 0.0;
            $quoteCount = 0;
            $leadTimes = [];
            $lowestPriceCount = 0;

            foreach ($itemsMap as $item) {
                if (isset($item['quotes'][$rfq->rfq_id])) {
                    $q = $item['quotes'][$rfq->rfq_id];
                    if ($q['has_quote']) {
                        $quoteSum += $q['quoted_line_total'];
                        $quoteCount++;
                        if ($q['lead_time_days'] > 0) {
                            $leadTimes[] = $q['lead_time_days'];
                        }
                        if (!empty($q['is_lowest_price'])) {
                            $lowestPriceCount++;
                        }
                    }
                }
            }

            $vendorSummaries[$rfq->rfq_id] = [
                'rfq_id' => $rfq->rfq_id,
                'rfq_number' => $rfq->rfq_number,
                'alternative_label' => $rfq->alternative_label ?: 'A',
                'vendor_id' => $vId,
                'vendor_name' => $vendorName,
                'rfq_status' => $rfq->rfq_status,
                'total_quoted_amount' => $quoteSum,
                'quoted_items_count' => $quoteCount,
                'lowest_price_items_count' => $lowestPriceCount,
                'average_lead_time' => !empty($leadTimes) ? round(array_sum($leadTimes) / count($leadTimes), 1) : 0,
                'variance_vs_budget' => $totalBudgetSum > 0 ? round((($quoteSum - $totalBudgetSum) / $totalBudgetSum) * 100, 2) : 0,
            ];
        }

        return [
            'rfq_group_id' => $groupId,
            'rfq_count' => $rfqs->count(),
            'rfqs' => $rfqs,
            'vendor_summaries' => array_values($vendorSummaries),
            'benchmark_budget_total' => $totalBudgetSum,
            'lowest_combined_total' => $lowestCombinedSum,
            'potential_savings_amount' => max(0, $totalBudgetSum - $lowestCombinedSum),
            'potential_savings_percent' => $totalBudgetSum > 0 ? round((max(0, $totalBudgetSum - $lowestCombinedSum) / $totalBudgetSum) * 100, 2) : 0,
            'matrix_items' => array_values($itemsMap),
        ];
    }

    /**
     * Atomically execute Full or Split Award and generate Purchase Orders.
     */
    public function awardBids(string $groupId, string $awardType, array $awards, ?string $userId = null, string $poStatus = 'Approved / Issued'): array
    {
        return DB::transaction(function () use ($groupId, $awardType, $awards, $userId, $poStatus) {
            $rfqs = RfqHeader::with(['lines.item', 'vendor'])
                ->where('rfq_group_id', $groupId)
                ->lockForUpdate()
                ->get();

            if ($rfqs->isEmpty()) {
                throw new InvalidArgumentException("No RFQ group found with ID {$groupId}");
            }

            // Check if group is already awarded
            $alreadyAwarded = $rfqs->contains(fn($r) => strtoupper($r->rfq_status) === 'AWARDED');
            if ($alreadyAwarded) {
                throw new InvalidArgumentException("This bidding package has already been awarded.");
            }

            // Group awards by winning vendor / RFQ
            // Format of $awards: [ ['item_id' => 'RAW-301', 'rfq_id' => 'uuid', 'vendor_id' => '...'], ... ]
            $vendorAwardMap = [];
            foreach ($awards as $award) {
                $rfqId = $award['rfq_id'];
                $itemId = $award['item_id'];
                $vendorAwardMap[$rfqId][] = $itemId;
            }

            $generatedPos = [];
            $year = date('Y');

            foreach ($rfqs as $rfq) {
                $awardedItemIds = $vendorAwardMap[$rfq->rfq_id] ?? [];

                if (!empty($awardedItemIds)) {
                    // Winning Vendor: Generate Purchase Order for awarded lines
                    $count = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
                    $poNumber = sprintf('PO-%s-%04d', $year, $count);

                    $awardedLines = $rfq->lines->whereIn('item_id', $awardedItemIds);
                    $grossTotal = 0.0;
                    foreach ($awardedLines as $line) {
                        $qty = (float) ($line->quoted_quantity ?: $line->requested_quantity);
                        $price = (float) ($line->quoted_unit_price ?: $line->target_unit_price);
                        $grossTotal += ($qty * $price);
                    }

                    $vendorName = $rfq->vendor ? ($rfq->vendor->trade_name ?? $rfq->vendor->legal_name) : 'Approved Vendor';

                    $po = PurchaseOrder::create([
                        'po_number' => $poNumber,
                        'po_type' => 'vendor',
                        'rfq_reference' => $rfq->rfq_number,
                        'vendor_id' => $rfq->vendor ? $rfq->vendor->vendor_code : $rfq->vendor_id,
                        'vendor_name' => $vendorName,
                        'order_date' => now()->toDateString(),
                        'expected_delivery' => $rfq->rfq_requested_delivery_date ?: now()->addDays(3)->toDateString(),
                        'delivery_location' => 'Central Commissary - Receiving Dock 1',
                        'payment_terms' => $rfq->rfq_payment_terms_code ?: 'Net 30 Days',
                        'payment_status' => 'Unpaid / Credit',
                        'payment_method' => 'Trade Credit (Net 30/15)',
                        'amount_paid' => 0.00,
                        'balance_due' => $grossTotal,
                        'approved_by' => $userId ?: 'Procurement Officer',
                        'status' => $poStatus,
                        'subtotal' => $grossTotal,
                        'tax_amount' => 0.00,
                        'gross_total' => $grossTotal,
                        'created_by_user' => $userId ?: 'Procurement Specialist',
                    ]);

                    foreach ($awardedLines as $line) {
                        $qty = (float) ($line->quoted_quantity ?: $line->requested_quantity);
                        $price = (float) ($line->quoted_unit_price ?: $line->target_unit_price);

                        PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'sku' => $line->item_id,
                            'item_name' => $line->item ? $line->item->name : $line->item_id,
                            'category' => 'Raw Ingredients',
                            'uom' => $line->purchase_uom_code ?: 'PCS',
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'total_amount' => $qty * $price,
                            'received_quantity' => 0.00,
                            'status' => 'PENDING',
                        ]);

                        $line->is_awarded = true;
                        $line->awarded_purchase_order_id = $po->id;
                        $line->award_notes = "Awarded via {$awardType} award in package {$groupId}";
                        $line->save();
                    }

                    // Mark unawarded lines on this winning RFQ as not awarded
                    $rfq->lines->whereNotIn('item_id', $awardedItemIds)->each(function ($line) {
                        $line->is_awarded = false;
                        $line->save();
                    });

                    $rfq->rfq_status = 'AWARDED';
                    $rfq->purchase_order_id = $po->id;
                    $rfq->save();

                    $generatedPos[] = [
                        'po_number' => $po->po_number,
                        'po_id' => $po->id,
                        'vendor_name' => $vendorName,
                        'gross_total' => $grossTotal,
                        'item_count' => count($awardedLines),
                        'rfq_number' => $rfq->rfq_number,
                    ];
                } else {
                    // Losing Vendor: Mark RFQ as CLOSED
                    $rfq->lines->each(function ($line) {
                        $line->is_awarded = false;
                        $line->save();
                    });
                    $rfq->rfq_status = 'CLOSED';
                    $rfq->rejection_reason = "Tender package awarded to alternative vendor(s).";
                    $rfq->save();
                }
            }

            return [
                'success' => true,
                'award_type' => $awardType,
                'rfq_group_id' => $groupId,
                'generated_pos' => $generatedPos,
                'total_awarded_pos' => count($generatedPos),
            ];
        });
    }

    private function generateAlternativeRfqNumber(string $baseNumber, string $label): string
    {
        // Strip any existing alternative suffix (e.g., 'RFQ-2026-0042-B' -> 'RFQ-2026-0042')
        $root = preg_replace('/-[A-Z]$/', '', $baseNumber);
        return "{$root}-{$label}";
    }
}

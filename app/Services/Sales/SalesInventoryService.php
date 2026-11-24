<?php

namespace App\Services\Sales;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\StockOutOrder;
use App\Models\Inventory\StockOutOrderItem;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\Sales\SalesOrderReservation;
use App\Services\Accounting\AccountingArService;
use App\Services\Accounting\AccountingNumberingService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalesInventoryService
{
    public function __construct(
        protected AccountingNumberingService $numberingService,
        protected AccountingPostingService $postingService,
        protected AccountingSeedService $seedService,
        protected AccountingArService $arService
    ) {
    }

    /**
     * Vectorized Available-to-Promise (ATP) map across item_master and active sales_order_reservations.
     */
    public function getAtpMap(?array $itemIds = null): array
    {
        $query = InventoryItem::query()->select(['id', 'sku', 'current_stock']);
        if (!empty($itemIds)) {
            $query->whereIn('id', $itemIds);
        }
        $items = $query->get();

        $reservedById = [];
        $reservedBySku = [];

        if (Schema::hasTable('sales_order_reservations')) {
            $resRows = SalesOrderReservation::where('status', 'ACTIVE')
                ->when(!empty($itemIds), fn ($q) => $q->whereIn('inventory_item_id', $itemIds))
                ->get(['inventory_item_id', 'sku', 'reserved_qty', 'fulfilled_qty']);

            foreach ($resRows as $row) {
                $openRes = max(0.0, (float) $row->reserved_qty - (float) $row->fulfilled_qty);
                if ($row->inventory_item_id) {
                    $idKey = (int) $row->inventory_item_id;
                    $reservedById[$idKey] = ($reservedById[$idKey] ?? 0.0) + $openRes;
                } elseif (!empty($row->sku)) {
                    $reservedBySku[$row->sku] = ($reservedBySku[$row->sku] ?? 0.0) + $openRes;
                }
            }
        }

        $map = [];
        foreach ($items as $item) {
            $onHand = (float) $item->current_stock;
            $reserved = ($reservedById[(int) $item->id] ?? 0.0) + ($reservedBySku[$item->sku] ?? 0.0);
            $map[(int) $item->id] = [
                'id' => (int) $item->id,
                'sku' => $item->sku,
                'on_hand' => round($onHand, 4),
                'reserved_qty' => round($reserved, 4),
                'atp_qty' => round($onHand - $reserved, 4),
            ];
        }

        return $map;
    }

    /**
     * Soft-reserve remaining undelivered quantities for an approved Sales Order.
     */
    public function reserveOrderStock(SalesOrder $order): void
    {
        if (!Schema::hasTable('sales_order_reservations')) {
            return;
        }

        $this->releaseOrderReservations($order);
        $order->loadMissing('lines');

        $skus = $order->lines->pluck('sku')->filter()->unique()->values()->all();
        $itemsBySku = !empty($skus)
            ? InventoryItem::whereIn('sku', $skus)->get()->keyBy('sku')
            : collect();

        foreach ($order->lines as $line) {
            $remaining = max(0.0, round((float) $line->qty_ordered - (float) $line->qty_delivered, 4));
            $itemId = $line->inventory_item_id ?: ($line->sku ? $itemsBySku->get($line->sku)?->id : null);

            if ($remaining <= 0.0001 || (!$itemId && empty($line->sku))) {
                $line->qty_reserved = 0;
                $line->save();
                continue;
            }

            SalesOrderReservation::create([
                'company_id' => $order->company_id ?: '1',
                'branch_id' => $order->branch_id ?: 'BR-01',
                'sales_order_id' => $order->sales_order_id,
                'sales_order_line_id' => $line->sales_order_line_id,
                'inventory_item_id' => $itemId,
                'sku' => $line->sku ?: ($itemsBySku->firstWhere('id', $itemId)?->sku ?? 'CUSTOM'),
                'warehouse_name' => $order->source_warehouse_name,
                'reserved_qty' => $remaining,
                'fulfilled_qty' => 0.0,
                'status' => 'ACTIVE',
            ]);

            $line->qty_reserved = $remaining;
            $line->save();
        }
    }

    /**
     * Release all active ATP reservations for a Sales Order.
     */
    public function releaseOrderReservations(SalesOrder $order): void
    {
        if (!Schema::hasTable('sales_order_reservations')) {
            return;
        }

        SalesOrderReservation::where('sales_order_id', $order->sales_order_id)
            ->where('status', 'ACTIVE')
            ->update(['status' => 'RELEASED']);

        SalesOrderLine::where('sales_order_id', $order->sales_order_id)
            ->update(['qty_reserved' => 0]);
    }

    /**
     * Create a Delivery Order (StockOutOrder with order_type=SALES_DELIVERY) from an Approved Sales Order.
     * Optionally ships immediately (deducting stock, logging StockLedger, posting COGS GL, creating DRAFT Invoice).
     */
    public function createDeliveryOrderFromSalesOrder(
        string $salesOrderId,
        array $lineDispatches,
        array $meta,
        string $actor,
        bool $shipImmediately = false
    ): array {
        return DB::transaction(function () use ($salesOrderId, $lineDispatches, $meta, $actor, $shipImmediately) {
            $order = SalesOrder::with('lines')
                ->where('sales_order_id', $salesOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array(strtoupper((string) $order->status), ['APPROVED', 'PARTIALLY_DELIVERED', 'BILLED'], true)) {
                throw new DomainException("Cannot create delivery for Sales Order [{$order->order_number}] in status [{$order->status}]. Credit approval is required first.");
            }

            if (strtoupper((string) $order->fulfillment_status) === 'DELIVERED') {
                throw new DomainException("Sales Order [{$order->order_number}] is already fully delivered.");
            }

            $linesById = $order->lines->keyBy('sales_order_line_id');
            $dispatchItems = [];

            if (empty($lineDispatches)) {
                foreach ($order->lines as $line) {
                    $rem = max(0.0, round((float) $line->qty_ordered - (float) $line->qty_delivered, 4));
                    if ($rem > 0.0001) {
                        $dispatchItems[] = [
                            'line' => $line,
                            'qty' => $rem,
                            'batch_lot_no' => null,
                        ];
                    }
                }
            } else {
                foreach ($lineDispatches as $row) {
                    $line = $linesById->get($row['sales_order_line_id'] ?? '');
                    if (!$line) {
                        continue;
                    }
                    $qty = round((float) ($row['qty'] ?? $row['quantity'] ?? 0), 4);
                    if ($qty <= 0.0001) {
                        continue;
                    }
                    $rem = max(0.0, round((float) $line->qty_ordered - (float) $line->qty_delivered, 4));
                    if ($qty > $rem + 0.0001) {
                        throw new DomainException("Delivery qty ({$qty}) exceeds remaining deliverable qty ({$rem}) for [{$line->item_name}].");
                    }
                    $dispatchItems[] = [
                        'line' => $line,
                        'qty' => $qty,
                        'batch_lot_no' => $row['batch_lot_no'] ?? null,
                    ];
                }
            }

            if (empty($dispatchItems)) {
                throw new DomainException("No deliverable quantities remaining on Sales Order [{$order->order_number}].");
            }

            $this->seedService->ensureSeeded((string) $order->company_id);

            do {
                $doNumber = $this->numberingService->generateNextNumber((string) $order->company_id, 'DO', 'DO');
            } while (StockOutOrder::where('order_number', $doNumber)->exists());

            $skus = collect($dispatchItems)->map(fn ($d) => $d['line']->sku)->filter()->unique()->values()->all();
            $itemIds = collect($dispatchItems)->map(fn ($d) => $d['line']->inventory_item_id)->filter()->unique()->values()->all();
            $invById = !empty($itemIds) ? InventoryItem::whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id') : collect();
            $invBySku = !empty($skus) ? InventoryItem::whereIn('sku', $skus)->lockForUpdate()->get()->keyBy('sku') : collect();

            $totalRequestedQty = 0.0;
            $totalCostValue = 0.0;

            foreach ($dispatchItems as $d) {
                $line = $d['line'];
                $invItem = $line->inventory_item_id
                    ? ($invById->get($line->inventory_item_id) ?? $invBySku->get($line->sku))
                    : $invBySku->get($line->sku);

                $unitCost = (float) ($line->unit_cost_snapshot > 0 ? $line->unit_cost_snapshot : ($invItem?->cost_price ?? 0));
                $totalRequestedQty += $d['qty'];
                $totalCostValue += round($d['qty'] * $unitCost, 2);
            }

            $stockOut = StockOutOrder::create([
                'order_number' => $doNumber,
                'order_type' => 'SALES_DELIVERY',
                'status' => $shipImmediately ? 'PACKED' : 'DRAFT',
                'priority' => $meta['priority'] ?? 'NORMAL',
                'destination_type' => 'CUSTOMER',
                'destination_location' => $meta['shipping_address'] ?? $order->shipping_address ?? $order->billing_address ?? $order->customer_name,
                'vendor_id' => null,
                'vendor_name' => $order->customer_name,
                'sales_order_id' => $order->sales_order_id,
                'sales_order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'requested_by' => $actor,
                'department' => 'Sales Operations',
                'picker_name' => $meta['picker_name'] ?? null,
                'carrier_name' => $meta['carrier_name'] ?? null,
                'tracking_waybill' => $meta['tracking_waybill'] ?? null,
                'reference_no' => $order->order_number,
                'required_at' => $order->requested_delivery_date,
                'total_items_count' => count($dispatchItems),
                'total_requested_qty' => round($totalRequestedQty, 2),
                'total_packed_qty' => round($totalRequestedQty, 2),
                'total_cost_value' => round($totalCostValue, 2),
                'notes' => $meta['notes'] ?? "Delivery for Sales Order {$order->order_number} ({$order->customer_name})",
            ]);

            foreach ($dispatchItems as $d) {
                $line = $d['line'];
                $invItem = $line->inventory_item_id
                    ? ($invById->get($line->inventory_item_id) ?? $invBySku->get($line->sku))
                    : $invBySku->get($line->sku);

                $unitCost = (float) ($line->unit_cost_snapshot > 0 ? $line->unit_cost_snapshot : ($invItem?->cost_price ?? 0));
                $qty = (float) $d['qty'];

                StockOutOrderItem::create([
                    'stock_out_order_id' => $stockOut->id,
                    'inventory_item_id' => $invItem?->id ?? $line->inventory_item_id,
                    'sales_order_line_id' => $line->sales_order_line_id,
                    'sku' => $line->sku ?: ($invItem?->sku ?? 'CUSTOM'),
                    'item_name' => $line->item_name,
                    'category' => $invItem?->category ?? 'Finished Goods',
                    'uom' => $line->uom ?: 'PCS',
                    'available_stock' => $invItem ? (float) $invItem->current_stock : 0.0,
                    'requested_qty' => round($qty, 2),
                    'packed_qty' => round($qty, 2),
                    'unit_cost' => round($unitCost, 2),
                    'total_cost' => round($qty * $unitCost, 2),
                    'batch_lot_no' => $d['batch_lot_no'],
                    'storage_location' => $invItem?->storage_location ?? $order->source_warehouse_name,
                    'notes' => $line->line_notes,
                ]);
            }

            $salesSync = null;
            if ($shipImmediately) {
                $stockOut->load('items');
                $shippedCost = 0.0;
                $shippedQty = 0.0;

                foreach ($stockOut->items as $sItem) {
                    $qtyToDeduct = (float) $sItem->packed_qty;
                    if ($qtyToDeduct <= 0) {
                        continue;
                    }
                    $invItem = $sItem->inventory_item_id
                        ? ($invById->get($sItem->inventory_item_id) ?? $invBySku->get($sItem->sku))
                        : $invBySku->get($sItem->sku);

                    if ($invItem) {
                        $beforeQty = (float) $invItem->current_stock;
                        if ($qtyToDeduct > ($beforeQty + 0.0001)) {
                            throw new DomainException(sprintf(
                                'Insufficient on-hand stock for [%s] %s: Available %s, Requested %s. Queue as Draft Stock-Out or replenish inventory first.',
                                $invItem->sku,
                                $invItem->name,
                                round($beforeQty, 2),
                                round($qtyToDeduct, 2)
                            ));
                        }
                        $afterQty = $beforeQty - $qtyToDeduct;
                        $invItem->current_stock = $afterQty;
                        $invItem->save();

                        StockLedger::create([
                            'transaction_uuid' => (string) Str::uuid(),
                            'sku' => $sItem->sku,
                            'inventory_item_id' => $invItem->id,
                            'item_name' => $sItem->item_name,
                            'transaction_type' => 'STOCK_OUT',
                            'reference_type' => 'stock_out_orders',
                            'reference_id' => $stockOut->id,
                            'reference_no' => $stockOut->order_number,
                            'before_quantity' => $beforeQty,
                            'quantity_change' => -$qtyToDeduct,
                            'after_quantity' => $afterQty,
                            'unit_cost' => $sItem->unit_cost,
                            'total_value' => $qtyToDeduct * (float) $sItem->unit_cost,
                            'batch_lot_no' => $sItem->batch_lot_no,
                            'storage_location' => $sItem->storage_location ?? $invItem->storage_location,
                            'performed_by' => $actor,
                            'notes' => "Sales Delivery [{$order->order_number}] to {$order->customer_name}",
                        ]);
                    }

                    $shippedQty += $qtyToDeduct;
                    $shippedCost += ($qtyToDeduct * (float) $sItem->unit_cost);
                }

                $stockOut->update([
                    'status' => 'SHIPPED',
                    'dispatched_at' => now(),
                    'approved_by' => $actor,
                    'picker_name' => $stockOut->picker_name ?: $actor,
                    'total_packed_qty' => round($shippedQty, 2),
                    'total_cost_value' => round($shippedCost, 2),
                ]);

                $salesSync = $this->onDeliveryShipped($stockOut->fresh('items'), $actor);
            }

            return [
                'delivery' => $stockOut->fresh('items'),
                'sales_sync' => $salesSync,
            ];
        });
    }

    /**
     * Hook invoked atomically when a Sales Delivery StockOutOrder transitions to SHIPPED:
     * 1) Updates sales_order_lines.qty_delivered & fulfills ATP reservations
     * 2) Updates sales_orders.fulfillment_status & status
     * 3) Posts perpetual COGS GL journal (DR COGS / CR Inventory Asset)
     * 4) Auto-creates a DRAFT AcctSalesInvoice in Accounting AR for 1-click posting
     */
    public function onDeliveryShipped(StockOutOrder $stockOut, string $actor): array
    {
        if (empty($stockOut->sales_order_id) || !Schema::hasTable('sales_orders')) {
            return [];
        }

        $order = SalesOrder::with('lines')
            ->where('sales_order_id', $stockOut->sales_order_id)
            ->lockForUpdate()
            ->first();

        if (!$order) {
            return [];
        }

        $stockOut->loadMissing('items');
        $linesById = $order->lines->keyBy('sales_order_line_id');
        $linesBySku = $order->lines->keyBy('sku');

        $invoiceLinesPayload = [];

        foreach ($stockOut->items as $shippedItem) {
            $shippedQty = (float) $shippedItem->packed_qty;
            if ($shippedQty <= 0.0001) {
                continue;
            }

            $soLine = !empty($shippedItem->sales_order_line_id)
                ? $linesById->get($shippedItem->sales_order_line_id)
                : $linesBySku->get($shippedItem->sku);

            if ($soLine) {
                $soLine->qty_delivered = round((float) $soLine->qty_delivered + $shippedQty, 4);
                $remainingToDeliver = max(0.0, round((float) $soLine->qty_ordered - (float) $soLine->qty_delivered, 4));
                $soLine->qty_reserved = $remainingToDeliver;
                $soLine->save();

                if (Schema::hasTable('sales_order_reservations')) {
                    $res = SalesOrderReservation::where('sales_order_line_id', $soLine->sales_order_line_id)
                        ->where('status', 'ACTIVE')
                        ->lockForUpdate()
                        ->first();
                    if ($res) {
                        $res->fulfilled_qty = min((float) $res->reserved_qty, round((float) $res->fulfilled_qty + $shippedQty, 4));
                        if ($res->fulfilled_qty >= (float) $res->reserved_qty - 0.0001) {
                            $res->status = 'FULFILLED';
                        }
                        $res->save();
                    }
                }

                $netUnitPrice = (float) $soLine->qty_ordered > 0
                    ? round((float) $soLine->line_subtotal / (float) $soLine->qty_ordered, 4)
                    : (float) $soLine->unit_price;

                $invoiceLinesPayload[] = [
                    'sales_order_line_id' => $soLine->sales_order_line_id,
                    'item_code' => $soLine->sku,
                    'description' => ($soLine->sku ? "[{$soLine->sku}] " : '') . $soLine->item_name,
                    'quantity' => $shippedQty,
                    'uom' => $soLine->uom ?: 'PCS',
                    'unit_price' => $netUnitPrice,
                    'tax_code' => $soLine->tax_code ?: 'VAT12_OUT',
                ];
            }
        }

        // Recompute fulfillment status
        $order->load('lines');
        $allDelivered = $order->lines->every(function (SalesOrderLine $l) {
            return (float) $l->qty_delivered >= ((float) $l->qty_ordered - 0.0001);
        });
        $anyDelivered = $order->lines->some(fn (SalesOrderLine $l) => (float) $l->qty_delivered > 0.0001);

        $order->fulfillment_status = $allDelivered
            ? 'DELIVERED'
            : ($anyDelivered ? 'PARTIALLY_DELIVERED' : 'UNFULFILLED');

        if (!in_array(strtoupper((string) $order->status), ['CLOSED', 'CANCELLED', 'BILLED'], true)) {
            $order->status = $order->fulfillment_status === 'DELIVERED' ? 'DELIVERED' : 'PARTIALLY_DELIVERED';
        }
        $order->save();

        // Perpetual COGS GL Posting (DR COGS / CR Inventory Asset)
        $companyId = (string) ($order->company_id ?: '1');
        $branchId = (string) ($order->branch_id ?: 'BR-01');
        $cogsAmount = round((float) $stockOut->total_cost_value, 4);

        if ($cogsAmount > 0.0001 && empty($stockOut->cogs_journal_id) && Schema::hasTable('gl_journal_headers')) {
            $this->seedService->ensureSeeded($companyId);
            $cogsAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'COGS');
            $inventoryAccount = $this->postingService->resolveSystemRoleAccount($companyId, 'INVENTORY_ASSET');

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_date' => now()->toDateString(),
                'journal_type' => 'SYSTEM_INVENTORY',
                'source_module' => 'INVENTORY',
                'source_document_type' => 'SALES_DELIVERY',
                'source_document_id' => (string) $stockOut->id,
                'source_document_number' => $stockOut->order_number,
                'currency_code' => $order->currency_code ?: 'PHP',
                'exchange_rate' => (float) ($order->exchange_rate ?: 1.0),
                'is_system_generated' => true,
                'description' => "COGS Recognition for Delivery {$stockOut->order_number} (SO: {$order->order_number})",
                'acted_by' => $actor,
            ], [
                [
                    'account_id' => $cogsAccount->account_id,
                    'branch_id' => $branchId,
                    'partner_type' => 'CUSTOMER',
                    'partner_id' => $order->customer_id,
                    'transaction_debit' => $cogsAmount,
                    'transaction_credit' => 0.0,
                    'description' => "Cost of Goods Sold — {$stockOut->order_number}",
                ],
                [
                    'account_id' => $inventoryAccount->account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $cogsAmount,
                    'description' => "Inventory Dispatch Deduction — {$stockOut->order_number}",
                ],
            ], 'POSTED');

            $stockOut->cogs_journal_id = $journal->journal_id;
            $stockOut->save();
        }

        // Auto-create DRAFT Sales Invoice in Accounting AR (Approved Policy A3)
        $draftInvoice = null;
        if (!empty($invoiceLinesPayload) && Schema::hasTable('acct_sales_invoices')) {
            $this->seedService->ensureSeeded($companyId);
            $linesGrossSum = collect($invoiceLinesPayload)->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
            $orderLinesGrossSum = (float) $order->lines->sum('line_subtotal');
            $totalHeaderDisc = (float) $order->discount_amount + (float) $order->voucher_discount_amount;
            $proRatedDisc = ($orderLinesGrossSum > 0.0001 && $totalHeaderDisc > 0.0001)
                ? round($totalHeaderDisc * min(1.0, $linesGrossSum / $orderLinesGrossSum), 4)
                : 0.0;

            $dueDays = app(SalesCreditControlService::class)->resolveDueDays($companyId, $order->payment_terms_code);

            $draftInvoice = $this->arService->saveDraftOrPostInvoice([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $order->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($dueDays)->toDateString(),
                'payment_terms_code' => $order->payment_terms_code ?: 'NET_30',
                'currency_code' => $order->currency_code ?: 'PHP',
                'exchange_rate' => (float) ($order->exchange_rate ?: 1.0),
                'discount_amount' => $proRatedDisc,
                'sales_order_id' => $order->sales_order_id,
                'sales_order_number' => $order->order_number,
                'stock_out_order_ref_id' => $stockOut->id,
                'stock_out_number' => $stockOut->order_number,
                'notes' => "Auto-drafted from Delivery {$stockOut->order_number} (Sales Order {$order->order_number})",
                'acted_by' => $actor,
            ], $invoiceLinesPayload, 'DRAFT');
        }

        return [
            'order' => $order->fresh(['lines']),
            'cogs_journal_id' => $stockOut->cogs_journal_id,
            'draft_invoice' => $draftInvoice,
        ];
    }
}

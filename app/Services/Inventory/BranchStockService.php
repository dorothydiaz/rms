<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Inventory\BranchStockBalance;
use App\Models\Inventory\InternalTransfer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BranchStockService
{
    /**
     * O(1) indexed balance snapshot for a single branch.
     *
     * @param  array<int, int>|null  $itemIds
     * @return array<int, array<string, float>>
     */
    public function getBranchStockMap(int $branchId, ?array $itemIds = null): array
    {
        $query = BranchStockBalance::query()
            ->where('branch_id', $branchId)
            ->select([
                'inventory_item_id',
                'on_hand_qty',
                'reserved_qty',
                'in_transit_in_qty',
                'in_transit_out_qty',
                'avg_unit_cost',
            ]);

        if ($itemIds !== null) {
            $cleanIds = array_values(array_unique(array_map('intval', $itemIds)));
            if (empty($cleanIds)) {
                return [];
            }
            $query->whereIn('inventory_item_id', $cleanIds);
        }

        $map = [];
        foreach ($query->get() as $row) {
            $onHand = (float) $row->on_hand_qty;
            $reserved = (float) $row->reserved_qty;
            $map[(int) $row->inventory_item_id] = [
                'on_hand_qty' => $onHand,
                'reserved_qty' => $reserved,
                'available_qty' => max(0.0, round($onHand - $reserved, 4)),
                'in_transit_in_qty' => (float) $row->in_transit_in_qty,
                'in_transit_out_qty' => (float) $row->in_transit_out_qty,
                'avg_unit_cost' => (float) $row->avg_unit_cost,
            ];
        }

        return $map;
    }

    /**
     * Lock & ensure balance rows exist for sorted item IDs (deadlock-free).
     *
     * @param  array<int, int>  $itemIds
     * @return Collection<int, BranchStockBalance>
     */
    public function ensureAndLockBalances(int $branchId, array $itemIds, ?int $companyId = null): Collection
    {
        $sortedIds = array_values(array_unique(array_map('intval', $itemIds)));
        sort($sortedIds);

        if (empty($sortedIds)) {
            return collect();
        }

        $existing = BranchStockBalance::query()
            ->where('branch_id', $branchId)
            ->whereIn('inventory_item_id', $sortedIds)
            ->orderBy('inventory_item_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('inventory_item_id');

        $missingIds = array_values(array_diff($sortedIds, $existing->keys()->map(fn ($id) => (int) $id)->all()));
        if (!empty($missingIds)) {
            $items = InventoryItem::query()
                ->whereIn('id', $missingIds)
                ->orderBy('id')
                ->get(['id', 'sku', 'cost_price'])
                ->keyBy('id');

            foreach ($missingIds as $itemId) {
                $item = $items->get($itemId);
                if (!$item) {
                    continue;
                }
                $created = BranchStockBalance::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'inventory_item_id' => $itemId,
                    'sku' => $item->sku,
                    'on_hand_qty' => 0.0000,
                    'reserved_qty' => 0.0000,
                    'in_transit_in_qty' => 0.0000,
                    'in_transit_out_qty' => 0.0000,
                    'avg_unit_cost' => (float) $item->cost_price,
                    'last_movement_at' => now(),
                ]);
                $existing->put($itemId, $created);
            }
        }

        return $existing;
    }

    /**
     * Soft-reserve available stock at source branch when approving a branch request.
     *
     * @param  array<int, float>  $baseQtyByItemId
     */
    public function reserve(int $branchId, array $baseQtyByItemId, ?int $companyId = null): void
    {
        $itemIds = array_keys($baseQtyByItemId);
        if (empty($itemIds)) {
            return;
        }

        $balances = $this->ensureAndLockBalances($branchId, $itemIds, $companyId);
        $items = InventoryItem::query()->whereIn('id', $itemIds)->get(['id', 'name', 'sku', 'uom'])->keyBy('id');
        $now = now();

        foreach ($balances->keys()->sort() as $itemId) {
            $qty = round((float) ($baseQtyByItemId[$itemId] ?? 0), 4);
            if ($qty <= 0) {
                continue;
            }

            /** @var BranchStockBalance $balance */
            $balance = $balances->get($itemId);
            $available = max(0.0, round((float) $balance->on_hand_qty - (float) $balance->reserved_qty, 4));
            if ($qty > $available + 0.0001) {
                $item = $items->get($itemId);
                $label = $item ? "{$item->name} (SKU: {$item->sku})" : "Item #{$itemId}";
                throw ValidationException::withMessages([
                    'items' => ["Insufficient available stock at source branch for {$label}. Requested reservation ({$qty}) exceeds available ({$available})."],
                ]);
            }

            $balance->reserved_qty = round((float) $balance->reserved_qty + $qty, 4);
            $balance->last_movement_at = $now;
            $balance->save();
        }
    }

    /**
     * Release soft-reserved stock at source branch (cancellation or rejection of approved request).
     *
     * @param  array<int, float>  $baseQtyByItemId
     */
    public function releaseReservation(int $branchId, array $baseQtyByItemId): void
    {
        $itemIds = array_keys($baseQtyByItemId);
        if (empty($itemIds)) {
            return;
        }

        $balances = $this->ensureAndLockBalances($branchId, $itemIds);
        $now = now();

        foreach ($balances->keys()->sort() as $itemId) {
            $qty = round((float) ($baseQtyByItemId[$itemId] ?? 0), 4);
            if ($qty <= 0) {
                continue;
            }

            /** @var BranchStockBalance $balance */
            $balance = $balances->get($itemId);
            $balance->reserved_qty = max(0.0, round((float) $balance->reserved_qty - $qty, 4));
            $balance->last_movement_at = $now;
            $balance->save();
        }
    }

    /**
     * Step 1 of Two-Step Transit Accounting (Source Branch Dispatch -> In-Transit).
     * Deducts source on_hand_qty, releases line reservation, adds in_transit_out & dest in_transit_in,
     * syncs item_master.current_stock, and appends TRANSFER_OUT ledger records.
     *
     * @param  array<int, array{item_id: int, sku: string, item_name: string, ship_base_qty: float, release_reserved_base_qty: float, unit_cost: float, batch_lot_no: ?string, storage_location: ?string}>  $dispatchLines
     */
    public function dispatchOut(
        int $sourceBranchId,
        int $destBranchId,
        InternalTransfer $transfer,
        array $dispatchLines,
        string $performedBy
    ): void {
        if (empty($dispatchLines)) {
            return;
        }

        $itemIds = array_values(array_unique(array_map(fn ($l) => (int) $l['item_id'], $dispatchLines)));
        sort($itemIds);

        // Deterministic lock order on branches to prevent deadlocks
        if ($sourceBranchId < $destBranchId) {
            $sourceBalances = $this->ensureAndLockBalances($sourceBranchId, $itemIds, $transfer->company_id);
            $destBalances = $this->ensureAndLockBalances($destBranchId, $itemIds, $transfer->company_id);
        } else {
            $destBalances = $this->ensureAndLockBalances($destBranchId, $itemIds, $transfer->company_id);
            $sourceBalances = $this->ensureAndLockBalances($sourceBranchId, $itemIds, $transfer->company_id);
        }

        $masterItems = InventoryItem::query()
            ->whereIn('id', $itemIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $now = now();
        $ledgerRows = [];

        foreach ($dispatchLines as $line) {
            $itemId = (int) $line['item_id'];
            $shipQty = round((float) $line['ship_base_qty'], 4);
            $releaseReserved = round((float) ($line['release_reserved_base_qty'] ?? 0), 4);
            if ($shipQty <= 0) {
                continue;
            }

            /** @var BranchStockBalance $srcBal */
            $srcBal = $sourceBalances->get($itemId);
            /** @var BranchStockBalance $dstBal */
            $dstBal = $destBalances->get($itemId);
            /** @var InventoryItem|null $master */
            $master = $masterItems->get($itemId);

            if (!$srcBal || !$dstBal || !$master) {
                throw ValidationException::withMessages([
                    'items' => ["Inventory item SKU {$line['sku']} not found in Item Master."],
                ]);
            }

            $srcBeforeOnHand = (float) $srcBal->on_hand_qty;
            $otherReserved = max(0.0, round((float) $srcBal->reserved_qty - $releaseReserved, 4));
            $effectiveAvailable = max(0.0, round($srcBeforeOnHand - $otherReserved, 4));

            if ($shipQty > $effectiveAvailable + 0.0001) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient available stock at {$transfer->source_location} for '{$master->name}' (SKU: {$master->sku}). Requested dispatch ({$shipQty} {$master->uom}) exceeds available stock ({$effectiveAvailable} {$master->uom})."],
                ]);
            }

            $srcAfterOnHand = max(0.0, round($srcBeforeOnHand - $shipQty, 4));
            $srcBal->on_hand_qty = $srcAfterOnHand;
            $srcBal->reserved_qty = $otherReserved;
            $srcBal->in_transit_out_qty = round((float) $srcBal->in_transit_out_qty + $shipQty, 4);
            $srcBal->last_movement_at = $now;
            $srcBal->save();

            $dstBal->in_transit_in_qty = round((float) $dstBal->in_transit_in_qty + $shipQty, 4);
            $dstBal->last_movement_at = $now;
            $dstBal->save();

            // Sync legacy consolidated item_master.current_stock (on-hand excludes in-transit until received)
            $master->current_stock = max(0.0, round((float) $master->current_stock - $shipQty, 2));
            $master->save();

            $unitCost = (float) $line['unit_cost'];
            $ledgerRows[] = [
                'transaction_uuid' => (string) Str::uuid(),
                'branch_id' => $sourceBranchId,
                'sku' => $master->sku,
                'inventory_item_id' => $master->id,
                'item_name' => $master->name,
                'transaction_type' => 'TRANSFER_OUT',
                'reference_type' => 'stock_transfer_header',
                'reference_id' => $transfer->id,
                'reference_no' => $transfer->transfer_number,
                'before_quantity' => round($srcBeforeOnHand, 2),
                'quantity_change' => round(-$shipQty, 2),
                'after_quantity' => round($srcAfterOnHand, 2),
                'unit_cost' => round($unitCost, 2),
                'total_value' => round($shipQty * $unitCost, 2),
                'batch_lot_no' => $line['batch_lot_no'] ?? null,
                'storage_location' => $line['storage_location'] ?? $transfer->source_location,
                'performed_by' => $performedBy,
                'notes' => "Dispatched {$transfer->transfer_number} from {$transfer->source_location} to {$transfer->destination_location}",
                'created_at' => $now,
            ];
        }

        if (!empty($ledgerRows)) {
            StockLedger::insert($ledgerRows);
        }
    }

    /**
     * Step 2 of Two-Step Transit Accounting (Destination Branch Acceptance Receipt).
     * Credits destination on_hand_qty, clears source/dest in-transit counters, syncs item_master.current_stock,
     * and appends TRANSFER_IN (+ TRANSFER_VARIANCE if shortage/damage) ledger records.
     *
     * @param  array<int, array{item_id: int, sku: string, item_name: string, receive_base_qty: float, variance_base_qty: float, variance_reason: ?string, unit_cost: float, batch_lot_no: ?string, storage_location: ?string}>  $receiveLines
     */
    public function receiveIn(
        int $sourceBranchId,
        int $destBranchId,
        InternalTransfer $transfer,
        array $receiveLines,
        string $performedBy
    ): void {
        if (empty($receiveLines)) {
            return;
        }

        $itemIds = array_values(array_unique(array_map(fn ($l) => (int) $l['item_id'], $receiveLines)));
        sort($itemIds);

        if ($sourceBranchId < $destBranchId) {
            $sourceBalances = $this->ensureAndLockBalances($sourceBranchId, $itemIds, $transfer->company_id);
            $destBalances = $this->ensureAndLockBalances($destBranchId, $itemIds, $transfer->company_id);
        } else {
            $destBalances = $this->ensureAndLockBalances($destBranchId, $itemIds, $transfer->company_id);
            $sourceBalances = $this->ensureAndLockBalances($sourceBranchId, $itemIds, $transfer->company_id);
        }

        $masterItems = InventoryItem::query()
            ->whereIn('id', $itemIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $now = now();
        $ledgerRows = [];
        $refVoucher = $transfer->receipt_number ?: $transfer->transfer_number;

        foreach ($receiveLines as $line) {
            $itemId = (int) $line['item_id'];
            $recvQty = round((float) $line['receive_base_qty'], 4);
            $varQty = round((float) ($line['variance_base_qty'] ?? 0), 4);
            $clearTransit = round($recvQty + $varQty, 4);

            if ($clearTransit <= 0) {
                continue;
            }

            /** @var BranchStockBalance $srcBal */
            $srcBal = $sourceBalances->get($itemId);
            /** @var BranchStockBalance $dstBal */
            $dstBal = $destBalances->get($itemId);
            /** @var InventoryItem|null $master */
            $master = $masterItems->get($itemId);

            if (!$srcBal || !$dstBal || !$master) {
                continue;
            }

            $dstBeforeOnHand = (float) $dstBal->on_hand_qty;
            $dstAfterOnHand = round($dstBeforeOnHand + $recvQty, 4);

            // Weighted moving average unit cost update at destination
            $unitCost = (float) $line['unit_cost'];
            if ($recvQty > 0 && $unitCost > 0) {
                $existingVal = max(0.0, $dstBeforeOnHand) * (float) $dstBal->avg_unit_cost;
                $incomingVal = $recvQty * $unitCost;
                $newDenom = max(0.0, $dstBeforeOnHand) + $recvQty;
                if ($newDenom > 0) {
                    $dstBal->avg_unit_cost = round(($existingVal + $incomingVal) / $newDenom, 4);
                }
            }

            $dstBal->on_hand_qty = $dstAfterOnHand;
            $dstBal->in_transit_in_qty = max(0.0, round((float) $dstBal->in_transit_in_qty - $clearTransit, 4));
            $dstBal->last_movement_at = $now;
            $dstBal->save();

            $srcBal->in_transit_out_qty = max(0.0, round((float) $srcBal->in_transit_out_qty - $clearTransit, 4));
            $srcBal->last_movement_at = $now;
            $srcBal->save();

            if ($recvQty > 0) {
                $master->current_stock = round((float) $master->current_stock + $recvQty, 2);
                $master->save();

                $ledgerRows[] = [
                    'transaction_uuid' => (string) Str::uuid(),
                    'branch_id' => $destBranchId,
                    'sku' => $master->sku,
                    'inventory_item_id' => $master->id,
                    'item_name' => $master->name,
                    'transaction_type' => 'TRANSFER_IN',
                    'reference_type' => 'stock_transfer_header',
                    'reference_id' => $transfer->id,
                    'reference_no' => $refVoucher,
                    'before_quantity' => round($dstBeforeOnHand, 2),
                    'quantity_change' => round($recvQty, 2),
                    'after_quantity' => round($dstAfterOnHand, 2),
                    'unit_cost' => round($unitCost, 2),
                    'total_value' => round($recvQty * $unitCost, 2),
                    'batch_lot_no' => $line['batch_lot_no'] ?? null,
                    'storage_location' => $line['storage_location'] ?? $transfer->destination_location,
                    'performed_by' => $performedBy,
                    'notes' => "Accepted receipt {$refVoucher} for transfer {$transfer->transfer_number} from {$transfer->source_location}",
                    'created_at' => $now,
                ];
            }

            if ($varQty > 0) {
                $reason = $line['variance_reason'] ?? 'SHORT_PICKED';
                $ledgerRows[] = [
                    'transaction_uuid' => (string) Str::uuid(),
                    'branch_id' => $destBranchId,
                    'sku' => $master->sku,
                    'inventory_item_id' => $master->id,
                    'item_name' => $master->name,
                    'transaction_type' => 'TRANSFER_VARIANCE',
                    'reference_type' => 'stock_transfer_header',
                    'reference_id' => $transfer->id,
                    'reference_no' => $refVoucher,
                    'before_quantity' => round($dstAfterOnHand, 2),
                    'quantity_change' => round(-$varQty, 2),
                    'after_quantity' => round($dstAfterOnHand, 2),
                    'unit_cost' => round($unitCost, 2),
                    'total_value' => round($varQty * $unitCost, 2),
                    'batch_lot_no' => $line['batch_lot_no'] ?? null,
                    'storage_location' => $line['storage_location'] ?? $transfer->destination_location,
                    'performed_by' => $performedBy,
                    'notes' => "Transit shortage/variance ({$reason}) on {$transfer->transfer_number} [{$refVoucher}]",
                    'created_at' => $now,
                ];
            }
        }

        if (!empty($ledgerRows)) {
            StockLedger::insert($ledgerRows);
        }
    }

    /**
     * Lightweight O(1) hook invoked when Stock In (GRN) commits accepted quantities.
     *
     * @param  array<int, array{qty: float, unit_cost: float}>  $itemDeltasById
     */
    public function creditBranchOnStockIn(int $branchId, array $itemDeltasById, ?int $companyId = null): void
    {
        if ($branchId <= 0 || empty($itemDeltasById)) {
            return;
        }

        $balances = $this->ensureAndLockBalances($branchId, array_keys($itemDeltasById), $companyId);
        $now = now();

        foreach ($balances->keys()->sort() as $itemId) {
            $delta = $itemDeltasById[$itemId] ?? null;
            if (!$delta || (float) ($delta['qty'] ?? 0) <= 0) {
                continue;
            }

            /** @var BranchStockBalance $bal */
            $bal = $balances->get($itemId);
            $qty = (float) $delta['qty'];
            $cost = (float) ($delta['unit_cost'] ?? $bal->avg_unit_cost);
            $before = (float) $bal->on_hand_qty;
            $after = round($before + $qty, 4);

            if ($cost > 0 && $after > 0) {
                $bal->avg_unit_cost = round(((max(0.0, $before) * (float) $bal->avg_unit_cost) + ($qty * $cost)) / $after, 4);
            }
            $bal->on_hand_qty = $after;
            $bal->last_movement_at = $now;
            $bal->save();
        }
    }

    /**
     * Adjust a single branch's on-hand balance (used by Stock Out, Production, Waste, and Adjustments).
     */
    public function adjustBranchOnHand(
        int $branchId,
        int $itemId,
        float $qtyDelta,
        ?float $unitCost = null,
        ?int $companyId = null
    ): ?BranchStockBalance {
        if ($branchId <= 0 || $itemId <= 0 || abs($qtyDelta) < 0.0001) {
            return null;
        }

        $balances = $this->ensureAndLockBalances($branchId, [$itemId], $companyId);
        /** @var BranchStockBalance|null $bal */
        $bal = $balances->get($itemId);
        if (!$bal) {
            return null;
        }

        $before = (float) $bal->on_hand_qty;
        $after = max(0.0, round($before + $qtyDelta, 4));

        if ($qtyDelta > 0 && $unitCost !== null && $unitCost > 0 && $after > 0) {
            $bal->avg_unit_cost = round(((max(0.0, $before) * (float) $bal->avg_unit_cost) + ($qtyDelta * $unitCost)) / $after, 4);
        }

        $bal->on_hand_qty = $after;
        $bal->last_movement_at = now();
        $bal->save();

        return $bal;
    }
}

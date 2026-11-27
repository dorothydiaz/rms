<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\Inventory\TransferPriority;
use App\Enums\Inventory\TransferStatus;
use App\Enums\Inventory\TransferType;
use App\Enums\Inventory\VarianceReason;
use App\Models\Branch;
use App\Models\Inventory\BranchStockBalance;
use App\Models\Inventory\InternalTransfer;
use App\Models\Inventory\InternalTransferItem;
use App\Models\Inventory\InventoryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StockTransferService
{
    public function __construct(
        protected BranchStockService $branchStockService,
        protected InterBranchAccountingService $accountingService
    ) {
    }

    /**
     * O(1) paginated register + SQL GROUP BY KPI payload.
     * Zero full-table loads, zero line-item eager loading on index rows.
     */
    public function getRegisterPayload(array $filters, ?int $activeBranchId, bool $isConsolidated): array
    {
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perspective = (string) ($filters['perspective'] ?? 'all');
        $status = strtoupper(trim((string) ($filters['status'] ?? 'ALL')));
        $transferType = strtoupper(trim((string) ($filters['transfer_type'] ?? 'ALL')));
        $branchFilter = isset($filters['branch_id']) && is_numeric($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));

        $baseScope = InternalTransfer::query();
        if (!$isConsolidated && $activeBranchId !== null) {
            $baseScope->where(function ($q) use ($activeBranchId) {
                $q->where('source_branch_id', $activeBranchId)
                  ->orWhere('destination_branch_id', $activeBranchId);
            });
        }

        // 1. Single indexed SQL GROUP BY KPI aggregate query
        $kpiQuery = (clone $baseScope)
            ->selectRaw('
                status,
                source_branch_id,
                destination_branch_id,
                has_discrepancy,
                COUNT(*) as row_count,
                COALESCE(SUM(total_valuation), 0) as sum_valuation
            ')
            ->groupBy('status', 'source_branch_id', 'destination_branch_id', 'has_discrepancy')
            ->get();

        $stats = [
            'totalTransfers' => 0,
            'incomingRequestsCount' => 0,
            'outgoingRequestsCount' => 0,
            'inTransitCount' => 0,
            'toReceiveCount' => 0,
            'completedCount' => 0,
            'discrepancyCount' => 0,
            'inTransitValuation' => 0.0,
        ];

        $openRequestStatuses = [
            TransferStatus::DRAFT->value,
            TransferStatus::PENDING->value,
            TransferStatus::REQUESTED->value,
            TransferStatus::APPROVED->value,
        ];
        $transitStatuses = [
            TransferStatus::IN_TRANSIT->value,
            TransferStatus::PARTIALLY_RECEIVED->value,
        ];

        foreach ($kpiQuery as $agg) {
            $count = (int) $agg->row_count;
            $val = (float) $agg->sum_valuation;
            $st = (string) $agg->status;
            $srcId = $agg->source_branch_id !== null ? (int) $agg->source_branch_id : null;
            $dstId = $agg->destination_branch_id !== null ? (int) $agg->destination_branch_id : null;

            $stats['totalTransfers'] += $count;

            if (in_array($st, $openRequestStatuses, true)) {
                if ($isConsolidated || $activeBranchId === null || $srcId === $activeBranchId) {
                    $stats['incomingRequestsCount'] += $count;
                }
                if ($isConsolidated || $activeBranchId === null || $dstId === $activeBranchId) {
                    $stats['outgoingRequestsCount'] += $count;
                }
            }

            if (in_array($st, $transitStatuses, true)) {
                $stats['inTransitCount'] += $count;
                $stats['inTransitValuation'] += $val;
                if ($isConsolidated || $activeBranchId === null || $dstId === $activeBranchId) {
                    $stats['toReceiveCount'] += $count;
                }
            }

            if ($st === TransferStatus::COMPLETED->value) {
                $stats['completedCount'] += $count;
            }

            if ($st === TransferStatus::DISCREPANCY->value || (bool) $agg->has_discrepancy) {
                $stats['discrepancyCount'] += $count;
            }
        }
        $stats['inTransitValuation'] = round($stats['inTransitValuation'], 2);

        // 2. Paginated list query (never loads child items)
        $listQuery = (clone $baseScope)
            ->with([
                'sourceBranch:branch_id,branch_name,branch_code',
                'destinationBranch:branch_id,branch_name,branch_code',
            ])
            ->withCount('items');

        if ($perspective === 'action_needed' && $activeBranchId !== null) {
            $listQuery->where(function ($q) use ($activeBranchId, $openRequestStatuses, $transitStatuses) {
                $q->where(function ($sq) use ($activeBranchId, $openRequestStatuses) {
                    $sq->where('source_branch_id', $activeBranchId)->whereIn('status', $openRequestStatuses);
                })->orWhere(function ($dq) use ($activeBranchId, $transitStatuses) {
                    $dq->where('destination_branch_id', $activeBranchId)->whereIn('status', $transitStatuses);
                });
            });
        } elseif ($perspective === 'to_fulfill') {
            if (!$isConsolidated && $activeBranchId !== null) {
                $listQuery->where('source_branch_id', $activeBranchId);
            }
            $listQuery->whereIn('status', $openRequestStatuses);
        } elseif ($perspective === 'to_receive') {
            if (!$isConsolidated && $activeBranchId !== null) {
                $listQuery->where('destination_branch_id', $activeBranchId);
            }
            $listQuery->whereIn('status', $transitStatuses);
        } elseif ($perspective === 'my_requests') {
            if (!$isConsolidated && $activeBranchId !== null) {
                $listQuery->where('destination_branch_id', $activeBranchId);
            }
            $listQuery->whereIn('transfer_type', [
                TransferType::BRANCH_REQUEST->value,
                TransferType::BACKORDER_REQUEST->value,
                TransferType::COMMISSARY_REPLENISHMENT->value,
            ]);
        }

        if ($status !== '' && $status !== 'ALL') {
            if ($status === 'REQUESTED') {
                $listQuery->whereIn('status', [TransferStatus::REQUESTED->value, TransferStatus::PENDING->value]);
            } elseif ($status === 'IN_TRANSIT') {
                $listQuery->whereIn('status', $transitStatuses);
            } else {
                $listQuery->where('status', $status);
            }
        }

        if ($transferType !== '' && $transferType !== 'ALL') {
            $listQuery->where('transfer_type', $transferType);
        }

        if ($branchFilter !== null) {
            $listQuery->where(function ($q) use ($branchFilter) {
                $q->where('source_branch_id', $branchFilter)
                  ->orWhere('destination_branch_id', $branchFilter);
            });
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $listQuery->where(function ($q) use ($like) {
                $q->where('transfer_number', 'like', $like)
                  ->orWhere('receipt_number', 'like', $like)
                  ->orWhere('source_location', 'like', $like)
                  ->orWhere('destination_location', 'like', $like)
                  ->orWhere('waybill_number', 'like', $like)
                  ->orWhere('carrier_name', 'like', $like)
                  ->orWhereHas('items', function ($iq) use ($like) {
                      $iq->where('sku', 'like', $like)->orWhere('item_name', 'like', $like);
                  });
            });
        }

        $paginator = $listQuery->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'transfers' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => $stats,
        ];
    }

    /**
     * Load a single transfer with its line items & live source/destination balances.
     */
    public function getTransferDetail(int $transferId): array
    {
        $transfer = InternalTransfer::with([
            'items',
            'sourceBranch:branch_id,branch_name,branch_code,branch_address_line_1',
            'destinationBranch:branch_id,branch_name,branch_code,branch_address_line_1',
            'parentTransfer:id,transfer_number',
            'backorders:id,parent_transfer_id,transfer_number,status,total_requested_qty',
        ])->findOrFail($transferId);

        $itemIds = $transfer->items->pluck('inventory_item_id')->filter()->map(fn ($id) => (int) $id)->all();

        $sourceStock = $transfer->source_branch_id
            ? $this->branchStockService->getBranchStockMap((int) $transfer->source_branch_id, $itemIds)
            : [];
        $destStock = $transfer->destination_branch_id
            ? $this->branchStockService->getBranchStockMap((int) $transfer->destination_branch_id, $itemIds)
            : [];

        return [
            'transfer' => $transfer,
            'sourceStock' => $sourceStock,
            'destinationStock' => $destStock,
        ];
    }

    /**
     * 1-click low-stock suggestions for Pull Requests: indexed join capped at 50 rows.
     */
    public function getLowStockSuggestions(int $sourceBranchId, int $destBranchId, int $limit = 50): array
    {
        if ($sourceBranchId === $destBranchId) {
            throw ValidationException::withMessages([
                'destination_branch_id' => ['Source and destination branches must be different.'],
            ]);
        }

        $rows = DB::table('item_master as im')
            ->join('branch_stock_balances as src', function ($join) use ($sourceBranchId) {
                $join->on('src.inventory_item_id', '=', 'im.id')
                     ->where('src.branch_id', '=', $sourceBranchId);
            })
            ->leftJoin('branch_stock_balances as dst', function ($join) use ($destBranchId) {
                $join->on('dst.inventory_item_id', '=', 'im.id')
                     ->where('dst.branch_id', '=', $destBranchId);
            })
            ->where('im.is_active', true)
            ->whereRaw('(src.on_hand_qty - src.reserved_qty) > 0')
            ->where(function ($q) {
                $q->where('im.min_stock', '>', 0)
                  ->whereRaw('COALESCE(dst.on_hand_qty, 0) < im.min_stock');
            })
            ->select([
                'im.id as inventory_item_id',
                'im.sku',
                'im.name as item_name',
                'im.category',
                'im.uom',
                'im.cost_price',
                'im.min_stock',
                'im.max_stock',
                'im.storage_location',
                DB::raw('COALESCE(dst.on_hand_qty, 0) as dest_on_hand'),
                DB::raw('CASE WHEN (src.on_hand_qty - src.reserved_qty) > 0 THEN (src.on_hand_qty - src.reserved_qty) ELSE 0 END as source_available'),
            ])
            ->orderByRaw('(im.min_stock - COALESCE(dst.on_hand_qty, 0)) DESC')
            ->limit(min(50, max(1, $limit)))
            ->get();

        return $rows->map(function ($r) {
            $targetMax = (float) $r->max_stock > (float) $r->min_stock
                ? (float) $r->max_stock
                : ((float) $r->min_stock * 2);
            $deficit = max(1.0, round($targetMax - (float) $r->dest_on_hand, 2));
            $sourceAvail = (float) $r->source_available;
            $suggested = round(min($deficit, $sourceAvail), 2);

            return [
                'inventory_item_id' => (int) $r->inventory_item_id,
                'sku' => $r->sku,
                'item_name' => $r->item_name,
                'category' => $r->category,
                'uom' => $r->uom,
                'unit_cost' => (float) $r->cost_price,
                'dest_on_hand' => (float) $r->dest_on_hand,
                'min_stock' => (float) $r->min_stock,
                'source_available' => $sourceAvail,
                'suggested_qty' => $suggested,
                'storage_location' => $r->storage_location,
            ];
        })->filter(fn ($r) => $r['suggested_qty'] > 0)->values()->all();
    }

    /**
     * Create a Branch Stock Request (Pull: Destination Branch requests stock from Source Branch).
     */
    public function createRequest(array $data, ?User $actor): InternalTransfer
    {
        if (!empty($data['idempotency_key'])) {
            $existing = InternalTransfer::with(['items', 'sourceBranch', 'destinationBranch'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $sourceBranch = Branch::findOrFail((int) $data['source_branch_id']);
        $destBranch = Branch::findOrFail((int) $data['destination_branch_id']);

        if ((int) $sourceBranch->branch_id === (int) $destBranch->branch_id) {
            throw ValidationException::withMessages([
                'destination_branch_id' => ['Source branch and destination branch cannot be the same.'],
            ]);
        }

        $this->assertBranchSupplyPolicy($sourceBranch, $destBranch);

        $consolidatedItems = $this->normalizeAndValidateItems($data['items'], 'requested_qty');
        $isDraft = (bool) ($data['save_as_draft'] ?? false);
        $status = $isDraft ? TransferStatus::DRAFT->value : TransferStatus::REQUESTED->value;
        $actorName = $this->resolveActorName($actor, $data['requested_by'] ?? 'Branch Requester');

        return DB::transaction(function () use ($data, $sourceBranch, $destBranch, $consolidatedItems, $status, $actorName) {
            $transferNumber = $this->nextSequenceNumber('REQ-TRF');
            $itemIds = array_column($consolidatedItems, 'inventory_item_id');
            $sourceStockMap = $this->branchStockService->getBranchStockMap((int) $sourceBranch->branch_id, $itemIds);

            $totalRequested = 0.0;
            $totalValuation = 0.0;
            $totalInternalBilled = 0.0;
            $headerPricing = $this->accountingService->computeLinePricing($sourceBranch, 1.0, 1.0);
            foreach ($consolidatedItems as $idx => $line) {
                $pricing = $this->accountingService->computeLinePricing($sourceBranch, $line['unit_cost'], $line['base_qty']);
                $consolidatedItems[$idx]['internal_unit_price'] = $pricing['internal_unit_price'];
                $consolidatedItems[$idx]['internal_line_total'] = $pricing['internal_line_total'];
                $totalRequested += $line['qty'];
                $totalValuation += ($line['base_qty'] * $line['unit_cost']);
                $totalInternalBilled += $pricing['internal_line_total'];
            }

            $transfer = InternalTransfer::create([
                'company_id' => $destBranch->company_id ?? $sourceBranch->company_id,
                'transfer_number' => $transferNumber,
                'transfer_type' => TransferType::BRANCH_REQUEST->value,
                'status' => $status,
                'priority' => $this->resolvePriority($data['priority'] ?? 'NORMAL'),
                'source_location' => $sourceBranch->branch_name,
                'source_branch_id' => (int) $sourceBranch->branch_id,
                'destination_location' => $destBranch->branch_name,
                'destination_branch_id' => (int) $destBranch->branch_id,
                'requisition_reference' => $data['requisition_reference'] ?? null,
                'reason_code' => $data['reason_code'] ?? 'Branch Replenishment Request',
                'requested_by' => $actorName,
                'required_at' => $data['required_at'] ?? null,
                'total_items_count' => count($consolidatedItems),
                'total_requested_qty' => round($totalRequested, 2),
                'total_approved_qty' => 0.00,
                'total_transferred_qty' => 0.00,
                'total_received_qty' => 0.00,
                'total_variance_qty' => 0.00,
                'total_valuation' => round($totalValuation, 2),
                'pricing_mode' => $headerPricing['pricing_mode'],
                'markup_pct' => $headerPricing['markup_pct'],
                'total_internal_billed' => round($totalInternalBilled, 2),
                'settlement_status' => 'UNBILLED',
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $now = now();
            $rows = [];
            foreach ($consolidatedItems as $line) {
                $avail = $sourceStockMap[$line['inventory_item_id']]['available_qty'] ?? 0.0;
                $rows[] = [
                    'internal_transfer_id' => $transfer->id,
                    'inventory_item_id' => $line['inventory_item_id'],
                    'sku' => $line['sku'],
                    'item_name' => $line['item_name'],
                    'category' => $line['category'],
                    'uom' => $line['uom'],
                    'uom_multiplier' => $line['uom_multiplier'],
                    'base_uom' => $line['base_uom'],
                    'source_available_stock' => round($avail, 2),
                    'requested_qty' => round($line['qty'], 2),
                    'approved_qty' => 0.00,
                    'reserved_qty' => 0.00,
                    'transferred_qty' => 0.00,
                    'received_qty' => 0.00,
                    'variance_qty' => 0.00,
                    'unit_cost' => round($line['unit_cost'], 2),
                    'total_cost' => round($line['base_qty'] * $line['unit_cost'], 2),
                    'internal_unit_price' => $line['internal_unit_price'],
                    'internal_line_total' => $line['internal_line_total'],
                    'storage_location' => $line['storage_location'],
                    'notes' => $line['notes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            InternalTransferItem::insert($rows);

            return $transfer->load(['items', 'sourceBranch', 'destinationBranch']);
        });
    }

    /**
     * Direct Transfer (Push: Source Branch dispatches stock directly to Destination Branch -> IN_TRANSIT).
     * Destination MUST still explicitly accept via receive().
     */
    public function createDirectPush(array $data, ?User $actor, ?int $activeBranchId, bool $isConsolidated): InternalTransfer
    {
        if (!empty($data['idempotency_key'])) {
            $existing = InternalTransfer::with(['items', 'sourceBranch', 'destinationBranch'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $sourceBranch = $this->resolveBranchFromPayload(
            $data['source_branch_id'] ?? null,
            $data['source_location'] ?? null,
            true
        );
        $destBranch = $this->resolveBranchFromPayload(
            $data['destination_branch_id'] ?? null,
            $data['destination_location'] ?? null,
            false
        );

        if ((int) $sourceBranch->branch_id === (int) $destBranch->branch_id) {
            throw ValidationException::withMessages([
                'destination_branch_id' => ['Source branch and destination branch cannot be the same.'],
            ]);
        }

        if (!$isConsolidated && $activeBranchId !== null && (int) $sourceBranch->branch_id !== $activeBranchId) {
            throw new HttpException(403, "Custody Violation: Active branch can only push direct transfers originating from itself ({$sourceBranch->branch_name}).");
        }

        $this->assertBranchSupplyPolicy($sourceBranch, $destBranch);

        $consolidatedItems = $this->normalizeAndValidateItems($data['items'], 'transferred_qty');
        $actorName = $this->resolveActorName($actor, $data['dispatched_by'] ?? 'Warehouse Dispatcher');

        return DB::transaction(function () use ($data, $sourceBranch, $destBranch, $consolidatedItems, $actorName) {
            $transferNumber = $this->nextSequenceNumber('DIR-TRF');

            $totalTransferred = 0.0;
            $totalValuation = 0.0;
            $totalInternalBilled = 0.0;
            $headerPricing = $this->accountingService->computeLinePricing($sourceBranch, 1.0, 1.0);
            foreach ($consolidatedItems as $idx => $line) {
                $pricing = $this->accountingService->computeLinePricing($sourceBranch, $line['unit_cost'], $line['base_qty']);
                $consolidatedItems[$idx]['internal_unit_price'] = $pricing['internal_unit_price'];
                $consolidatedItems[$idx]['internal_line_total'] = $pricing['internal_line_total'];
                $totalTransferred += $line['qty'];
                $totalValuation += ($line['base_qty'] * $line['unit_cost']);
                $totalInternalBilled += $pricing['internal_line_total'];
            }

            $transfer = InternalTransfer::create([
                'company_id' => $sourceBranch->company_id ?? $destBranch->company_id,
                'transfer_number' => $transferNumber,
                'transfer_type' => TransferType::CUSTOM_PUSH->value,
                'status' => TransferStatus::IN_TRANSIT->value,
                'priority' => $this->resolvePriority($data['priority'] ?? 'NORMAL'),
                'source_location' => $sourceBranch->branch_name,
                'source_branch_id' => (int) $sourceBranch->branch_id,
                'destination_location' => $destBranch->branch_name,
                'destination_branch_id' => (int) $destBranch->branch_id,
                'reason_code' => $data['reason_code'] ?? 'Commissary Bulk Batch Push',
                'dispatched_by' => $actorName,
                'carrier_name' => $data['carrier_name'] ?? null,
                'driver_plate' => $data['driver_plate'] ?? null,
                'waybill_number' => $data['waybill_number'] ?? null,
                'required_at' => $data['required_at'] ?? null,
                'dispatched_at' => now(),
                'total_items_count' => count($consolidatedItems),
                'total_requested_qty' => round($totalTransferred, 2),
                'total_approved_qty' => round($totalTransferred, 2),
                'total_transferred_qty' => round($totalTransferred, 2),
                'total_received_qty' => 0.00,
                'total_variance_qty' => 0.00,
                'total_valuation' => round($totalValuation, 2),
                'pricing_mode' => $headerPricing['pricing_mode'],
                'markup_pct' => $headerPricing['markup_pct'],
                'total_internal_billed' => round($totalInternalBilled, 2),
                'settlement_status' => 'UNBILLED',
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $now = now();
            $rows = [];
            $dispatchLines = [];
            foreach ($consolidatedItems as $line) {
                $rows[] = [
                    'internal_transfer_id' => $transfer->id,
                    'inventory_item_id' => $line['inventory_item_id'],
                    'sku' => $line['sku'],
                    'item_name' => $line['item_name'],
                    'category' => $line['category'],
                    'uom' => $line['uom'],
                    'uom_multiplier' => $line['uom_multiplier'],
                    'base_uom' => $line['base_uom'],
                    'source_available_stock' => 0.00,
                    'requested_qty' => round($line['qty'], 2),
                    'approved_qty' => round($line['qty'], 2),
                    'reserved_qty' => 0.00,
                    'transferred_qty' => round($line['qty'], 2),
                    'received_qty' => 0.00,
                    'variance_qty' => 0.00,
                    'unit_cost' => round($line['unit_cost'], 2),
                    'total_cost' => round($line['base_qty'] * $line['unit_cost'], 2),
                    'internal_unit_price' => $line['internal_unit_price'],
                    'internal_line_total' => $line['internal_line_total'],
                    'storage_location' => $line['storage_location'],
                    'notes' => $line['notes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $dispatchLines[] = [
                    'item_id' => $line['inventory_item_id'],
                    'sku' => $line['sku'],
                    'item_name' => $line['item_name'],
                    'ship_base_qty' => $line['base_qty'],
                    'release_reserved_base_qty' => 0.0,
                    'unit_cost' => $line['unit_cost'],
                    'batch_lot_no' => null,
                    'storage_location' => $line['storage_location'] ?: $sourceBranch->branch_name,
                ];
            }

            InternalTransferItem::insert($rows);

            $this->branchStockService->dispatchOut(
                (int) $sourceBranch->branch_id,
                (int) $destBranch->branch_id,
                $transfer,
                $dispatchLines,
                $actorName
            );

            $this->accountingService->postDispatchJournal($transfer, $actorName);

            return $transfer->load(['items', 'sourceBranch', 'destinationBranch']);
        });
    }

    /**
     * Source Branch approves a request (reserves stock) and optionally dispatches immediately to IN_TRANSIT.
     * NEVER auto-receives at destination.
     *
     * @return array{transfer: InternalTransfer, backorder: ?InternalTransfer}
     */
    public function approve(int $transferId, array $data, ?User $actor, ?int $activeBranchId, bool $isConsolidated): array
    {
        return DB::transaction(function () use ($transferId, $data, $actor, $activeBranchId, $isConsolidated) {
            /** @var InternalTransfer $transfer */
            $transfer = InternalTransfer::where('id', $transferId)->lockForUpdate()->firstOrFail();
            $currentStatus = TransferStatus::from($transfer->status);

            if (!$currentStatus->isApprovable()) {
                throw ValidationException::withMessages([
                    'transfer_id' => ["Transfer {$transfer->transfer_number} cannot be approved from {$transfer->status} status."],
                ]);
            }

            // Assign source branch if legacy ReplenishmentEngine draft had null source_branch_id
            if ($transfer->source_branch_id === null && !empty($data['source_branch_id'])) {
                $src = Branch::findOrFail((int) $data['source_branch_id']);
                if ((int) $src->branch_id === (int) $transfer->destination_branch_id) {
                    throw ValidationException::withMessages([
                        'source_branch_id' => ['Source branch and destination branch cannot be the same.'],
                    ]);
                }
                $transfer->source_branch_id = (int) $src->branch_id;
                $transfer->source_location = $src->branch_name;
            }

            $this->assertSourceCustody($transfer, $activeBranchId, $isConsolidated);

            $transfer->load('items');
            $actorName = $this->resolveActorName($actor, $data['approved_by'] ?? 'Source Branch Manager');

            $qtyUpdates = [];
            foreach (($data['items'] ?? []) as $row) {
                $qtyUpdates[(string) $row['sku']] = (float) ($row['approved_qty'] ?? $row['transferred_qty'] ?? 0);
            }

            $reserveBaseByItem = [];
            $totalApproved = 0.0;
            $hasPositiveLine = false;

            foreach ($transfer->items as $line) {
                $approvedQty = array_key_exists($line->sku, $qtyUpdates)
                    ? max(0.0, round($qtyUpdates[$line->sku], 2))
                    : (float) $line->requested_qty;

                if ($approvedQty > (float) $line->requested_qty + 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => ["Approved quantity ({$approvedQty}) for {$line->sku} cannot exceed requested quantity ({$line->requested_qty})."],
                    ]);
                }

                $multiplier = max(0.0001, (float) ($line->uom_multiplier ?: 1.0));
                $baseQty = round($approvedQty * $multiplier, 4);
                $line->approved_qty = $approvedQty;
                $line->reserved_qty = $approvedQty;
                $line->save();

                if ($approvedQty > 0) {
                    $hasPositiveLine = true;
                    $itemId = (int) $line->inventory_item_id;
                    $reserveBaseByItem[$itemId] = ($reserveBaseByItem[$itemId] ?? 0.0) + $baseQty;
                    $totalApproved += $approvedQty;
                }
            }

            if (!$hasPositiveLine) {
                throw ValidationException::withMessages([
                    'items' => ['At least one item must have an approved quantity greater than zero.'],
                ]);
            }

            $sourceBranchId = (int) $transfer->source_branch_id;
            $this->branchStockService->reserve($sourceBranchId, $reserveBaseByItem, $transfer->company_id);

            $transfer->update([
                'status' => TransferStatus::APPROVED->value,
                'approved_by' => $actorName,
                'approved_at' => now(),
                'total_approved_qty' => round($totalApproved, 2),
                'source_branch_id' => $sourceBranchId,
                'source_location' => $transfer->source_location,
            ]);

            if (!empty($data['dispatch_immediately'])) {
                $dispatchItems = $transfer->items->map(fn (InternalTransferItem $it) => [
                    'sku' => $it->sku,
                    'transferred_qty' => (float) $it->approved_qty,
                ])->all();

                return $this->performDispatch($transfer, array_merge($data, ['items' => $dispatchItems]), $actorName);
            }

            return [
                'transfer' => $transfer->fresh(['items', 'sourceBranch', 'destinationBranch', 'parentTransfer', 'backorders']),
                'backorder' => null,
            ];
        });
    }

    /**
     * Source Branch rejects a transfer request (releases reservation if already approved).
     */
    public function reject(int $transferId, string $reason, ?User $actor, ?int $activeBranchId, bool $isConsolidated): InternalTransfer
    {
        return DB::transaction(function () use ($transferId, $reason, $actor, $activeBranchId, $isConsolidated) {
            /** @var InternalTransfer $transfer */
            $transfer = InternalTransfer::where('id', $transferId)->lockForUpdate()->firstOrFail();
            $currentStatus = TransferStatus::from($transfer->status);

            if (!$currentStatus->canTransitionTo(TransferStatus::REJECTED)) {
                throw ValidationException::withMessages([
                    'transfer_id' => ["Transfer {$transfer->transfer_number} cannot be rejected from {$transfer->status} status."],
                ]);
            }

            $this->assertSourceCustody($transfer, $activeBranchId, $isConsolidated);

            $transfer->load('items');
            if ($currentStatus === TransferStatus::APPROVED && $transfer->source_branch_id) {
                $releaseByItem = [];
                foreach ($transfer->items as $line) {
                    if ((float) $line->reserved_qty > 0 && $line->inventory_item_id) {
                        $mult = max(0.0001, (float) ($line->uom_multiplier ?: 1.0));
                        $id = (int) $line->inventory_item_id;
                        $releaseByItem[$id] = ($releaseByItem[$id] ?? 0.0) + round((float) $line->reserved_qty * $mult, 4);
                        $line->reserved_qty = 0.00;
                        $line->save();
                    }
                }
                if (!empty($releaseByItem)) {
                    $this->branchStockService->releaseReservation((int) $transfer->source_branch_id, $releaseByItem);
                }
            }

            $actorName = $this->resolveActorName($actor, 'Source Branch Manager');
            $transfer->update([
                'status' => TransferStatus::REJECTED->value,
                'rejected_by' => $actorName,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'notes' => trim(($transfer->notes ? $transfer->notes . ' | ' : '') . "Rejected: {$reason}"),
            ]);

            return $transfer->fresh(['items', 'sourceBranch', 'destinationBranch']);
        });
    }

    /**
     * Source Branch dispatches a DRAFT/PENDING/REQUESTED/APPROVED transfer -> IN_TRANSIT.
     * Supports partial dispatch + optional child backorder creation.
     *
     * @return array{transfer: InternalTransfer, backorder: ?InternalTransfer}
     */
    public function dispatch(int $transferId, array $data, ?User $actor, ?int $activeBranchId, bool $isConsolidated): array
    {
        return DB::transaction(function () use ($transferId, $data, $actor, $activeBranchId, $isConsolidated) {
            /** @var InternalTransfer $transfer */
            $transfer = InternalTransfer::where('id', $transferId)->lockForUpdate()->firstOrFail();
            $currentStatus = TransferStatus::from($transfer->status);

            if (!$currentStatus->isDispatchable()) {
                throw ValidationException::withMessages([
                    'transfer_id' => ["Transfer {$transfer->transfer_number} is already in {$transfer->status} status and cannot be dispatched."],
                ]);
            }

            if ($transfer->source_branch_id === null && !empty($data['source_branch_id'])) {
                $src = Branch::findOrFail((int) $data['source_branch_id']);
                $transfer->source_branch_id = (int) $src->branch_id;
                $transfer->source_location = $src->branch_name;
            }

            $this->assertSourceCustody($transfer, $activeBranchId, $isConsolidated);

            $actorName = $this->resolveActorName($actor, $data['dispatched_by'] ?? 'Source Warehouse Lead');

            return $this->performDispatch($transfer, $data, $actorName);
        });
    }

    /**
     * Destination Branch explicitly accepts & receives an IN_TRANSIT or PARTIALLY_RECEIVED transfer.
     */
    public function receive(int $transferId, array $data, ?User $actor, ?int $activeBranchId, bool $isConsolidated): InternalTransfer
    {
        return DB::transaction(function () use ($transferId, $data, $actor, $activeBranchId, $isConsolidated) {
            /** @var InternalTransfer $transfer */
            $transfer = InternalTransfer::where('id', $transferId)->lockForUpdate()->firstOrFail();
            $currentStatus = TransferStatus::from($transfer->status);

            if (!$currentStatus->isReceivable()) {
                throw ValidationException::withMessages([
                    'transfer_id' => ["Transfer {$transfer->transfer_number} cannot be received from {$transfer->status} status. Source branch must dispatch it first."],
                ]);
            }

            $this->assertDestinationCustody($transfer, $activeBranchId, $isConsolidated);

            if ($transfer->source_branch_id === null || $transfer->destination_branch_id === null) {
                throw ValidationException::withMessages([
                    'transfer_id' => ['Transfer is missing canonical source or destination branch assignment.'],
                ]);
            }

            $transfer->load('items');
            $actorName = $this->resolveActorName($actor, $data['received_by'] ?? 'Destination Receiving Lead');

            if (empty($transfer->receipt_number)) {
                $transfer->receipt_number = $this->nextSequenceNumber('TRF-REC', 'receipt_number');
            }

            $submittedBySku = [];
            foreach (($data['items'] ?? []) as $row) {
                $submittedBySku[(string) $row['sku']] = $row;
            }

            $allowPartial = (bool) ($data['allow_partial'] ?? false);
            $receiveLines = [];
            $totalReceived = 0.0;
            $totalVariance = 0.0;
            $receivedBaseCost = 0.0;
            $receivedInternalBilled = 0.0;
            $varianceBaseCost = 0.0;
            $anyPendingRemaining = false;
            $anyMovementThisCall = false;

            foreach ($transfer->items as $line) {
                $shipped = (float) $line->transferred_qty;
                $alreadyRecv = (float) $line->received_qty;
                $alreadyVar = (float) $line->variance_qty;
                $remaining = max(0.0, round($shipped - $alreadyRecv - $alreadyVar, 2));

                if ($remaining <= 0) {
                    $totalReceived += $alreadyRecv;
                    $totalVariance += $alreadyVar;
                    continue;
                }

                $sub = $submittedBySku[$line->sku] ?? null;
                if ($sub !== null) {
                    $recvDelta = round((float) ($sub['received_qty'] ?? 0), 2);
                    $varDelta = round((float) ($sub['variance_qty'] ?? 0), 2);
                    $varReason = isset($sub['variance_reason']) && $sub['variance_reason'] !== '' ? (string) $sub['variance_reason'] : null;

                    if (!$allowPartial && ($recvDelta + $varDelta) < $remaining - 0.0001 && !isset($sub['variance_qty'])) {
                        // If caller didn't supply explicit variance_qty on full close, treat shortfall as variance if reason provided
                        $varDelta = round($remaining - $recvDelta, 2);
                    }
                } else {
                    // Legacy 1-click full acceptance payload without per-line array
                    $recvDelta = empty($submittedBySku) ? $remaining : 0.0;
                    $varDelta = 0.0;
                    $varReason = null;
                }

                if ($recvDelta < 0 || $varDelta < 0) {
                    throw ValidationException::withMessages([
                        'items' => ["Received and variance quantities cannot be negative for {$line->sku}."],
                    ]);
                }

                if (($recvDelta + $varDelta) > $remaining + 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => ["Total received + variance ({$recvDelta} + {$varDelta}) for {$line->sku} exceeds remaining in-transit quantity ({$remaining})."],
                    ]);
                }

                if ($varDelta > 0) {
                    if (!$varReason || VarianceReason::tryFrom($varReason) === null) {
                        throw ValidationException::withMessages([
                            'items' => ["A valid discrepancy reason code is required for shortage/damage on {$line->sku}."],
                        ]);
                    }
                }

                if ($recvDelta > 0 || $varDelta > 0) {
                    $anyMovementThisCall = true;
                    $mult = max(0.0001, (float) ($line->uom_multiplier ?: 1.0));
                    $recvBase = round($recvDelta * $mult, 4);
                    $varBase = round($varDelta * $mult, 4);
                    $unitCost = (float) $line->unit_cost;
                    $internalUnitPrice = (float) ($line->internal_unit_price > 0 ? $line->internal_unit_price : $unitCost);

                    $receivedBaseCost += ($recvBase * $unitCost);
                    $receivedInternalBilled += ($recvBase * $internalUnitPrice);
                    $varianceBaseCost += ($varBase * $unitCost);

                    $receiveLines[] = [
                        'item_id' => (int) $line->inventory_item_id,
                        'sku' => $line->sku,
                        'item_name' => $line->item_name,
                        'receive_base_qty' => $recvBase,
                        'variance_base_qty' => $varBase,
                        'variance_reason' => $varReason,
                        'unit_cost' => $internalUnitPrice,
                        'batch_lot_no' => $line->batch_lot_no,
                        'storage_location' => $transfer->destination_location,
                    ];

                    $line->received_qty = round($alreadyRecv + $recvDelta, 2);
                    $line->variance_qty = round($alreadyVar + $varDelta, 2);
                    if ($varReason) {
                        $line->variance_reason = $varReason;
                    }
                    $line->save();
                }

                $newRemaining = max(0.0, round($shipped - (float) $line->received_qty - (float) $line->variance_qty, 2));
                if ($newRemaining > 0.0001) {
                    $anyPendingRemaining = true;
                }

                $totalReceived += (float) $line->received_qty;
                $totalVariance += (float) $line->variance_qty;
            }

            if (!$anyMovementThisCall) {
                throw ValidationException::withMessages([
                    'items' => ['At least one line item must record an accepted or variance quantity greater than zero.'],
                ]);
            }

            $this->branchStockService->receiveIn(
                (int) $transfer->source_branch_id,
                (int) $transfer->destination_branch_id,
                $transfer,
                $receiveLines,
                $actorName
            );

            $finalStatus = $anyPendingRemaining
                ? TransferStatus::PARTIALLY_RECEIVED->value
                : ($totalVariance > 0 ? TransferStatus::DISCREPANCY->value : TransferStatus::COMPLETED->value);

            $noteLine = !empty($data['notes'])
                ? "Receipt [{$transfer->receipt_number}]: {$data['notes']}"
                : "Accepted at destination [{$transfer->receipt_number}]";

            $transfer->update([
                'status' => $finalStatus,
                'receipt_number' => $transfer->receipt_number,
                'received_by' => $actorName,
                'received_at' => now(),
                'total_received_qty' => round($totalReceived, 2),
                'total_variance_qty' => round($totalVariance, 2),
                'has_discrepancy' => $totalVariance > 0,
                'notes' => trim(($transfer->notes ? $transfer->notes . ' | ' : '') . $noteLine),
            ]);

            $this->accountingService->postReceiptJournal(
                $transfer,
                round($receivedBaseCost, 2),
                round($receivedInternalBilled, 2),
                round($varianceBaseCost, 2),
                $actorName
            );

            return $transfer->fresh(['items', 'sourceBranch', 'destinationBranch', 'parentTransfer', 'backorders']);
        });
    }

    /**
     * Transactional cancellation with lockForUpdate() + automatic reservation release.
     */
    public function cancel(int $transferId, string $reason, ?User $actor): InternalTransfer
    {
        return DB::transaction(function () use ($transferId, $reason, $actor) {
            /** @var InternalTransfer $transfer */
            $transfer = InternalTransfer::where('id', $transferId)->lockForUpdate()->firstOrFail();
            $currentStatus = TransferStatus::from($transfer->status);

            if (!$currentStatus->isCancellable()) {
                throw ValidationException::withMessages([
                    'transfer_id' => ["Cannot cancel transfer {$transfer->transfer_number} in {$transfer->status} status."],
                ]);
            }

            $transfer->load('items');
            if ($currentStatus === TransferStatus::APPROVED && $transfer->source_branch_id) {
                $releaseByItem = [];
                foreach ($transfer->items as $line) {
                    if ((float) $line->reserved_qty > 0 && $line->inventory_item_id) {
                        $mult = max(0.0001, (float) ($line->uom_multiplier ?: 1.0));
                        $id = (int) $line->inventory_item_id;
                        $releaseByItem[$id] = ($releaseByItem[$id] ?? 0.0) + round((float) $line->reserved_qty * $mult, 4);
                        $line->reserved_qty = 0.00;
                        $line->save();
                    }
                }
                if (!empty($releaseByItem)) {
                    $this->branchStockService->releaseReservation((int) $transfer->source_branch_id, $releaseByItem);
                }
            }

            $actorName = $this->resolveActorName($actor, 'Warehouse Supervisor');
            $transfer->update([
                'status' => TransferStatus::CANCELLED->value,
                'cancelled_by' => $actorName,
                'cancelled_at' => now(),
                'notes' => trim(($transfer->notes ? $transfer->notes . ' | ' : '') . "Cancelled: {$reason}"),
            ]);

            return $transfer->fresh(['items', 'sourceBranch', 'destinationBranch']);
        });
    }

    /**
     * Internal shared dispatch execution inside an active DB transaction.
     *
     * @return array{transfer: InternalTransfer, backorder: ?InternalTransfer}
     */
    protected function performDispatch(InternalTransfer $transfer, array $data, string $actorName): array
    {
        if ($transfer->source_branch_id === null || $transfer->destination_branch_id === null) {
            throw ValidationException::withMessages([
                'source_branch_id' => ['Transfer must have both source and destination branches assigned before dispatch.'],
            ]);
        }

        if ((int) $transfer->source_branch_id === (int) $transfer->destination_branch_id) {
            throw ValidationException::withMessages([
                'destination_branch_id' => ['Source and destination branches cannot be the same.'],
            ]);
        }

        $transfer->load('items');
        $qtyBySku = [];
        foreach (($data['items'] ?? []) as $row) {
            $sku = (string) $row['sku'];
            $qtyBySku[$sku] = ($qtyBySku[$sku] ?? 0.0) + round((float) $row['transferred_qty'], 2);
        }

        $sourceBranch = Branch::find((int) $transfer->source_branch_id);
        $dispatchLines = [];
        $backorderLines = [];
        $totalDispatched = 0.0;
        $totalValuation = 0.0;
        $totalInternalBilled = 0.0;
        $backorderReqTotal = 0.0;
        $backorderValTotal = 0.0;

        foreach ($transfer->items as $line) {
            $shipQty = array_key_exists($line->sku, $qtyBySku)
                ? max(0.0, round($qtyBySku[$line->sku], 2))
                : 0.0;

            $requested = (float) $line->requested_qty;
            if ($requested > 0 && $shipQty > $requested + 0.0001) {
                throw ValidationException::withMessages([
                    'items' => ["Dispatch quantity ({$shipQty}) for {$line->sku} cannot exceed requested quantity ({$requested})."],
                ]);
            }

            $mult = max(0.0001, (float) ($line->uom_multiplier ?: 1.0));
            $shipBaseQty = round($shipQty * $mult, 4);
            $releaseReservedBase = round((float) $line->reserved_qty * $mult, 4);
            $unitCost = (float) $line->unit_cost;
            $pricing = $this->accountingService->computeLinePricing($sourceBranch, $unitCost, $shipBaseQty);

            $line->transferred_qty = $shipQty;
            $line->reserved_qty = 0.00;
            $line->total_cost = round($shipBaseQty * $unitCost, 2);
            $line->internal_unit_price = $pricing['internal_unit_price'];
            $line->internal_line_total = $pricing['internal_line_total'];
            $line->save();

            if ($shipQty > 0) {
                $dispatchLines[] = [
                    'item_id' => (int) $line->inventory_item_id,
                    'sku' => $line->sku,
                    'item_name' => $line->item_name,
                    'ship_base_qty' => $shipBaseQty,
                    'release_reserved_base_qty' => $releaseReservedBase,
                    'unit_cost' => $unitCost,
                    'batch_lot_no' => $line->batch_lot_no,
                    'storage_location' => $line->storage_location ?: $transfer->source_location,
                ];
                $totalDispatched += $shipQty;
                $totalValuation += ($shipBaseQty * $unitCost);
                $totalInternalBilled += $pricing['internal_line_total'];
            } elseif ($releaseReservedBase > 0 && $line->inventory_item_id) {
                $this->branchStockService->releaseReservation((int) $transfer->source_branch_id, [
                    (int) $line->inventory_item_id => $releaseReservedBase,
                ]);
            }

            $shortfall = max(0.0, round($requested - $shipQty, 2));
            if ($shortfall > 0.0001) {
                $shortfallBase = round($shortfall * $mult, 4);
                $boPricing = $this->accountingService->computeLinePricing($sourceBranch, $unitCost, $shortfallBase);
                $backorderLines[] = [
                    'inventory_item_id' => $line->inventory_item_id,
                    'sku' => $line->sku,
                    'item_name' => $line->item_name,
                    'category' => $line->category,
                    'uom' => $line->uom,
                    'uom_multiplier' => $mult,
                    'base_uom' => $line->base_uom,
                    'requested_qty' => $shortfall,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($shortfallBase * $unitCost, 2),
                    'internal_unit_price' => $boPricing['internal_unit_price'],
                    'internal_line_total' => $boPricing['internal_line_total'],
                    'storage_location' => $line->storage_location,
                ];
                $backorderReqTotal += $shortfall;
                $backorderValTotal += ($shortfallBase * $unitCost);
            }
        }

        if ($totalDispatched <= 0) {
            throw ValidationException::withMessages([
                'items' => ['At least one line item must have a dispatch quantity greater than zero.'],
            ]);
        }

        $this->branchStockService->dispatchOut(
            (int) $transfer->source_branch_id,
            (int) $transfer->destination_branch_id,
            $transfer,
            $dispatchLines,
            $actorName
        );

        $headerPricing = $this->accountingService->computeLinePricing($sourceBranch, 1.0, 1.0);
        $transfer->update([
            'status' => TransferStatus::IN_TRANSIT->value,
            'dispatched_by' => $actorName,
            'carrier_name' => $data['carrier_name'] ?? $transfer->carrier_name,
            'driver_plate' => $data['driver_plate'] ?? $transfer->driver_plate,
            'waybill_number' => $data['waybill_number'] ?? $transfer->waybill_number,
            'dispatched_at' => now(),
            'total_transferred_qty' => round($totalDispatched, 2),
            'total_valuation' => round($totalValuation, 2),
            'pricing_mode' => $headerPricing['pricing_mode'],
            'markup_pct' => $headerPricing['markup_pct'],
            'total_internal_billed' => round($totalInternalBilled, 2),
        ]);

        $this->accountingService->postDispatchJournal($transfer, $actorName);

        $backorder = null;
        if (!empty($data['create_backorder']) && !empty($backorderLines)) {
            $boNumber = $this->nextSequenceNumber('BO-TRF');
            $backorder = InternalTransfer::create([
                'company_id' => $transfer->company_id,
                'parent_transfer_id' => $transfer->id,
                'transfer_number' => $boNumber,
                'transfer_type' => TransferType::BACKORDER_REQUEST->value,
                'status' => TransferStatus::REQUESTED->value,
                'priority' => $transfer->priority,
                'source_location' => $transfer->source_location,
                'source_branch_id' => $transfer->source_branch_id,
                'destination_location' => $transfer->destination_location,
                'destination_branch_id' => $transfer->destination_branch_id,
                'requisition_reference' => $transfer->transfer_number,
                'reason_code' => 'Backorder Balance from ' . $transfer->transfer_number,
                'requested_by' => $transfer->requested_by ?? $actorName,
                'required_at' => $transfer->required_at,
                'total_items_count' => count($backorderLines),
                'total_requested_qty' => round($backorderReqTotal, 2),
                'total_approved_qty' => 0.00,
                'total_transferred_qty' => 0.00,
                'total_received_qty' => 0.00,
                'total_variance_qty' => 0.00,
                'total_valuation' => round($backorderValTotal, 2),
                'notes' => "Auto-split backorder for unfulfilled balance of {$transfer->transfer_number}.",
            ]);

            $now = now();
            $boRows = [];
            foreach ($backorderLines as $bl) {
                $boRows[] = array_merge($bl, [
                    'internal_transfer_id' => $backorder->id,
                    'source_available_stock' => 0.00,
                    'approved_qty' => 0.00,
                    'reserved_qty' => 0.00,
                    'transferred_qty' => 0.00,
                    'received_qty' => 0.00,
                    'variance_qty' => 0.00,
                    'notes' => "Backorder balance from {$transfer->transfer_number}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            InternalTransferItem::insert($boRows);
            $backorder->load(['items', 'sourceBranch', 'destinationBranch']);
        }

        return [
            'transfer' => $transfer->fresh(['items', 'sourceBranch', 'destinationBranch', 'parentTransfer', 'backorders']),
            'backorder' => $backorder,
        ];
    }

    protected function assertSourceCustody(InternalTransfer $transfer, ?int $activeBranchId, bool $isConsolidated): void
    {
        if (!$isConsolidated && $activeBranchId !== null && $transfer->source_branch_id !== null && (int) $transfer->source_branch_id !== $activeBranchId) {
            throw new HttpException(403, "Custody Violation: Only the source branch ({$transfer->source_location}) can approve, reject, or dispatch this transfer.");
        }
    }

    protected function assertDestinationCustody(InternalTransfer $transfer, ?int $activeBranchId, bool $isConsolidated): void
    {
        if (!$isConsolidated && $activeBranchId !== null && $transfer->destination_branch_id !== null && (int) $transfer->destination_branch_id !== $activeBranchId) {
            throw new HttpException(403, "Custody Violation: Only the destination branch ({$transfer->destination_location}) can accept and confirm receipt of this transfer.");
        }
    }

    /**
     * Consolidate duplicate SKUs in request payload, resolve InventoryItem IDs & UOM multipliers.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeAndValidateItems(array $rawItems, string $qtyField): array
    {
        $skus = array_values(array_unique(array_map(fn ($it) => trim((string) ($it['sku'] ?? '')), $rawItems)));
        $masterBySku = InventoryItem::query()
            ->whereIn('sku', $skus)
            ->get()
            ->keyBy('sku');

        $grouped = [];
        foreach ($rawItems as $raw) {
            $sku = trim((string) ($raw['sku'] ?? ''));
            $master = $masterBySku->get($sku);
            if (!$master) {
                throw ValidationException::withMessages([
                    'items' => ["Item with SKU '{$sku}' not found in Item Master."],
                ]);
            }

            $qty = round((float) ($raw[$qtyField] ?? 0), 2);
            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    'items' => ["Quantity for SKU '{$sku}' must be greater than zero."],
                ]);
            }

            $multiplier = max(0.0001, (float) ($raw['uom_multiplier'] ?? 1.0));
            $uom = trim((string) ($raw['uom'] ?? $master->uom ?: 'PCS'));
            $baseUom = $master->uom ?: $uom;
            $unitCost = isset($raw['unit_cost']) ? (float) $raw['unit_cost'] : (float) $master->cost_price;

            if (isset($grouped[$sku])) {
                $grouped[$sku]['qty'] = round($grouped[$sku]['qty'] + $qty, 2);
                $grouped[$sku]['base_qty'] = round($grouped[$sku]['qty'] * $grouped[$sku]['uom_multiplier'], 4);
            } else {
                $grouped[$sku] = [
                    'inventory_item_id' => (int) $master->id,
                    'sku' => $master->sku,
                    'item_name' => $raw['item_name'] ?? $master->name,
                    'category' => $raw['category'] ?? $master->category,
                    'uom' => $uom,
                    'uom_multiplier' => $multiplier,
                    'base_uom' => $baseUom,
                    'qty' => $qty,
                    'base_qty' => round($qty * $multiplier, 4),
                    'unit_cost' => $unitCost,
                    'storage_location' => $raw['storage_location'] ?? $master->storage_location,
                    'notes' => $raw['notes'] ?? null,
                ];
            }
        }

        return array_values($grouped);
    }

    protected function resolveBranchFromPayload(?int $branchId, ?string $locationName, bool $allowDefaultFallback): Branch
    {
        if ($branchId !== null && $branchId > 0) {
            return Branch::findOrFail($branchId);
        }

        if ($locationName) {
            $matched = Branch::where('branch_name', $locationName)->first();
            if ($matched) {
                return $matched;
            }
        }

        if ($allowDefaultFallback) {
            $primary = Branch::active()
                ->orderByRaw("CASE WHEN LOWER(branch_name) LIKE '%commissary%' OR LOWER(branch_name) LIKE '%central%' OR LOWER(branch_name) LIKE '%main%' THEN 0 ELSE 1 END")
                ->orderBy('branch_id')
                ->first();
            if ($primary) {
                return $primary;
            }
        }

        throw ValidationException::withMessages([
            'branch_id' => ['A valid active branch must be selected.'],
        ]);
    }

    /**
     * Collision-safe sequential reference numbering under DB transaction lock.
     */
    protected function nextSequenceNumber(string $prefix, string $column = 'transfer_number'): string
    {
        $year = date('Y');
        $pattern = "{$prefix}-{$year}-";

        $latest = InternalTransfer::query()
            ->where($column, 'like', "{$pattern}%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', (string) $latest->{$column}, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        } else {
            $nextSeq = InternalTransfer::whereYear('created_at', $year)->count() + 1;
        }

        do {
            $candidate = sprintf('%s%04d', $pattern, $nextSeq++);
        } while (InternalTransfer::where($column, $candidate)->exists());

        return $candidate;
    }

    protected function resolvePriority(?string $priority): string
    {
        $upper = strtoupper(trim((string) $priority));
        return TransferPriority::tryFrom($upper)?->value ?? TransferPriority::NORMAL->value;
    }

    protected function assertBranchSupplyPolicy(Branch $sourceBranch, Branch $destBranch): void
    {
        if ($sourceBranch->can_supply_branches === false) {
            throw ValidationException::withMessages([
                'source_branch_id' => ["Branch '{$sourceBranch->branch_name}' is configured as non-supplying and cannot fulfill outbound internal transfers."],
            ]);
        }

        $srcRole = $sourceBranch->branch_role ?? 'RETAIL_STORE';
        $dstRole = $destBranch->branch_role ?? 'RETAIL_STORE';
        if ($srcRole === 'RETAIL_STORE' && $dstRole === 'RETAIL_STORE' && ($sourceBranch->allow_peer_transfers === false || $destBranch->allow_peer_transfers === false)) {
            throw ValidationException::withMessages([
                'source_branch_id' => ["Peer-to-peer transfers are disabled between retail branches ('{$sourceBranch->branch_name}' and '{$destBranch->branch_name}'). Please request from a Central Commissary or Hub."],
            ]);
        }
    }

    protected function resolveActorName(?User $actor, string $fallback): string
    {
        if ($actor) {
            return (string) ($actor->full_name ?? $actor->name ?? $actor->username ?? $fallback);
        }

        return $fallback;
    }
}

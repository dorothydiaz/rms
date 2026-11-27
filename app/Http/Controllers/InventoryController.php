<?php

namespace App\Http\Controllers;

use App\Models\Branch as CanonicalBranch;
use App\Models\Hr\Branch;
use App\Models\Inventory\BillOfMaterials;
use App\Models\Inventory\BillOfMaterialsItem;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\GoodsReceiptItem;
use App\Models\Inventory\InternalTransfer;
use App\Models\Inventory\InternalTransferItem;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\ProductionOrder;
use App\Models\Inventory\ProductionOrderItem;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\StockOutOrder;
use App\Models\Inventory\StockOutOrderItem;
use App\Models\Inventory\WasteRecord;
use App\Models\Inventory\WasteRecordItem;
use App\Models\Inventory\UomCategory;
use App\Models\Inventory\UnitOfMeasure;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\ReorderingRule;
use App\Services\CompanyBranchContext;
use App\Services\Inventory\BranchStockService;
use App\Services\Inventory\StockTransferService;
use App\Services\ReplenishmentEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function dashboard(): View
    {
        return view('inventory.dashboard');
    }

    /**
     * Stocks Overview - Live inventory balances, multi-warehouse tracking and audit ledger
     */
    public function stocksOverview(Request $request): View
    {
        $enriched = $this->getEnrichedStocksOverviewData();

        return view('inventory.stocks-overview', [
            'initialProducts' => $enriched['products'],
            'initialCategories' => $enriched['categories'],
            'initialLedger' => $enriched['ledger'],
            'initialWarehouses' => $enriched['warehouses'],
            'initialStorageAreas' => $enriched['storageAreas'],
        ]);
    }

    /**
     * API: Stocks Overview Live Payload
     */
    public function apiGetStocksOverviewData(): JsonResponse
    {
        $enriched = $this->getEnrichedStocksOverviewData();
        $products = $enriched['products'];

        $totalItems = $products->count();
        $totalValuation = $products->sum(function ($p) {
            return (float) $p->current_stock * (float) $p->cost_price;
        });
        $lowStockCount = $products->filter(function ($p) {
            return (float) $p->current_stock <= (float) $p->min_stock;
        })->count();
        $outOfStockCount = $products->filter(function ($p) {
            return (float) $p->current_stock <= 0;
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'categories' => $enriched['categories'],
                'stocksLedger' => $enriched['ledger'],
                'warehouses' => $enriched['warehouses'],
                'storageAreas' => $enriched['storageAreas'],
                'stats' => [
                    'totalItems' => $totalItems,
                    'totalValuation' => $totalValuation,
                    'lowStockCount' => $lowStockCount,
                    'outOfStockCount' => $outOfStockCount,
                ],
            ],
        ]);
    }

    /**
     * Enterprise Stocks Aggregator: Computes multi-warehouse stock balances,
     * location-specific bin holdings, and inbound open Purchase Order demand.
     */
    private function getEnrichedStocksOverviewData(): array
    {
        $branchCtx = app(CompanyBranchContext::class);
        $activeBranch = $branchCtx->getActiveBranch();
        $isConsolidated = $branchCtx->isConsolidatedView();
        $checkedBranchIds = $branchCtx->getCheckedBranchIds();

        $scopeBranchIds = [];
        if ($isConsolidated) {
            $scopeBranchIds = CanonicalBranch::active()->pluck('branch_id')->map(fn ($id) => (int) $id)->all();
        } elseif (count($checkedBranchIds) > 1) {
            $scopeBranchIds = array_map('intval', $checkedBranchIds);
        } elseif ($activeBranch) {
            $scopeBranchIds = [(int) $activeBranch->branch_id];
        }

        $hasBranchBalances = !empty($scopeBranchIds) && \App\Models\Inventory\BranchStockBalance::exists();
        $branchBalancesByItem = $hasBranchBalances
            ? \App\Models\Inventory\BranchStockBalance::with('branch:branch_id,branch_name,branch_code')
                ->whereIn('branch_id', $scopeBranchIds)
                ->get()
                ->groupBy('inventory_item_id')
            : collect();

        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();
        $ledgerQuery = StockLedger::orderByDesc('id');
        if ($hasBranchBalances && !$isConsolidated) {
            $ledgerQuery->where(function ($q) use ($scopeBranchIds) {
                $q->whereIn('branch_id', $scopeBranchIds)->orWhereNull('branch_id');
            });
        }
        $ledger = $ledgerQuery->take(100)->get();
        $warehouses = self::getDefaultWarehousesPayload();
        $storageAreas = self::getDefaultStorageAreasPayload();

        // 1. Open Purchase Orders: calculate Inbound PO demand per SKU and per Warehouse
        $openPos = PurchaseOrder::with('items')
            ->whereIn('status', ['Issued', 'Approved', 'Partially Received', 'Ready to Receive', 'Draft'])
            ->get();

        $poInboundBySku = [];
        $openPosBySku = [];

        foreach ($openPos as $po) {
            $poWhId = 'WH-MAIN';
            $poLoc = strtolower($po->delivery_location ?? '');
            foreach ($warehouses as $wh) {
                if (str_contains($poLoc, strtolower($wh['id'])) || str_contains($poLoc, strtolower($wh['code'])) || str_contains($poLoc, strtolower($wh['name']))) {
                    $poWhId = $wh['id'];
                    break;
                }
            }

            foreach ($po->items as $pi) {
                $ordered = (float) $pi->quantity;
                $recv = (float) ($pi->received_quantity ?? 0);
                $pending = max(0, $ordered - $recv);
                if ($pending > 0) {
                    $sku = $pi->sku;
                    if (!isset($poInboundBySku[$sku])) {
                        $poInboundBySku[$sku] = ['total' => 0.0, 'by_wh' => []];
                        $openPosBySku[$sku] = [];
                    }
                    $poInboundBySku[$sku]['total'] += $pending;
                    $poInboundBySku[$sku]['by_wh'][$poWhId] = ($poInboundBySku[$sku]['by_wh'][$poWhId] ?? 0.0) + $pending;

                    $openPosBySku[$sku][] = [
                        'po_number' => $po->po_number,
                        'vendor_name' => $po->vendor_name,
                        'order_date' => $po->order_date ? (is_string($po->order_date) ? $po->order_date : $po->order_date->format('Y-m-d')) : null,
                        'expected_delivery' => $po->expected_delivery ? (is_string($po->expected_delivery) ? $po->expected_delivery : $po->expected_delivery->format('Y-m-d')) : null,
                        'warehouse_id' => $poWhId,
                        'pending_qty' => $pending,
                    ];
                }
            }
        }

        // 2. Ledger balances aggregated per SKU and per Storage Location
        $ledgerLocationSums = StockLedger::selectRaw('sku, storage_location, SUM(quantity_change) as net_qty')
            ->groupBy('sku', 'storage_location')
            ->get()
            ->groupBy('sku');

        $locationToWhMap = [];
        foreach ($storageAreas as $area) {
            $locationToWhMap[strtolower(trim($area['name']))] = $area['warehouse_id'];
        }

        // 3. Production and Waste/Expiry movements aggregated per SKU
        $productionIn = StockLedger::where('transaction_type', 'PRODUCTION_IN')
            ->selectRaw('sku, SUM(quantity_change) as total_produced')
            ->groupBy('sku')
            ->pluck('total_produced', 'sku');

        $productionOut = StockLedger::where('transaction_type', 'PRODUCTION_OUT')
            ->selectRaw('sku, SUM(ABS(quantity_change)) as total_consumed')
            ->groupBy('sku')
            ->pluck('total_consumed', 'sku');

        $wasteAggregates = WasteRecordItem::whereHas('wasteRecord', function ($q) {
                $q->where('status', '!=', 'CANCELLED');
            })
            ->selectRaw("sku,
                SUM(quantity) as total_waste,
                SUM(CASE WHEN reason_code LIKE '%Expire%' OR reason_code LIKE '%Shelf%' THEN quantity ELSE 0 END) as total_expired,
                SUM(CASE WHEN reason_code NOT LIKE '%Expire%' AND reason_code NOT LIKE '%Shelf%' THEN quantity ELSE 0 END) as total_damaged,
                SUM(total_cost) as total_cost")
            ->groupBy('sku')
            ->get()
            ->keyBy('sku');

        // 4. Enrich each product (scoped to active / checked branch balances when available)
        $products = $products->map(function ($p) use ($poInboundBySku, $openPosBySku, $ledgerLocationSums, $locationToWhMap, $warehouses, $productionIn, $productionOut, $wasteAggregates, $hasBranchBalances, $branchBalancesByItem) {
            $sku = $p->sku;
            $itemBalances = $hasBranchBalances ? ($branchBalancesByItem->get($p->id) ?? collect()) : collect();
            if ($hasBranchBalances) {
                $currentStock = round((float) $itemBalances->sum('on_hand_qty'), 2);
                $reservedQty = round((float) $itemBalances->sum('reserved_qty'), 2);
                $inTransitIn = round((float) $itemBalances->sum('in_transit_in_qty'), 2);
                $inTransitOut = round((float) $itemBalances->sum('in_transit_out_qty'), 2);
                $p->current_stock = $currentStock;
                $p->reserved_qty = $reservedQty;
                $p->in_transit_in_qty = $inTransitIn;
                $p->in_transit_out_qty = $inTransitOut;
                $p->available_stock = max(0.0, round($currentStock - $reservedQty, 2));
                $p->branch_breakdown = $itemBalances->map(fn ($b) => [
                    'branch_id' => (int) $b->branch_id,
                    'branch_code' => $b->branch?->branch_code,
                    'branch_name' => $b->branch?->branch_name,
                    'on_hand_qty' => (float) $b->on_hand_qty,
                    'reserved_qty' => (float) $b->reserved_qty,
                    'available_qty' => (float) $b->available_qty,
                    'in_transit_in_qty' => (float) $b->in_transit_in_qty,
                    'in_transit_out_qty' => (float) $b->in_transit_out_qty,
                ])->values()->all();
            } else {
                $currentStock = (float) $p->current_stock;
                $p->reserved_qty = 0.0;
                $p->in_transit_in_qty = 0.0;
                $p->in_transit_out_qty = 0.0;
                $p->available_stock = max(0.0, $currentStock);
                $p->branch_breakdown = [];
            }

            $produced = (float) ($productionIn[$sku] ?? 0);
            $consumed = (float) ($productionOut[$sku] ?? 0);
            $p->production_produced = $produced;
            $p->production_consumed = $consumed;
            $p->production_net = $produced - $consumed;

            $wasteInfo = $wasteAggregates[$sku] ?? null;
            $p->waste_total = (float) ($wasteInfo->total_waste ?? 0);
            $p->waste_expired = (float) ($wasteInfo->total_expired ?? 0);
            $p->waste_damaged = (float) ($wasteInfo->total_damaged ?? 0);
            $p->waste_cost = (float) ($wasteInfo->total_cost ?? 0);

            $inboundInfo = $poInboundBySku[$sku] ?? ['total' => 0.0, 'by_wh' => []];
            $p->inbound_po_qty = (float) $inboundInfo['total'];
            $p->open_pos = $openPosBySku[$sku] ?? [];

            $whBreakdown = [];
            foreach ($warehouses as $wh) {
                $whBreakdown[$wh['id']] = [
                    'warehouse_id' => $wh['id'],
                    'code' => $wh['code'],
                    'name' => $wh['name'],
                    'on_hand' => 0.0,
                    'inbound_po' => (float) ($inboundInfo['by_wh'][$wh['id']] ?? 0.0),
                    'locations' => [],
                ];
            }

            $hasLedgerEntries = false;
            if (isset($ledgerLocationSums[$sku])) {
                foreach ($ledgerLocationSums[$sku] as $locSum) {
                    $locName = trim($locSum->storage_location ?? '');
                    if (empty($locName)) continue;
                    $qty = (float) $locSum->net_qty;
                    if ($qty <= 0) continue;
                    $hasLedgerEntries = true;

                    $whId = $locationToWhMap[strtolower($locName)] ?? null;
                    if (!$whId) {
                        $locLower = strtolower($locName);
                        if (str_contains($locLower, 'cold') || str_contains($locLower, 'freezer') || str_contains($locLower, 'meat')) {
                            $whId = 'WH-COLD';
                        } elseif (str_contains($locLower, 'bgc') || str_contains($locLower, 'pantry') || str_contains($locLower, 'boh')) {
                            $whId = 'WH-BGC';
                        } else {
                            $whId = 'WH-MAIN';
                        }
                    }

                    if (isset($whBreakdown[$whId])) {
                        $whBreakdown[$whId]['on_hand'] += $qty;
                        $whBreakdown[$whId]['locations'][] = [
                            'location_name' => $locName,
                            'qty' => $qty,
                        ];
                    }
                }
            }

            $allocatedTotal = array_sum(array_column($whBreakdown, 'on_hand'));
            if ($currentStock > $allocatedTotal || !$hasLedgerEntries) {
                $unallocated = max(0, $currentStock - $allocatedTotal);
                $defLoc = $p->storage_location ?: 'Central Commissary - Dock 1';
                $defWh = 'WH-MAIN';
                $defLower = strtolower($defLoc);
                if (str_contains($defLower, 'cold') || str_contains($defLower, 'freezer') || str_contains($defLower, 'meat') || str_contains(strtolower($p->category ?? ''), 'chilled') || str_contains(strtolower($p->category ?? ''), 'frozen')) {
                    $defWh = 'WH-COLD';
                } elseif (str_contains($defLower, 'bgc') || str_contains($defLower, 'pantry')) {
                    $defWh = 'WH-BGC';
                }

                if (isset($whBreakdown[$defWh])) {
                    $whBreakdown[$defWh]['on_hand'] += $unallocated;
                    $whBreakdown[$defWh]['locations'][] = [
                        'location_name' => $p->storage_location ?: 'Stock Zone A (Dry Goods)',
                        'qty' => $unallocated,
                    ];
                }
            }

            $primaryWh = 'WH-MAIN';
            $maxStockWh = -1;
            $primaryLoc = $p->storage_location ?: 'Stock Zone A';

            foreach ($whBreakdown as $wId => $wData) {
                if ($wData['on_hand'] > $maxStockWh) {
                    $maxStockWh = $wData['on_hand'];
                    $primaryWh = $wId;
                    if (!empty($wData['locations'])) {
                        $primaryLoc = $wData['locations'][0]['location_name'];
                    }
                }
            }

            $p->warehouse_breakdown = $whBreakdown;
            $p->primary_warehouse = $primaryWh;
            $p->primary_location = $primaryLoc;
            $p->available_to_promise = max(0, $currentStock + (float) $p->inbound_po_qty);

            return $p;
        });

        return [
            'products' => $products,
            'categories' => $categories,
            'ledger' => $ledger,
            'warehouses' => $warehouses,
            'storageAreas' => $storageAreas,
        ];
    }

    /**
     * Stock In / Receiving (GRN) Module
     */
    public function stockIn(Request $request): View
    {
        $purchaseOrders = PurchaseOrder::with('items')->orderByDesc('id')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $recentReceipts = GoodsReceipt::with('items')->orderByDesc('id')->take(200)->get();

        // Active company name for the printable GRN header (falls back to app name)
        $companyName = config('app.name', 'RMS');
        try {
            $activeCompany = app(\App\Services\CompanyBranchContext::class)->getActiveCompany();
            if ($activeCompany) {
                $companyName = $activeCompany->company_trade_name ?: ($activeCompany->company_legal_name ?: $companyName);
            }
        } catch (\Throwable $e) {
            // Context unavailable (e.g. no session) — keep fallback
        }

        return view('inventory.stock-in', [
            'companyName' => $companyName,
            'initialPurchaseOrders' => $purchaseOrders,
            'initialVendors' => $vendors,
            'initialProducts' => $products,
            'initialReceipts' => $recentReceipts,
            'initialWarehouses' => self::getDefaultWarehousesPayload(),
            'initialStorageAreas' => self::getDefaultStorageAreasPayload(),
            'initialMovementTypes' => self::getDefaultMovementTypesPayload(),
        ]);
    }

    /**
     * API: Stock In Initial Hydration Payload
     */
    public function apiGetStockInData(): JsonResponse
    {
        $purchaseOrders = PurchaseOrder::with('items')->orderByDesc('id')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $recentReceipts = GoodsReceipt::with('items')->orderByDesc('id')->take(200)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'purchaseOrders' => $purchaseOrders,
                'vendors' => $vendors,
                'products' => $products,
                'goodsReceipts' => $recentReceipts,
                'warehouses' => self::getDefaultWarehousesPayload(),
                'storageAreas' => self::getDefaultStorageAreasPayload(),
                'movementTypes' => self::getDefaultMovementTypesPayload(),
            ],
        ]);
    }


    /**
     * API: Receive Stock (PO fulfillment or Direct Receiving)
     * Strictly adheres to the 17-point Architectural Blueprint:
     * - Master Ledger System (SYS_LEDGER): Immutable append-only record with Before_Quantity & After_Quantity
     * - Atomic Database Transaction (Deferred Writes & Rollback Guard)
     * - Concurrency Guard: Lock rows with lockForUpdate()
     * - Bottom-up ledger state synchronization
     * - Server-side native user identity stamp
     */
    /**
     * Resolve deterministic UoM conversion ratio from source unit to target unit.
     * Queries active UnitOfMeasure rows first, then falls back to industry standards and explicit multipliers.
     */
    private static function resolveUomConversionRatio(?string $fromUom, ?string $toUom, ?float $explicitMultiplier = null): float
    {
        if ($explicitMultiplier !== null && $explicitMultiplier > 0 && abs($explicitMultiplier - 1.0) > 0.000001) {
            return $explicitMultiplier;
        }

        $fromKey = strtoupper(trim((string) $fromUom));
        $toKey = strtoupper(trim((string) $toUom));

        $aliasMap = [
            'GRAM' => 'G', 'GRAMS' => 'G', 'G' => 'G',
            'KG' => 'KG', 'KILO' => 'KG', 'KILOGRAM' => 'KG', 'KILOGRAMS' => 'KG',
            'MG' => 'MG', 'MILLIGRAM' => 'MG', 'MILLIGRAMS' => 'MG',
            'LB' => 'LB', 'LBS' => 'LB', 'POUND' => 'LB', 'POUNDS' => 'LB',
            'SACK' => 'SACK', 'SACKS' => 'SACK', 'BAG25' => 'SACK',
            'ML' => 'ML', 'MILLILITER' => 'ML', 'MILLILITRE' => 'ML',
            'L' => 'L', 'LITER' => 'L', 'LITERS' => 'L', 'LITRE' => 'L', 'LITRES' => 'L',
            'GAL' => 'GAL', 'GALLON' => 'GAL', 'GALLONS' => 'GAL',
            'PCS' => 'PCS', 'PC' => 'PCS', 'PIECE' => 'PCS', 'PIECES' => 'PCS', 'UNIT' => 'PCS',
            'DOZ' => 'DOZ', 'DOZEN' => 'DOZ',
        ];

        $normFrom = $aliasMap[$fromKey] ?? $fromKey;
        $normTo = $aliasMap[$toKey] ?? $toKey;

        if ($normFrom === '' || $normTo === '' || $normFrom === $normTo) {
            return ($explicitMultiplier !== null && $explicitMultiplier > 0) ? $explicitMultiplier : 1.0;
        }

        // Check active UnitOfMeasure registry when seeded in database
        try {
            $units = UnitOfMeasure::where('uom_is_active', true)->get()->keyBy(fn ($u) => strtoupper(trim((string) $u->uom_code)));
            $fromModel = $units->get($fromKey) ?? $units->get($normFrom);
            $toModel = $units->get($toKey) ?? $units->get($normTo);
            if ($fromModel && $toModel && strtoupper((string) $fromModel->uom_category) === strtoupper((string) $toModel->uom_category)) {
                $fromRatio = (float) $fromModel->base_ratio;
                $toRatio = (float) $toModel->base_ratio;
                if ($fromRatio > 0 && $toRatio > 0) {
                    return $fromRatio / $toRatio;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to deterministic conversion table
        }

        // Standard dimensional fallback table (grams, milliliters, pieces)
        $dimensions = [
            'MG'   => ['dim' => 'MASS',   'factor' => 0.001],
            'G'    => ['dim' => 'MASS',   'factor' => 1.0],
            'KG'   => ['dim' => 'MASS',   'factor' => 1000.0],
            'LB'   => ['dim' => 'MASS',   'factor' => 453.59237],
            'SACK' => ['dim' => 'MASS',   'factor' => 25000.0],
            'ML'   => ['dim' => 'VOLUME', 'factor' => 1.0],
            'L'    => ['dim' => 'VOLUME', 'factor' => 1000.0],
            'GAL'  => ['dim' => 'VOLUME', 'factor' => 3785.41],
            'PCS'  => ['dim' => 'COUNT',  'factor' => 1.0],
            'DOZ'  => ['dim' => 'COUNT',  'factor' => 12.0],
        ];

        if (isset($dimensions[$normFrom], $dimensions[$normTo]) && $dimensions[$normFrom]['dim'] === $dimensions[$normTo]['dim']) {
            return $dimensions[$normFrom]['factor'] / $dimensions[$normTo]['factor'];
        }

        return ($explicitMultiplier !== null && $explicitMultiplier > 0) ? $explicitMultiplier : 1.0;
    }

    public function apiReceiveStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_number' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|string|max:100',
            'vendor_name' => 'required|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|string|max:50',
            'movement_type_id' => 'nullable|string|max:50',
            'delivery_slip' => 'nullable|string|max:100',
            'carrier' => 'nullable|string|max:255',
            'reason_code' => 'nullable|string|max:100',
            'delivery_location' => 'required|string|max:255',
            'expected_delivery' => 'nullable|date',
            'received_at' => 'nullable|date',
            'backorder_policy' => 'nullable|string|in:CREATE_BACKORDER,SHORT_CLOSE',
            'intake_uuid' => 'nullable|string|max:100',
            'supervisor_override' => 'nullable|boolean',
            'variance_reason_code' => 'nullable|string|max:255',
            'qty_over_tolerance_pct' => 'nullable|numeric|min:0|max:100',
            'price_variance_tolerance_pct' => 'nullable|numeric|min:0|max:100',
            'use_mac' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'nullable',
            'items.*.sku' => 'required|string|max:100',
            'items.*.name' => 'required|string|max:255',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.uom_multiplier' => 'nullable|numeric|min:0.0001',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.rejected_qty' => 'nullable|numeric|min:0',
            'items.*.rejection_reason' => 'nullable|string|max:255',
            'items.*.quarantine_location' => 'nullable|string|max:255',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.lot_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        // Ensure at least one item has non-zero accepted or rejected quantity
        $hasPositiveMovement = collect($validated['items'])->some(function ($it) {
            return ((float) ($it['received_qty'] ?? 0) + (float) ($it['rejected_qty'] ?? 0)) > 0;
        });
        if (!$hasPositiveMovement) {
            return response()->json([
                'success' => false,
                'message' => 'At least one item must have an accepted or rejected quantity greater than zero.',
            ], 422);
        }

        // Idempotency Shield (Pillar 3 Rule 10)
        if (!empty($validated['intake_uuid'])) {
            $idempotencyKey = 'rms_intake_tx_' . $validated['intake_uuid'];
            if (!\Illuminate\Support\Facades\Cache::add($idempotencyKey, true, 60)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Intake already received and recorded (idempotent replay prevented).',
                ]);
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $year = date('Y');
            $prefix = !empty($validated['po_number']) ? 'GRN' : 'DIR-REC';
            $grnPrefix = "{$prefix}-{$year}-";

            // FIX-02: Collision-safe sequential GRN voucher numbering under transaction lock
            $latestGrn = GoodsReceipt::where('grn_number', 'like', "{$grnPrefix}%")->lockForUpdate()->orderByDesc('id')->first();
            $nextSeq = 1;
            if ($latestGrn && preg_match('/-(\d+)$/', (string) $latestGrn->grn_number, $m)) {
                $nextSeq = ((int) $m[1]) + 1;
            } else {
                $nextSeq = GoodsReceipt::whereYear('created_at', $year)->count() + 1;
            }
            do {
                $grnNumber = sprintf('%s%04d', $grnPrefix, $nextSeq++);
            } while (GoodsReceipt::where('grn_number', $grnNumber)->exists());

            $po = null;
            if (!empty($validated['po_number'])) {
                $po = PurchaseOrder::where('po_number', $validated['po_number'])->lockForUpdate()->first();
                if (!$po) {
                    return response()->json([
                        'success' => false,
                        'message' => "Purchase Order {$validated['po_number']} not found.",
                    ], 404);
                }

                // FIX-02: Reject concurrent / late receipts if PO is already closed or cancelled
                $poStatusUpper = strtoupper(trim((string) $po->status));
                if (in_array($poStatusUpper, ['FULLY RECEIVED', 'FULFILLED', 'CANCELLED', 'REFUSED', 'SHORT-CLOSED'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Purchase Order {$po->po_number} is already {$po->status} and cannot receive additional stock.",
                    ], 422);
                }

                if (!empty($validated['expected_delivery'])) {
                    $po->expected_delivery = $validated['expected_delivery'];
                    $po->save();
                }
            }

            $supervisorOverride = (bool) ($validated['supervisor_override'] ?? $request->boolean('supervisor_override'));
            $varianceReason = trim((string) ($validated['variance_reason_code'] ?? ''));
            $qtyTolerancePct = (float) ($validated['qty_over_tolerance_pct'] ?? 0.0);
            $priceTolerancePct = (float) ($validated['price_variance_tolerance_pct'] ?? 2.0);
            $hasOverrideReason = $supervisorOverride && $varianceReason !== '';

            // Stamped native user identity (Pillar 4 Rule 15)
            $performedBy = auth()->user()->name ?? auth()->user()->email ?? 'Warehouse Logistics Supervisor';
            $now = now();
            $receivedAt = !empty($validated['received_at'])
                ? \Carbon\Carbon::parse($validated['received_at'])->setTimeFrom($now)
                : $now;

            $receiptNotes = $po ? "Fulfillment for PO {$po->po_number}" : 'Direct inbound stock receiving';
            if ($hasOverrideReason) {
                $receiptNotes .= " | Supervisor Variance Override: {$varianceReason}";
            }

            // Create Goods Receipt Header
            $receipt = GoodsReceipt::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $po ? $po->id : null,
                'po_number' => $validated['po_number'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'vendor_name' => $validated['vendor_name'],
                'vendor_trade_name' => $validated['vendor_trade_name'] ?? null,
                'delivery_slip_no' => $validated['delivery_slip'] ?? null,
                'carrier_name' => $validated['carrier'] ?? null,
                'reason_code' => $validated['reason_code'] ?? ($po ? 'Standard PO Delivery' : 'Direct Inbound Intake'),
                'delivery_location' => $validated['delivery_location'],
                'settlement_mode' => $po ? 'PURCHASE_ORDER' : 'IMMEDIATE_COD',
                'payment_method' => 'Trade Credit (Net 30/15)',
                'payment_ref' => null,
                'freight_cost' => 0.00,
                'customs_cost' => 0.00,
                'handling_cost' => 0.00,
                'total_landed_costs' => 0.00,
                'items_subtotal' => 0.00,
                'gross_total' => 0.00,
                'received_at' => $receivedAt,
                'received_by' => $performedBy,
                'status' => 'COMPLETED',
                'notes' => $receiptNotes,
            ]);

            // High-Performance Vectorized Batching (Pillar 2 Rule 5)
            $skus = collect($validated['items'])->pluck('sku')->unique()->toArray();

            // Pre-fetch all relevant inventory items with locks in 1 query
            $inventoryItems = InventoryItem::whereIn('sku', $skus)->lockForUpdate()->get()->keyBy('sku');

            // Pre-fetch all PO lines with locks (by ID, avoiding duplicate-SKU key collapse)
            $allPoLines = $po
                ? PurchaseOrderItem::where('purchase_order_id', $po->id)->lockForUpdate()->get()
                : collect();

            $receiptItemsToInsert = [];
            $ledgerEntriesToInsert = [];
            $itemsToSave = [];
            $poItemsToSave = [];
            $branchStockDeltas = [];
            $itemsSubtotal = 0.00;

            foreach ($validated['items'] as $itemData) {
                $sku = trim($itemData['sku']);
                $acceptedQty = max(0.0, (float) ($itemData['received_qty'] ?? 0));
                $rejectedQty = max(0.0, (float) ($itemData['rejected_qty'] ?? 0));
                if (($acceptedQty + $rejectedQty) <= 0) {
                    continue;
                }

                // Resolve Inventory Item
                $invItem = $inventoryItems->get($sku);
                if (!$invItem) {
                    $invItem = InventoryItem::create([
                        'sku' => $sku,
                        'name' => $itemData['name'],
                        'category' => 'Raw Ingredients',
                        'uom' => $itemData['unit'] ?? 'Kg',
                        'cost_price' => 0.00,
                        'selling_price' => 0.00,
                        'current_stock' => 0.00,
                        'min_stock' => 10.00,
                        'max_stock' => 100.00,
                        'storage_location' => $itemData['storage_location'] ?? $validated['delivery_location'],
                        'is_active' => true,
                    ]);
                    $inventoryItems->put($sku, $invItem);
                }

                $receiptUom = $itemData['unit'] ?? $invItem->uom ?? 'PCS';
                $explicitMultiplier = isset($itemData['uom_multiplier']) && is_numeric($itemData['uom_multiplier'])
                    ? (float) $itemData['uom_multiplier']
                    : null;

                // FIX-01: Resolve deterministic conversion to Item Master Base UoM
                $multiplier = self::resolveUomConversionRatio($receiptUom, $invItem->uom, $explicitMultiplier);
                $baseQty = round($acceptedQty * $multiplier, 4);
                $rejectedBaseQty = round($rejectedQty * $multiplier, 4);

                // FIX-05: Match PO line by purchase_order_item_id first, then by SKU (preferring lines with remaining balance)
                $poItem = null;
                if ($allPoLines->isNotEmpty()) {
                    if (!empty($itemData['purchase_order_item_id'])) {
                        $poItem = $allPoLines->firstWhere('id', $itemData['purchase_order_item_id']);
                    }
                    if (!$poItem) {
                        $poItem = $allPoLines->first(function ($pl) use ($sku) {
                            return $pl->sku === $sku && ((float) $pl->received_quantity < (float) $pl->quantity);
                        }) ?? $allPoLines->firstWhere('sku', $sku);
                    }
                }

                // Resolve unit cost & enforce PO variance + over-receipt gates (FIX-03)
                $submittedCost = (isset($itemData['unit_cost']) && is_numeric($itemData['unit_cost']) && (float) $itemData['unit_cost'] > 0)
                    ? (float) $itemData['unit_cost']
                    : null;

                $unitCost = (float) ($invItem->cost_price ?? 0);
                if ($poItem) {
                    $poUomRatio = self::resolveUomConversionRatio($receiptUom, $poItem->uom, null);
                    $poNormalizedAccepted = round($acceptedQty * $poUomRatio, 4);
                    $poNormalizedRejected = round($rejectedQty * $poUomRatio, 4);
                    $remainingPoQty = max(0.0, (float) $poItem->quantity - (float) $poItem->received_quantity);
                    $maxAllowedPoQty = ($remainingPoQty * (1.0 + ($qtyTolerancePct / 100.0))) + 0.0001;

                    if ($poNormalizedAccepted > $maxAllowedPoQty && !$hasOverrideReason) {
                        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                            'success' => false,
                            'message' => "Over-receipt blocked for SKU {$sku}: trying to receive {$poNormalizedAccepted} {$poItem->uom} against remaining PO balance of {$remainingPoQty} {$poItem->uom} (tolerance {$qtyTolerancePct}%). Supervisor override & variance_reason_code required.",
                        ], 422));
                    }

                    $poContractPrice = (float) ($poItem->unit_price ?? 0);
                    if ($submittedCost !== null && $poContractPrice > 0) {
                        $expectedReceiptCost = $poContractPrice * $poUomRatio;
                        $priceVariancePct = $expectedReceiptCost > 0
                            ? (abs($submittedCost - $expectedReceiptCost) / $expectedReceiptCost) * 100.0
                            : 0.0;

                        if ($priceVariancePct > ($priceTolerancePct + 0.0001) && !$hasOverrideReason) {
                            $roundedVar = round($priceVariancePct, 2);
                            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                                'success' => false,
                                'message' => "Price variance blocked for SKU {$sku}: dock cost deviates {$roundedVar}% from PO contract price (limit ±{$priceTolerancePct}%). Supervisor override & variance_reason_code required.",
                            ], 422));
                        }
                        $unitCost = $submittedCost;
                    } elseif ($poContractPrice > 0) {
                        $unitCost = $poContractPrice * $poUomRatio;
                    } elseif ($submittedCost !== null) {
                        $unitCost = $submittedCost;
                    }

                    // Update PO Item in memory using PO-normalized quantities
                    $poItem->received_quantity = (float) $poItem->received_quantity + $poNormalizedAccepted;
                    $poItem->rejected_quantity = (float) ($poItem->rejected_quantity ?? 0) + $poNormalizedRejected;
                    $poItem->status = ($poItem->received_quantity >= (float) $poItem->quantity) ? 'FULFILLED' : 'PARTIAL';
                    $poItemsToSave[$poItem->id] = $poItem;
                } elseif ($submittedCost !== null) {
                    $unitCost = $submittedCost;
                }

                $totalCost = round($acceptedQty * $unitCost, 2);
                $itemsSubtotal += $totalCost;

                // Base unit cost & Moving Average Cost (MAC) update on Item Master (FIX-01)
                $baseUnitCost = $multiplier > 0 ? round($unitCost / $multiplier, 6) : $unitCost;
                $oldStock = max(0.0, (float) $invItem->current_stock);
                $oldCost = max(0.0, (float) ($invItem->cost_price ?? 0));

                if ($acceptedQty > 0 && $unitCost > 0) {
                    if (($po || $request->boolean('use_mac')) && ($oldStock + $baseQty) > 0 && $oldCost > 0) {
                        $invItem->cost_price = round((($oldStock * $oldCost) + ($baseQty * $baseUnitCost)) / ($oldStock + $baseQty), 2);
                    } else {
                        $invItem->cost_price = round($baseUnitCost, 2);
                    }
                }

                $rejectionReason = trim((string) ($itemData['rejection_reason'] ?? '')) ?: null;
                $quarantineLoc = trim((string) ($itemData['quarantine_location'] ?? '')) ?: 'Inbound Quarantine & Vendor Return Cage';

                // Prepare Receipt Item row (FIX-04)
                $receiptItemsToInsert[] = [
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $poItem ? $poItem->id : null,
                    'sku' => $sku,
                    'item_name' => $itemData['name'],
                    'uom' => $invItem->uom ?: $receiptUom,
                    'purchasing_uom' => $receiptUom,
                    'uom_multiplier' => $multiplier,
                    'received_qty' => $acceptedQty,
                    'accepted_qty' => $acceptedQty,
                    'rejected_qty' => $rejectedQty,
                    'rejection_reason' => $rejectedQty > 0 ? ($rejectionReason ?? 'Failed QC Inspection') : null,
                    'quarantine_location' => $rejectedQty > 0 ? $quarantineLoc : null,
                    'base_qty' => $baseQty,
                    'unit_cost' => round($unitCost, 2),
                    'total_cost' => $totalCost,
                    'lot_number' => $itemData['lot_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $targetLocation = !empty($itemData['storage_location']) ? $itemData['storage_location'] : $validated['delivery_location'];

                // Prepare Master Ledger (SYS_LEDGER) immutable row for ACCEPTED stock
                if ($acceptedQty > 0) {
                    $beforeQty = (float) $invItem->current_stock;
                    $afterQty = $beforeQty + $baseQty;
                    $invItem->current_stock = $afterQty;
                    $itemsToSave[$invItem->id] = $invItem;
                    $branchStockDeltas[$invItem->id] = [
                        'qty' => ($branchStockDeltas[$invItem->id]['qty'] ?? 0.0) + $baseQty,
                        'unit_cost' => round($baseUnitCost, 4),
                    ];

                    $noteSuffix = $hasOverrideReason ? " | Variance Override: {$varianceReason}" : '';
                    $ledgerEntriesToInsert[] = [
                        'transaction_uuid' => (string) Str::uuid(),
                        'sku' => $sku,
                        'inventory_item_id' => $invItem->id,
                        'item_name' => $invItem->name,
                        'transaction_type' => $po ? 'PURCHASE_RECEIPT' : 'DIRECT_RECEIVING',
                        'reference_type' => 'goods_receipts',
                        'reference_id' => $receipt->id,
                        'reference_no' => $receipt->grn_number,
                        'before_quantity' => $beforeQty,
                        'quantity_change' => $baseQty,
                        'after_quantity' => $afterQty,
                        'unit_cost' => round($baseUnitCost, 2),
                        'total_value' => $totalCost,
                        'batch_lot_no' => $itemData['lot_number'] ?? null,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                        'storage_location' => $targetLocation,
                        'performed_by' => $performedBy,
                        'notes' => "Dock intake under {$receipt->grn_number}" . ($po ? " (PO: {$po->po_number})" : '') . " | Target: {$targetLocation}{$noteSuffix}",
                        'created_at' => $now,
                    ];
                }

                // FIX-04: Segregated Quarantine Hold Ledger Entry for REJECTED / DAMAGED units
                if ($rejectedQty > 0) {
                    $currActive = (float) $invItem->current_stock;
                    $reasonTxt = $rejectionReason ?? 'Failed QC Inspection';
                    $ledgerEntriesToInsert[] = [
                        'transaction_uuid' => (string) Str::uuid(),
                        'sku' => $sku,
                        'inventory_item_id' => $invItem->id,
                        'item_name' => $invItem->name,
                        'transaction_type' => 'QUARANTINE_HOLD',
                        'reference_type' => 'goods_receipts',
                        'reference_id' => $receipt->id,
                        'reference_no' => $receipt->grn_number,
                        'before_quantity' => $currActive,
                        'quantity_change' => 0.00,
                        'after_quantity' => $currActive,
                        'unit_cost' => round($baseUnitCost, 2),
                        'total_value' => round($rejectedQty * $unitCost, 2),
                        'batch_lot_no' => $itemData['lot_number'] ?? null,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                        'storage_location' => $quarantineLoc,
                        'performed_by' => $performedBy,
                        'notes' => "QC Rejected/Quarantined {$rejectedBaseQty} {$invItem->uom} ({$rejectedQty} {$receiptUom}) under {$receipt->grn_number}" . ($po ? " (PO: {$po->po_number})" : '') . " | Reason: {$reasonTxt}",
                        'created_at' => $now,
                    ];
                }
            }

            // 1-Shot Batch Inserts
            if (!empty($receiptItemsToInsert)) {
                GoodsReceiptItem::insert($receiptItemsToInsert);
            }
            if (!empty($ledgerEntriesToInsert)) {
                StockLedger::insert($ledgerEntriesToInsert);
            }

            // Bulk Save In-Memory Updated Models
            foreach ($itemsToSave as $itSave) {
                $itSave->save();
            }
            foreach ($poItemsToSave as $piSave) {
                $piSave->save();
            }

            // Update receipt financials
            $receipt->items_subtotal = $itemsSubtotal;
            $receipt->gross_total = $itemsSubtotal;
            $receipt->save();

            if (!empty($branchStockDeltas)) {
                $branchCtx = app(CompanyBranchContext::class);
                $targetBranch = $branchCtx->getActiveBranch() ?? CanonicalBranch::active()->orderBy('branch_id')->first();
                if ($targetBranch) {
                    app(BranchStockService::class)->creditBranchOnStockIn(
                        (int) $targetBranch->branch_id,
                        $branchStockDeltas,
                        $targetBranch->company_id !== null ? (int) $targetBranch->company_id : null
                    );
                }
            }

            // Update Purchase Order overall status with Backorder Policy
            if ($po) {
                $allPoItems = PurchaseOrderItem::where('purchase_order_id', $po->id)->get();
                $allFulfilled = $allPoItems->every(function ($it) {
                    return (float) $it->received_quantity >= (float) $it->quantity;
                });
                $anyReceived = $allPoItems->some(function ($it) {
                    return (float) $it->received_quantity > 0;
                });

                $policy = $validated['backorder_policy'] ?? 'CREATE_BACKORDER';

                if ($allFulfilled || $policy === 'SHORT_CLOSE') {
                    $po->status = 'Fully Received';
                } elseif ($anyReceived) {
                    $po->status = 'Partially Received';
                }
                $po->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully received into inventory. Voucher {$receipt->grn_number} created and posted to Stock Ledger.",
                'grn_number' => $receipt->grn_number,
                'po_number' => $receipt->po_number,
                'received_at' => $receipt->received_at->toIso8601String(),
                'items_count' => count($receiptItemsToInsert),
            ]);
        });
    }

    public function begBalance(): View
    {
        return view('inventory.beg-balance');
    }

    public function stockOut(): View
    {
        $orderTypeFilter = request()->query('order_type');
        $ordersQuery = StockOutOrder::with(['items', 'vendor', 'salesOrder'])->orderByDesc('id');
        if (!empty($orderTypeFilter)) {
            $ordersQuery->where('order_type', strtoupper(trim((string) $orderTypeFilter)));
        }
        $orders = $ordersQuery->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        $companyName = config('app.name', 'RMS');
        try {
            $activeCompany = app(\App\Services\CompanyBranchContext::class)->getActiveCompany();
            if ($activeCompany) {
                $companyName = $activeCompany->company_trade_name ?: ($activeCompany->company_legal_name ?: $companyName);
            }
        } catch (\Throwable $e) {
            // Context unavailable — keep fallback
        }

        return view('inventory.stock-out', [
            'companyName' => $companyName,
            'initialOrders' => $orders,
            'initialProducts' => $products,
            'initialVendors' => $vendors,
            'initialCategories' => $categories,
            'initialWarehouses' => self::getDefaultWarehousesPayload(),
            'initialStorageAreas' => self::getDefaultStorageAreasPayload(),
            'initialMovementTypes' => self::getDefaultMovementTypesPayload(),
        ]);
    }

    /**
     * API: Stock Out Initial Hydration Payload
     */
    public function apiGetStockOutData(): JsonResponse
    {
        $orders = StockOutOrder::with(['items', 'vendor', 'salesOrder'])->orderByDesc('id')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'totalOrders' => $orders->count(),
            'draftCount' => $orders->where('status', 'DRAFT')->count(),
            'pickingCount' => $orders->whereIn('status', ['PICKING', 'PACKED'])->count(),
            'shippedCount' => $orders->where('status', 'SHIPPED')->count(),
            'totalValueDispatched' => (float) $orders->where('status', 'SHIPPED')->sum('total_cost_value'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'products' => $products,
                'vendors' => $vendors,
                'categories' => $categories,
                'warehouses' => self::getDefaultWarehousesPayload(),
                'storageAreas' => self::getDefaultStorageAreasPayload(),
                'movementTypes' => self::getDefaultMovementTypesPayload(),
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * API: Create Stock Out Draft Requisition
     */
    public function apiCreateStockOutDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_type' => 'required|string|max:50',
            'destination_type' => 'nullable|string|max:50',
            'destination_location' => 'required|string|max:255',
            'vendor_id' => 'nullable|integer',
            'vendor_name' => 'nullable|string|max:255',
            'requested_by' => 'required|string|max:255',
            'department' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'required_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.inventory_item_id' => 'nullable|integer',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.requested_qty' => 'required|numeric|min:0.001',
            'items.*.packed_qty' => 'nullable|numeric|min:0',
            'items.*.batch_lot_no' => 'nullable|string|max:100',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            $year = date('Y');
            $seq = StockOutOrder::whereYear('created_at', $year)->count() + 1;
            do {
                $orderNumber = sprintf('SO-%s-%04d', $year, $seq);
                $seq++;
            } while (StockOutOrder::where('order_number', $orderNumber)->exists());

            $totalItemsCount = count($validated['items']);
            $totalRequestedQty = 0.00;
            $totalPackedQty = 0.00;
            $totalCostValue = 0.00;

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['requested_qty'];
                $packed = (float) ($item['packed_qty'] ?? 0.00);
                $cost = (float) $item['unit_cost'];
                $totalRequestedQty += $qty;
                $totalPackedQty += $packed;
                $totalCostValue += ($qty * $cost);
            }

            $order = StockOutOrder::create([
                'order_number' => $orderNumber,
                'order_type' => $validated['order_type'],
                'status' => 'DRAFT',
                'priority' => $validated['priority'] ?? 'NORMAL',
                'destination_type' => $validated['destination_type'] ?? 'INTERNAL_KITCHEN',
                'destination_location' => $validated['destination_location'],
                'vendor_id' => $validated['vendor_id'] ?? null,
                'vendor_name' => $validated['vendor_name'] ?? null,
                'requested_by' => $validated['requested_by'],
                'department' => $validated['department'] ?? 'Kitchen Operations',
                'reference_no' => $validated['reference_no'] ?? null,
                'required_at' => $validated['required_at'] ?? null,
                'total_items_count' => $totalItemsCount,
                'total_requested_qty' => $totalRequestedQty,
                'total_packed_qty' => $totalPackedQty,
                'total_cost_value' => $totalCostValue,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Vectorized pre-fetch to avoid N+1 queries inside loop
            $skus = collect($validated['items'])->pluck('sku')->filter()->unique()->values()->all();
            $itemIds = collect($validated['items'])->pluck('inventory_item_id')->filter()->unique()->values()->all();
            $invItemsBySku = InventoryItem::whereIn('sku', $skus)->get()->keyBy('sku');
            $invItemsById = !empty($itemIds) ? InventoryItem::whereIn('id', $itemIds)->get()->keyBy('id') : collect();

            foreach ($validated['items'] as $item) {
                $invItem = !empty($item['inventory_item_id'])
                    ? ($invItemsById->get($item['inventory_item_id']) ?? $invItemsBySku->get($item['sku']))
                    : $invItemsBySku->get($item['sku']);

                $availableStock = $invItem ? (float) $invItem->current_stock : 0.00;
                $qty = (float) $item['requested_qty'];
                $packedQty = (float) ($item['packed_qty'] ?? 0.00);
                $unitCost = (float) $item['unit_cost'];

                StockOutOrderItem::create([
                    'stock_out_order_id' => $order->id,
                    'inventory_item_id' => $invItem ? $invItem->id : null,
                    'sku' => $item['sku'],
                    'item_name' => $item['item_name'],
                    'category' => $item['category'] ?? ($invItem ? $invItem->category : 'General'),
                    'uom' => $item['uom'] ?? ($invItem ? $invItem->uom : 'Unit'),
                    'available_stock' => $availableStock,
                    'requested_qty' => $qty,
                    'packed_qty' => $packedQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
                    'batch_lot_no' => $item['batch_lot_no'] ?? null,
                    'storage_location' => $item['storage_location'] ?? ($invItem ? $invItem->storage_location : null),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $order->load(['items', 'vendor']);

            return response()->json([
                'success' => true,
                'message' => "Draft Requisition {$orderNumber} successfully created.",
                'data' => [
                    'order' => $order,
                ],
            ], 201);
        });
    }

    /**
     * API: Update Pick & Pack Quantities
     */
    public function apiUpdateStockOutPickPack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:stock_out_header,id',
            'picker_name' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'tracking_waybill' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.packed_qty' => 'required|numeric|min:0',
            'items.*.batch_lot_no' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($validated) {
            $order = StockOutOrder::where('id', $validated['order_id'])->lockForUpdate()->firstOrFail();

            if ($order->status === 'SHIPPED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update an already dispatched/shipped order.',
                ], 422);
            }

            $totalPacked = 0.00;
            $allMatchedOrExceeded = true;
            $hasAnyPacked = false;

            foreach ($validated['items'] as $itemUpdate) {
                $lineItem = StockOutOrderItem::where('stock_out_order_id', $order->id)
                    ->where('sku', $itemUpdate['sku'])
                    ->first();

                if ($lineItem) {
                    $packedQty = (float) $itemUpdate['packed_qty'];
                    $lineItem->packed_qty = $packedQty;
                    if (isset($itemUpdate['batch_lot_no'])) {
                        $lineItem->batch_lot_no = $itemUpdate['batch_lot_no'];
                    }
                    $lineItem->save();

                    $totalPacked += $packedQty;
                    if ($packedQty > 0) {
                        $hasAnyPacked = true;
                    }
                    if ($packedQty < $lineItem->requested_qty) {
                        $allMatchedOrExceeded = false;
                    }
                }
            }

            $newStatus = 'DRAFT';
            if ($allMatchedOrExceeded && $hasAnyPacked) {
                $newStatus = 'PACKED';
            } elseif ($hasAnyPacked) {
                $newStatus = 'PICKING';
            }

            $order->update([
                'status' => $newStatus,
                'total_packed_qty' => $totalPacked,
                'picker_name' => $validated['picker_name'] ?? $order->picker_name,
                'carrier_name' => $validated['carrier_name'] ?? $order->carrier_name,
                'tracking_waybill' => $validated['tracking_waybill'] ?? $order->tracking_waybill,
            ]);

            $order->load(['items', 'vendor']);

            return response()->json([
                'success' => true,
                'message' => "Pick & Pack progress saved for {$order->order_number}.",
                'data' => [
                    'order' => $order,
                ],
            ]);
        });
    }

    /**
     * API: Confirm Ship & Complete Stock Out (Deducts Inventory & Appends to Master Ledger)
     */
    public function apiConfirmShipStockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:stock_out_header,id',
            'picker_name' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'tracking_waybill' => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|max:120',
            'items' => 'nullable|array',
            'items.*.sku' => 'required_with:items|string|max:100',
            'items.*.packed_qty' => 'required_with:items|numeric|min:0',
            'items.*.batch_lot_no' => 'nullable|string|max:100',
        ]);

        if (!empty($validated['idempotency_key'])) {
            $cachedDispatch = \Illuminate\Support\Facades\Cache::get('stock_out_idem_' . $validated['idempotency_key']);
            if ($cachedDispatch) {
                $existingOrder = StockOutOrder::with(['items', 'vendor'])->find($cachedDispatch['order_id'] ?? $validated['order_id']);
                return response()->json([
                    'success' => true,
                    'idempotent' => true,
                    'message' => 'Dispatch already recorded (idempotent replay prevented).',
                    'data' => [
                        'order' => $existingOrder,
                    ],
                ]);
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $order = StockOutOrder::where('id', $validated['order_id'])->lockForUpdate()->firstOrFail();

            if ($order->status === 'SHIPPED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already marked as SHIPPED. Inventory has already been decremented.',
                ], 422);
            }

            // If line items packed quantities were submitted in this call, update them first
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemUpdate) {
                    $lineItem = StockOutOrderItem::where('stock_out_order_id', $order->id)
                        ->where('sku', $itemUpdate['sku'])
                        ->first();
                    if ($lineItem) {
                        $lineItem->packed_qty = (float) $itemUpdate['packed_qty'];
                        if (isset($itemUpdate['batch_lot_no'])) {
                            $lineItem->batch_lot_no = $itemUpdate['batch_lot_no'];
                        }
                        $lineItem->save();
                    }
                }
            }

            $order->load('items');
            $performedBy = auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? 'Warehouse Lead';

            // Pre-fetch & lock all referenced SKUs in a single vectorized query
            $skus = $order->items->pluck('sku')->filter()->unique()->values()->all();
            $inventoryItems = InventoryItem::whereIn('sku', $skus)->lockForUpdate()->get()->keyBy('sku');
            $branchCtx = app(CompanyBranchContext::class);
            $activeBranch = $branchCtx->getActiveBranch();
            $branchId = $order->branch_id ? (int) $order->branch_id : ($activeBranch ? (int) $activeBranch->branch_id : null);
            $companyId = $order->company_id ? (int) $order->company_id : ($activeBranch?->company_id !== null ? (int) $activeBranch->company_id : null);
            $branchStockSvc = app(BranchStockService::class);

            $totalShippedQty = 0.00;
            $totalActualCost = 0.00;

            foreach ($order->items as $item) {
                $qtyToDeduct = (float) $item->packed_qty;
                if ($qtyToDeduct <= 0) {
                    continue; // Skip items not packed
                }

                $inventoryItem = $inventoryItems->get($item->sku);
                if (!$inventoryItem) {
                    throw new \RuntimeException("Inventory item with SKU {$item->sku} not found in Item Master.");
                }

                $beforeQty = (float) $inventoryItem->current_stock;
                $afterQty = $beforeQty - $qtyToDeduct;

                // Update inventory_items current stock
                $inventoryItem->current_stock = $afterQty;
                $inventoryItem->save();

                if ($branchId !== null) {
                    $branchStockSvc->adjustBranchOnHand($branchId, (int) $inventoryItem->id, -$qtyToDeduct, (float) $item->unit_cost, $companyId);
                }

                // Append to immutable StockLedger
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'sku' => $item->sku,
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name' => $item->item_name,
                    'transaction_type' => 'STOCK_OUT',
                    'reference_type' => 'stock_out_orders',
                    'reference_id' => $order->id,
                    'reference_no' => $order->order_number,
                    'before_quantity' => $beforeQty,
                    'quantity_change' => -$qtyToDeduct,
                    'after_quantity' => $afterQty,
                    'unit_cost' => $item->unit_cost,
                    'total_value' => $qtyToDeduct * (float)$item->unit_cost,
                    'batch_lot_no' => $item->batch_lot_no,
                    'storage_location' => $item->storage_location ?? $inventoryItem->storage_location,
                    'performed_by' => $performedBy,
                    'notes' => "Stock Out: [{$order->order_type}] Destination: {$order->destination_location}",
                ]);

                $totalShippedQty += $qtyToDeduct;
                $totalActualCost += ($qtyToDeduct * (float)$item->unit_cost);
            }

            if ($totalShippedQty <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot ship order with 0 packed items. Please pack items before dispatching.',
                ], 422);
            }

            // Update order status to SHIPPED with server-verified approved_by
            $order->update([
                'status' => 'SHIPPED',
                'dispatched_at' => now(),
                'approved_by' => $performedBy,
                'picker_name' => $validated['picker_name'] ?? $order->picker_name ?? $performedBy,
                'carrier_name' => $validated['carrier_name'] ?? $order->carrier_name,
                'tracking_waybill' => $validated['tracking_waybill'] ?? $order->tracking_waybill,
                'total_packed_qty' => $totalShippedQty,
                'total_cost_value' => $totalActualCost,
            ]);

            $salesSync = null;
            if (!empty($order->sales_order_id)) {
                $salesSync = app(\App\Services\Sales\SalesInventoryService::class)->onDeliveryShipped($order->fresh('items'), $performedBy);
            }

            if (!empty($validated['idempotency_key'])) {
                \Illuminate\Support\Facades\Cache::put('stock_out_idem_' . $validated['idempotency_key'], [
                    'order_id' => $order->id,
                ], 60);
            }

            $order->load(['items', 'vendor', 'salesOrder']);

            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_number} successfully dispatched & inventory deducted.",
                'data' => [
                    'order' => $order,
                    'dispatched_at' => $order->dispatched_at->format('Y-m-d H:i:s'),
                    'sales_sync' => $salesSync,
                ],
            ]);
        });
    }

    /**
     * API: Cancel Stock Out Order
     */
    public function apiCancelStockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:stock_out_header,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $order = StockOutOrder::findOrFail($validated['order_id']);
        if ($order->status === 'SHIPPED') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel an order that has already been shipped and deducted from ledger.',
            ], 422);
        }

        $order->status = 'CANCELLED';
        $order->notes = ($order->notes ? $order->notes . ' | ' : '') . 'Cancelled: ' . ($validated['reason'] ?? 'No reason provided');
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Order {$order->order_number} has been cancelled.",
            'data' => ['order' => $order],
        ]);
    }

    /**
     * Internal Transfer Module (Branch Requests, Direct Push & Receiving)
     */
    public function internalTransfer(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): View
    {
        $activeBranch = $branchContext->getActiveBranch();
        $activeBranchId = $activeBranch ? (int) $activeBranch->branch_id : null;
        $isConsolidated = $branchContext->isConsolidatedView();

        $branches = CanonicalBranch::active()
            ->orderBy('branch_name')
            ->get([
                'branch_id',
                'company_id',
                'branch_code',
                'branch_name',
                'branch_type',
                'branch_address_line_1',
                'branch_city',
                'branch_role',
                'default_source_branch_id',
                'can_supply_branches',
                'allow_peer_transfers',
                'can_direct_procure',
                'internal_pricing_mode',
                'internal_markup_pct',
                'auto_post_interbranch_gl',
            ])
            ->map(function (CanonicalBranch $b) {
                return [
                    'id' => (int) $b->branch_id,
                    'branch_id' => (int) $b->branch_id,
                    'company_id' => $b->company_id !== null ? (int) $b->company_id : null,
                    'code' => $b->branch_code,
                    'name' => $b->branch_name,
                    'type' => $b->branch_type,
                    'address' => trim(($b->branch_address_line_1 ?? '') . ($b->branch_city ? ', ' . $b->branch_city : ''), ', '),
                    'branch_role' => $b->branch_role ?? 'RETAIL_STORE',
                    'default_source_branch_id' => $b->default_source_branch_id !== null ? (int) $b->default_source_branch_id : null,
                    'can_supply_branches' => $b->can_supply_branches ?? true,
                    'allow_peer_transfers' => $b->allow_peer_transfers ?? true,
                    'can_direct_procure' => $b->can_direct_procure ?? true,
                    'internal_pricing_mode' => $b->internal_pricing_mode ?? 'AT_COST',
                    'internal_markup_pct' => (float) ($b->internal_markup_pct ?? 0),
                    'auto_post_interbranch_gl' => $b->auto_post_interbranch_gl ?? true,
                ];
            })
            ->values();

        $products = InventoryItem::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'barcode', 'name', 'category', 'uom', 'cost_price', 'min_stock', 'max_stock', 'storage_location']);

        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'slug']);

        $initialPayload = $transferService->getRegisterPayload(
            $request->only(['perspective', 'status', 'transfer_type', 'branch_id', 'search', 'page', 'per_page']),
            $activeBranchId,
            $isConsolidated
        );

        return view('inventory.internal-transfer', [
            'initialPayload' => $initialPayload,
            'initialProducts' => $products,
            'initialBranches' => $branches,
            'initialCategories' => $categories,
            'branchContext' => [
                'activeBranchId' => $activeBranchId,
                'activeBranchName' => $activeBranch?->branch_name,
                'activeBranchCode' => $activeBranch?->branch_code,
                'isConsolidated' => $isConsolidated,
            ],
        ]);
    }

    /**
     * API: Paginated Internal Transfer Register & SQL KPI Payload
     */
    public function apiGetInternalTransferData(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): JsonResponse
    {
        $activeBranch = $branchContext->getActiveBranch();
        $activeBranchId = $activeBranch ? (int) $activeBranch->branch_id : null;
        $isConsolidated = $branchContext->isConsolidatedView();

        $payload = $transferService->getRegisterPayload(
            $request->only(['perspective', 'status', 'transfer_type', 'branch_id', 'search', 'page', 'per_page']),
            $activeBranchId,
            $isConsolidated
        );

        $payload['branchContext'] = [
            'activeBranchId' => $activeBranchId,
            'activeBranchName' => $activeBranch?->branch_name,
            'activeBranchCode' => $activeBranch?->branch_code,
            'isConsolidated' => $isConsolidated,
        ];

        if ($request->boolean('include_lookups')) {
            $payload['products'] = InventoryItem::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'sku', 'barcode', 'name', 'category', 'uom', 'cost_price', 'min_stock', 'max_stock', 'storage_location']);
            $payload['branches'] = CanonicalBranch::active()
                ->orderBy('branch_name')
                ->get(['branch_id', 'company_id', 'branch_code', 'branch_name', 'branch_type', 'branch_address_line_1', 'branch_city'])
                ->map(fn (CanonicalBranch $b) => [
                    'id' => (int) $b->branch_id,
                    'branch_id' => (int) $b->branch_id,
                    'company_id' => $b->company_id !== null ? (int) $b->company_id : null,
                    'code' => $b->branch_code,
                    'name' => $b->branch_name,
                    'type' => $b->branch_type,
                    'address' => trim(($b->branch_address_line_1 ?? '') . ($b->branch_city ? ', ' . $b->branch_city : ''), ', '),
                ])->values();
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * API: Single Transfer Detail with Live Source/Dest Branch Balances
     */
    public function apiGetInternalTransferDetail(int $id, StockTransferService $transferService): JsonResponse
    {
        $detail = $transferService->getTransferDetail($id);

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * API: O(1) Branch Stock Balance Map for Source Branch Selection
     */
    public function apiGetTransferBranchStock(Request $request, BranchStockService $branchStockService): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer|min:1',
        ]);

        $balances = $branchStockService->getBranchStockMap((int) $validated['branch_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'branch_id' => (int) $validated['branch_id'],
                'balances' => $balances,
            ],
        ]);
    }

    /**
     * API: 1-Click Low-Stock Pull Suggestions between Source and Destination Branches
     */
    public function apiGetTransferLowStockSuggestions(Request $request, StockTransferService $transferService): JsonResponse
    {
        $validated = $request->validate([
            'source_branch_id' => 'required|integer|min:1',
            'destination_branch_id' => 'required|integer|min:1|different:source_branch_id',
        ]);

        $suggestions = $transferService->getLowStockSuggestions(
            (int) $validated['source_branch_id'],
            (int) $validated['destination_branch_id']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $suggestions,
            ],
        ]);
    }

    /**
     * API: Create Inter-Branch Stock Request (Pull Workflow)
     */
    public function apiCreateTransferRequest(Request $request, StockTransferService $transferService): JsonResponse
    {
        $validated = $request->validate([
            'source_branch_id' => 'required|integer|exists:branches,branch_id',
            'destination_branch_id' => 'required|integer|exists:branches,branch_id|different:source_branch_id',
            'priority' => 'nullable|string|in:NORMAL,HIGH,URGENT,EMERGENCY',
            'reason_code' => 'nullable|string|max:120',
            'required_at' => 'nullable|date',
            'requisition_reference' => 'nullable|string|max:100',
            'requested_by' => 'nullable|string|max:255',
            'save_as_draft' => 'nullable|boolean',
            'idempotency_key' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.item_name' => 'nullable|string|max:255',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.uom_multiplier' => 'nullable|numeric|min:0.0001',
            'items.*.requested_qty' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $transfer = $transferService->createRequest($validated, auth()->user());

        return response()->json([
            'success' => true,
            'message' => "Stock Request {$transfer->transfer_number} ({$transfer->status}) created successfully.",
            'data' => [
                'transfer' => $transfer,
            ],
        ], 201);
    }

    /**
     * API: Create Custom Transfer (Direct Push from Source -> IN_TRANSIT; Destination accepts via Receipt)
     */
    public function apiCreateCustomTransfer(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): JsonResponse
    {
        $validated = $request->validate([
            'destination_branch_id' => 'nullable|integer',
            'destination_location' => 'required_without:destination_branch_id|nullable|string|max:255',
            'source_location' => 'nullable|string|max:255',
            'source_branch_id' => 'nullable|integer',
            'reason_code' => 'nullable|string|max:120',
            'required_at' => 'nullable|date',
            'dispatched_by' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'driver_plate' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'idempotency_key' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.inventory_item_id' => 'nullable|integer',
            'items.*.item_name' => 'nullable|string|max:255',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.uom_multiplier' => 'nullable|numeric|min:0.0001',
            'items.*.transferred_qty' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $activeBranch = $branchContext->getActiveBranch();
        $transfer = $transferService->createDirectPush(
            $validated,
            auth()->user(),
            $activeBranch ? (int) $activeBranch->branch_id : null,
            $branchContext->isConsolidatedView()
        );

        return response()->json([
            'success' => true,
            'message' => "Direct Transfer {$transfer->transfer_number} dispatched and in-transit. Awaiting destination branch acceptance.",
            'data' => [
                'transfer' => $transfer,
            ],
        ], 201);
    }

    /**
     * API: Source Branch Approves Request (Reserves Stock) or Approves & Dispatches Now
     */
    public function apiApproveTransfer(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|integer|exists:stock_transfer_header,id',
            'source_branch_id' => 'nullable|integer|exists:branches,branch_id',
            'approved_by' => 'nullable|string|max:255',
            'dispatch_immediately' => 'nullable|boolean',
            'create_backorder' => 'nullable|boolean',
            'carrier_name' => 'nullable|string|max:255',
            'driver_plate' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'items' => 'nullable|array',
            'items.*.sku' => 'required_with:items|string|max:100',
            'items.*.approved_qty' => 'required_with:items|numeric|min:0',
        ]);

        $activeBranch = $branchContext->getActiveBranch();
        $result = $transferService->approve(
            (int) $validated['transfer_id'],
            $validated,
            auth()->user(),
            $activeBranch ? (int) $activeBranch->branch_id : null,
            $branchContext->isConsolidatedView()
        );

        $msg = !empty($validated['dispatch_immediately'])
            ? "Transfer {$result['transfer']->transfer_number} approved & dispatched (In Transit). Awaiting destination receipt."
            : "Transfer {$result['transfer']->transfer_number} approved and source stock reserved.";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $result,
        ]);
    }

    /**
     * API: Source Branch Rejects Request
     */
    public function apiRejectTransfer(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|integer|exists:stock_transfer_header,id',
            'reason' => 'required|string|max:500',
        ]);

        $activeBranch = $branchContext->getActiveBranch();
        $transfer = $transferService->reject(
            (int) $validated['transfer_id'],
            $validated['reason'],
            auth()->user(),
            $activeBranch ? (int) $activeBranch->branch_id : null,
            $branchContext->isConsolidatedView()
        );

        return response()->json([
            'success' => true,
            'message' => "Transfer {$transfer->transfer_number} has been rejected.",
            'data' => [
                'transfer' => $transfer,
            ],
        ]);
    }

    /**
     * API: Source Branch Dispatches Approved/Requested Transfer -> IN_TRANSIT
     */
    public function apiDispatchTransfer(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|integer|exists:stock_transfer_header,id',
            'source_branch_id' => 'nullable|integer|exists:branches,branch_id',
            'dispatched_by' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'driver_plate' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'create_backorder' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.transferred_qty' => 'required|numeric|min:0',
        ]);

        $activeBranch = $branchContext->getActiveBranch();
        $result = $transferService->dispatch(
            (int) $validated['transfer_id'],
            $validated,
            auth()->user(),
            $activeBranch ? (int) $activeBranch->branch_id : null,
            $branchContext->isConsolidatedView()
        );

        $msg = "Transfer {$result['transfer']->transfer_number} dispatched and in-transit.";
        if (!empty($result['backorder'])) {
            $msg .= " Backorder {$result['backorder']->transfer_number} created for unfulfilled balance.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $result,
        ]);
    }

    /**
     * API: Destination Branch Accepts & Confirms Receipt (Credits Destination + Clears Transit)
     */
    public function apiReceiveTransfer(Request $request, StockTransferService $transferService, CompanyBranchContext $branchContext): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|integer|exists:stock_transfer_header,id',
            'received_by' => 'nullable|string|max:255',
            'allow_partial' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.sku' => 'required_with:items|string|max:100',
            'items.*.received_qty' => 'required_with:items|numeric|min:0',
            'items.*.variance_qty' => 'nullable|numeric|min:0',
            'items.*.variance_reason' => 'nullable|string|max:100',
        ]);

        $activeBranch = $branchContext->getActiveBranch();
        $transfer = $transferService->receive(
            (int) $validated['transfer_id'],
            $validated,
            auth()->user(),
            $activeBranch ? (int) $activeBranch->branch_id : null,
            $branchContext->isConsolidatedView()
        );

        return response()->json([
            'success' => true,
            'message' => "Transfer {$transfer->transfer_number} accepted under Receipt {$transfer->receipt_number} ({$transfer->status}).",
            'data' => [
                'transfer' => $transfer,
            ],
        ]);
    }

    /**
     * API: Cancel Internal Transfer (Releases Soft Reservations if Approved)
     */
    public function apiCancelTransfer(Request $request, StockTransferService $transferService): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|integer|exists:stock_transfer_header,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $transfer = $transferService->cancel(
            (int) $validated['transfer_id'],
            $validated['reason'] ?? 'Cancelled by warehouse supervisor',
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => "Transfer {$transfer->transfer_number} has been cancelled.",
            'data' => ['transfer' => $transfer],
        ]);
    }

    /**
     * API: Live Inter-Branch Due-To / Due-From Settlement Matrix
     */
    public function apiGetInterBranchSettlementMatrix(
        \App\Services\Inventory\InterBranchAccountingService $accountingService,
        CompanyBranchContext $branchContext
    ): JsonResponse {
        $company = $branchContext->getActiveCompany();
        $branch = $branchContext->getActiveBranch();

        $matrix = $accountingService->getSettlementMatrix(
            $company ? (int) $company->company_id : null,
            $branch ? (int) $branch->branch_id : null,
            $branchContext->isConsolidatedView()
        );

        return response()->json([
            'success' => true,
            'data' => $matrix,
        ]);
    }

    /**
     * API: 1-Click Settle Inter-Branch Due-To / Due-From Transfers (Posts GL Clearing Journal)
     */
    public function apiSettleInterBranchTransfers(
        Request $request,
        \App\Services\Inventory\InterBranchAccountingService $accountingService
    ): JsonResponse {
        $validated = $request->validate([
            'transfer_ids' => 'required|array|min:1',
            'transfer_ids.*' => 'integer|exists:stock_transfer_header,id',
        ]);

        $result = $accountingService->settleTransfers($validated['transfer_ids'], auth()->user());

        return response()->json([
            'success' => true,
            'message' => "Settled {$result['settled_count']} transfer(s) totaling ₱" . number_format($result['settled_amount'], 2) . '.',
            'data' => $result,
        ]);
    }

    public function stockAdjustment(): View
    {
        return view('inventory.stock-adjustment');
    }


    public function productCategories(): View
    {
        return view('inventory.product-categories');
    }

    public function recipeManagement(): View
    {
        $boms = BillOfMaterials::with(['finishedItem', 'items.rawItem'])->where('is_active', true)->orderBy('item_name')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();

        return view('inventory.recipe-management', [
            'initialBoms' => $boms,
            'initialProducts' => $products,
        ]);
    }

    /**
     * Configuration: Unit of Measure (UOM) Directory & Management
     */
    public function uomConfiguration(): View
    {
        $categories = UomCategory::orderBy('category_name')->get();
        $units = UnitOfMeasure::orderBy('uom_category')->orderBy('uom_code')->get();

        return view('inventory.uom-configuration', [
            'initialCategories' => $categories,
            'initialUnits' => $units,
        ]);
    }

    /**
     * JSON API: Get all active UOMs and Categories for cross-app hydration
     */
    public function apiGetUoms(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive') || $request->boolean('all');

        $categoriesQuery = UomCategory::query()->orderBy('category_name');
        if (!$includeInactive) {
            $categoriesQuery->where('category_is_active', true);
        }
        $categories = $categoriesQuery->get();

        $unitsQuery = UnitOfMeasure::query()->orderBy('uom_category')->orderBy('base_ratio');
        if (!$includeInactive) {
            $activeCategoryCodes = $categories->pluck('category_code')->toArray();
            $unitsQuery->where('uom_is_active', true)
                       ->whereIn('uom_category', $activeCategoryCodes);
        }
        $units = $unitsQuery->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
            'units' => $units,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * JSON API: Store new Unit of Measure
     */
    public function apiSaveUom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uom_code' => 'required|string|max:20|unique:units_of_measure,uom_code',
            'uom_name' => 'required|string|max:100',
            'uom_category' => 'required|string|max:50',
            'uom_type' => 'nullable|string|in:REFERENCE,GREATER,SMALLER',
            'base_ratio' => 'required|numeric|min:0.000001',
            'is_base_unit' => 'nullable|boolean',
            'rounding_precision' => 'nullable|numeric|min:0.0001',
        ]);

        $isBase = !empty($validated['is_base_unit']);

        // If marked as base unit, update any existing base unit in this category to false
        if ($isBase) {
            UnitOfMeasure::where('uom_category', $validated['uom_category'])
                ->where('is_base_unit', true)
                ->update(['is_base_unit' => false, 'uom_type' => 'GREATER']);
            
            UomCategory::where('category_code', $validated['uom_category'])
                ->update(['base_uom_code' => strtoupper($validated['uom_code'])]);
        }

        $userId = auth()->id() ?? null;

        $uom = UnitOfMeasure::create([
            'uom_id' => (string) Str::uuid(),
            'uom_code' => strtoupper(trim($validated['uom_code'])),
            'uom_name' => trim($validated['uom_name']),
            'uom_category' => strtoupper(trim($validated['uom_category'])),
            'uom_type' => $isBase ? 'REFERENCE' : ($validated['uom_type'] ?? 'GREATER'),
            'base_ratio' => $isBase ? 1.000000 : $validated['base_ratio'],
            'is_base_unit' => $isBase,
            'rounding_precision' => $validated['rounding_precision'] ?? 0.0100,
            'uom_is_active' => true,
            'uom_created_by' => $userId,
            'uom_updated_by' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Unit of measure '{$uom->uom_code}' successfully created.",
            'uom' => $uom,
        ]);
    }

    /**
     * JSON API: Update existing Unit of Measure
     */
    public function apiUpdateUom(Request $request, string $id): JsonResponse
    {
        $uom = UnitOfMeasure::findOrFail($id);

        $validated = $request->validate([
            'uom_code' => 'required|string|max:20|unique:units_of_measure,uom_code,' . $id . ',uom_id',
            'uom_name' => 'required|string|max:100',
            'uom_category' => 'required|string|max:50',
            'uom_type' => 'nullable|string|in:REFERENCE,GREATER,SMALLER',
            'base_ratio' => 'required|numeric|min:0.000001',
            'is_base_unit' => 'nullable|boolean',
            'rounding_precision' => 'nullable|numeric|min:0.0001',
            'uom_is_active' => 'nullable|boolean',
        ]);

        $isBase = !empty($validated['is_base_unit']);

        if ($isBase && !$uom->is_base_unit) {
            UnitOfMeasure::where('uom_category', $validated['uom_category'])
                ->where('is_base_unit', true)
                ->where('uom_id', '!=', $id)
                ->update(['is_base_unit' => false, 'uom_type' => 'GREATER']);

            UomCategory::where('category_code', $validated['uom_category'])
                ->update(['base_uom_code' => strtoupper($validated['uom_code'])]);
        }

        $userId = auth()->id() ?? null;

        $uom->update([
            'uom_code' => strtoupper(trim($validated['uom_code'])),
            'uom_name' => trim($validated['uom_name']),
            'uom_category' => strtoupper(trim($validated['uom_category'])),
            'uom_type' => $isBase ? 'REFERENCE' : ($validated['uom_type'] ?? $uom->uom_type),
            'base_ratio' => $isBase ? 1.000000 : $validated['base_ratio'],
            'is_base_unit' => $isBase,
            'rounding_precision' => $validated['rounding_precision'] ?? $uom->rounding_precision,
            'uom_is_active' => isset($validated['uom_is_active']) ? (bool) $validated['uom_is_active'] : $uom->uom_is_active,
            'uom_updated_by' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Unit of measure '{$uom->uom_code}' successfully updated.",
            'uom' => $uom,
        ]);
    }

    /**
     * JSON API: Toggle UOM active status
     */
    public function apiToggleUomActive(Request $request, string $id): JsonResponse
    {
        $uom = UnitOfMeasure::findOrFail($id);
        $uom->uom_is_active = !$uom->uom_is_active;
        $uom->uom_updated_by = auth()->id() ?? null;
        $uom->save();

        $statusStr = $uom->uom_is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Unit '{$uom->uom_code}' has been {$statusStr}.",
            'is_active' => $uom->uom_is_active,
            'uom' => $uom,
        ]);
    }

    /**
     * JSON API: Create New UOM Category
     */
    public function apiSaveUomCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_code' => 'required|string|max:50|unique:uom_categories,category_code',
            'category_name' => 'required|string|max:100',
            'category_description' => 'nullable|string|max:500',
            'base_uom_code' => 'nullable|string|max:20',
        ]);

        $userId = auth()->id() ?? null;

        $category = UomCategory::create([
            'category_id' => (string) Str::uuid(),
            'category_code' => strtoupper(trim($validated['category_code'])),
            'category_name' => trim($validated['category_name']),
            'category_description' => $validated['category_description'] ?? null,
            'base_uom_code' => !empty($validated['base_uom_code']) ? strtoupper(trim($validated['base_uom_code'])) : null,
            'category_is_active' => true,
            'category_created_by' => $userId,
            'category_updated_by' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Category '{$category->category_name}' successfully created.",
            'category' => $category,
        ]);
    }

    /**
     * JSON API: Toggle UOM Category active status and cascade deactivation to child units
     */
    public function apiToggleUomCategoryActive(Request $request, string $id): JsonResponse
    {
        $category = UomCategory::where('category_id', $id)
            ->orWhere('category_code', $id)
            ->firstOrFail();

        $category->category_is_active = !$category->category_is_active;
        $category->category_updated_by = auth()->id() ?? null;
        $category->save();

        $affectedCount = 0;
        // If category is deactivated, cascade deactivation to all child UOMs
        if (!$category->category_is_active) {
            $affectedCount = UnitOfMeasure::where('uom_category', $category->category_code)
                ->where('uom_is_active', true)
                ->update([
                    'uom_is_active' => false,
                    'uom_updated_by' => auth()->id() ?? null,
                    'uom_updated_at' => now(),
                ]);
        }

        $statusStr = $category->category_is_active ? 'activated' : 'deactivated';
        $message = $category->category_is_active
            ? "Category '{$category->category_name}' has been activated."
            : "Category '{$category->category_name}' has been deactivated and {$affectedCount} unit(s) archived.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_active' => $category->category_is_active,
            'category' => $category,
            'affected_units_count' => $affectedCount,
        ]);
    }

    /**
     * Production / Kitchen Assembly Module
     */
    public function production(Request $request): View
    {
        $productionOrders = ProductionOrder::with(['finishedItem', 'billOfMaterials', 'items.rawItem'])
            ->orderByDesc('id')
            ->take(50)
            ->get();

        $boms = BillOfMaterials::with(['finishedItem', 'items.rawItem'])
            ->where('is_active', true)
            ->orderBy('item_name')
            ->get();

        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        $completedBatches = ProductionOrder::where('status', 'COMPLETED')->count();
        $totalUnitsProduced = (float) ProductionOrder::where('status', 'COMPLETED')->sum('actual_quantity');
        $totalProductionValuation = (float) ProductionOrder::where('status', 'COMPLETED')->sum('total_production_cost');
        $avgYieldEfficiency = (float) (ProductionOrder::where('status', 'COMPLETED')->avg('yield_efficiency_percent') ?? 100);

        return view('inventory.production', [
            'initialOrders' => $productionOrders,
            'initialBoms' => $boms,
            'initialProducts' => $products,
            'initialCategories' => $categories,
            'stats' => [
                'completedBatches' => $completedBatches,
                'totalUnitsProduced' => $totalUnitsProduced,
                'totalProductionValuation' => $totalProductionValuation,
                'avgYieldEfficiency' => $avgYieldEfficiency,
            ],
        ]);
    }

    /**
     * API: Production Live Payload
     */
    public function apiGetProductionData(): JsonResponse
    {
        $productionOrders = ProductionOrder::with(['finishedItem', 'billOfMaterials', 'items.rawItem'])
            ->orderByDesc('id')
            ->take(100)
            ->get();

        $boms = BillOfMaterials::with(['finishedItem', 'items.rawItem'])
            ->where('is_active', true)
            ->orderBy('item_name')
            ->get();

        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();

        $completedBatches = ProductionOrder::where('status', 'COMPLETED')->count();
        $totalUnitsProduced = (float) ProductionOrder::where('status', 'COMPLETED')->sum('actual_quantity');
        $totalProductionValuation = (float) ProductionOrder::where('status', 'COMPLETED')->sum('total_production_cost');
        $avgYieldEfficiency = (float) (ProductionOrder::where('status', 'COMPLETED')->avg('yield_efficiency_percent') ?? 100);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $productionOrders,
                'boms' => $boms,
                'products' => $products,
                'stats' => [
                    'completedBatches' => $completedBatches,
                    'totalUnitsProduced' => $totalUnitsProduced,
                    'totalProductionValuation' => $totalProductionValuation,
                    'avgYieldEfficiency' => $avgYieldEfficiency,
                ],
            ],
        ]);
    }

    /**
     * API: Create and Complete a Production Batch
     */
    public function apiCreateProductionOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_of_materials_id' => 'required|exists:bom_header,id',
            'planned_quantity' => 'required|numeric|min:0.01',
            'actual_quantity' => 'nullable|numeric|min:0.01',
            'production_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'kitchen_station' => 'nullable|string|max:100',
            'produced_by' => 'required|string|max:255',
            'verified_by' => 'nullable|string|max:255',
            'proceed_with_shortage' => 'nullable|boolean',
            'quality_notes' => 'nullable|string|max:1000',
        ]);

        $bom = BillOfMaterials::with(['items.rawItem', 'finishedItem'])->findOrFail($validated['bill_of_materials_id']);
        $plannedQty = (float) $validated['planned_quantity'];
        $actualQty = (float) ($validated['actual_quantity'] ?? $plannedQty);
        $baseYield = (float) ($bom->yield_quantity ?: 1.0);
        $scale = $baseYield > 0 ? ($plannedQty / $baseYield) : 1.0;

        // Check raw material availability
        $shortages = [];
        foreach ($bom->items as $item) {
            $raw = $item->rawItem;
            if (!$raw) continue;
            $required = $scale * (float) $item->quantity;
            $available = (float) $raw->current_stock;
            if ($required > $available) {
                $shortages[] = [
                    'item_id' => $raw->id,
                    'sku' => $raw->sku,
                    'name' => $raw->name,
                    'uom' => $raw->uom,
                    'required_qty' => round($required, 4),
                    'available_stock' => round($available, 4),
                    'deficit' => round($required - $available, 4),
                ];
            }
        }

        $proceedWithShortage = !empty($validated['proceed_with_shortage']);
        if (!empty($shortages) && !$proceedWithShortage) {
            return response()->json([
                'success' => false,
                'has_shortage' => true,
                'shortages' => $shortages,
                'message' => 'Raw material shortage detected for ' . count($shortages) . ' ingredient(s).',
            ], 422);
        }

        return DB::transaction(function () use ($validated, $bom, $plannedQty, $actualQty, $scale, $shortages, $proceedWithShortage) {
            $yearMonth = date('Ymd');
            $count = ProductionOrder::whereDate('created_at', date('Y-m-d'))->count() + 1;
            $productionNumber = sprintf('PRD-%s-%04d', $yearMonth, $count);
            $batchLotNumber = sprintf('LOT-PRD-%s-%04d', $yearMonth, $count);

            $finishedItem = InventoryItem::where('id', $bom->finished_item_id)->lockForUpdate()->firstOrFail();

            $totalRawCost = 0.0;
            $laborCost = (float) $bom->labor_cost * $scale;
            $overheadCost = (float) $bom->overhead_cost * $scale;

            // Pre-calculate expiry date if not provided
            $productionDate = $validated['production_date'];
            $expiryDate = $validated['expiry_date'] ?? null;
            if (!$expiryDate && $bom->shelf_life_days > 0) {
                $expiryDate = date('Y-m-d', strtotime("+{$bom->shelf_life_days} days", strtotime($productionDate)));
            }

            $efficiencyPercent = $plannedQty > 0 ? round(($actualQty / $plannedQty) * 100, 2) : 100.00;

            // Shortage notes string
            $shortageNotes = null;
            if (!empty($shortages)) {
                $shortageNotes = implode(', ', array_map(function ($s) {
                    return "{$s['name']} (Deficit: -{$s['deficit']} {$s['uom']})";
                }, $shortages));
            }

            $order = ProductionOrder::create([
                'production_number' => $productionNumber,
                'batch_lot_number' => $batchLotNumber,
                'finished_item_id' => $finishedItem->id,
                'bill_of_materials_id' => $bom->id,
                'sku' => $finishedItem->sku,
                'item_name' => $finishedItem->name,
                'uom' => $finishedItem->uom,
                'status' => 'COMPLETED',
                'kitchen_station' => $validated['kitchen_station'] ?? 'Central Commissary - Prep Kitchen',
                'planned_quantity' => $plannedQty,
                'actual_quantity' => $actualQty,
                'yield_efficiency_percent' => $efficiencyPercent,
                'total_raw_cost' => 0.00,
                'labor_cost' => $laborCost,
                'overhead_cost' => $overheadCost,
                'total_production_cost' => 0.00,
                'unit_production_cost' => 0.00,
                'production_date' => $productionDate,
                'expiry_date' => $expiryDate,
                'produced_by' => $validated['produced_by'],
                'verified_by' => $validated['verified_by'] ?? null,
                'proceed_with_shortage' => $proceedWithShortage,
                'shortage_notes' => $shortageNotes,
                'quality_notes' => $validated['quality_notes'] ?? null,
            ]);

            $branchCtx = app(CompanyBranchContext::class);
            $activeBranch = $branchCtx->getActiveBranch();
            $branchId = $activeBranch ? (int) $activeBranch->branch_id : null;
            $companyId = $activeBranch?->company_id !== null ? (int) $activeBranch->company_id : null;
            $branchStockSvc = app(BranchStockService::class);

            // Deduct raw ingredients
            foreach ($bom->items as $bomItem) {
                $rawItem = InventoryItem::where('id', $bomItem->raw_item_id)->lockForUpdate()->firstOrFail();
                $consumedQty = $scale * (float) $bomItem->quantity;
                $unitCost = (float) $rawItem->cost_price;
                $lineCost = $consumedQty * $unitCost;
                $totalRawCost += $lineCost;

                $beforeStock = (float) $rawItem->current_stock;
                $afterStock = $beforeStock - $consumedQty;
                $rawItem->current_stock = $afterStock;
                $rawItem->save();

                if ($branchId !== null) {
                    $branchStockSvc->adjustBranchOnHand($branchId, (int) $rawItem->id, -$consumedQty, $unitCost, $companyId);
                }

                $isItemShortage = $consumedQty > $beforeStock;

                ProductionOrderItem::create([
                    'production_order_id' => $order->id,
                    'raw_item_id' => $rawItem->id,
                    'sku' => $rawItem->sku,
                    'item_name' => $rawItem->name,
                    'uom' => $rawItem->uom,
                    'bom_standard_qty' => $bomItem->quantity,
                    'required_qty' => $consumedQty,
                    'actual_consumed_qty' => $consumedQty,
                    'available_stock_before' => $beforeStock,
                    'is_shortage' => $isItemShortage,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                ]);

                // Append to immutable Stock Ledger (SUBTRACTION / PRODUCTION_OUT)
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'sku' => $rawItem->sku,
                    'inventory_item_id' => $rawItem->id,
                    'item_name' => $rawItem->name,
                    'transaction_type' => 'PRODUCTION_OUT',
                    'reference_type' => 'production_orders',
                    'reference_id' => $order->id,
                    'reference_no' => $productionNumber,
                    'before_quantity' => $beforeStock,
                    'quantity_change' => -$consumedQty,
                    'after_quantity' => $afterStock,
                    'unit_cost' => $unitCost,
                    'total_value' => $lineCost,
                    'batch_lot_no' => $batchLotNumber,
                    'expiry_date' => null,
                    'storage_location' => $rawItem->storage_location ?? 'Prep Kitchen',
                    'performed_by' => $validated['produced_by'],
                    'notes' => "Consumed in Production Batch #{$productionNumber} ({$batchLotNumber}) for {$finishedItem->name}",
                ]);
            }

            // Calculate total and unit production cost
            $totalProductionCost = $totalRawCost + $laborCost + $overheadCost;
            $unitProductionCost = $actualQty > 0 ? ($totalProductionCost / $actualQty) : 0.00;

            // Increment finished item stock (ADDITION / PRODUCTION_IN)
            $finBeforeStock = (float) $finishedItem->current_stock;
            $finAfterStock = $finBeforeStock + $actualQty;
            $finishedItem->current_stock = $finAfterStock;
            if ($unitProductionCost > 0) {
                $finishedItem->cost_price = $unitProductionCost;
            }
            $finishedItem->save();

            if ($branchId !== null) {
                $branchStockSvc->adjustBranchOnHand($branchId, (int) $finishedItem->id, +$actualQty, $unitProductionCost, $companyId);
            }

            // Append to immutable Stock Ledger (ADDITION / PRODUCTION_IN)
            StockLedger::create([
                'transaction_uuid' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'sku' => $finishedItem->sku,
                'inventory_item_id' => $finishedItem->id,
                'item_name' => $finishedItem->name,
                'transaction_type' => 'PRODUCTION_IN',
                'reference_type' => 'production_orders',
                'reference_id' => $order->id,
                'reference_no' => $productionNumber,
                'before_quantity' => $finBeforeStock,
                'quantity_change' => +$actualQty,
                'after_quantity' => $finAfterStock,
                'unit_cost' => $unitProductionCost,
                'total_value' => $totalProductionCost,
                'batch_lot_no' => $batchLotNumber,
                'expiry_date' => $expiryDate,
                'storage_location' => $finishedItem->storage_location ?? 'Main Storage / Finished Goods',
                'performed_by' => $validated['produced_by'],
                'notes' => "Output yield from Production Batch #{$productionNumber} ({$batchLotNumber})",
            ]);

            // Update order with computed valuation
            $order->update([
                'total_raw_cost' => $totalRawCost,
                'total_production_cost' => $totalProductionCost,
                'unit_production_cost' => $unitProductionCost,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Production batch {$productionNumber} completed successfully. Finished item stock incremented and materials consumed.",
                'data' => [
                    'order' => $order->load(['items', 'finishedItem', 'billOfMaterials']),
                ],
            ]);
        });
    }

    /**
     * API: Cancel a Production Batch
     */
    public function apiCancelProductionOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'production_order_id' => 'required|exists:work_order_header,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $order = ProductionOrder::findOrFail($validated['production_order_id']);
        if ($order->status === 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Completed production orders cannot be cancelled directly as stock has already been mutated.',
            ], 422);
        }

        $order->status = 'CANCELLED';
        $order->quality_notes = ($order->quality_notes ? $order->quality_notes . ' | ' : '') . 'Cancelled: ' . ($validated['reason'] ?? 'Cancelled by supervisor');
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Production Order {$order->production_number} has been cancelled.",
            'data' => ['order' => $order],
        ]);
    }

    /**
     * Waste & Expiry Module - Records all defects, waste, spoilage and expiry tracking
     */
    public function wasteExpiry(Request $request): View
    {
        $wasteRecords = WasteRecord::with(['items', 'branch'])->orderByDesc('id')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        // Calculate KPI Statistics
        $totalWasteValue = WasteRecord::where('status', '!=', 'CANCELLED')->sum('total_cost');
        $totalWasteItemsCount = WasteRecordItem::whereHas('wasteRecord', function ($q) {
            $q->where('status', '!=', 'CANCELLED');
        })->sum('quantity');

        $expiredCount = WasteRecordItem::whereHas('wasteRecord', function ($q) {
            $q->where('status', '!=', 'CANCELLED');
        })->where(function ($q) {
            $q->where('reason_code', 'like', '%Expire%')
              ->orWhere('reason_code', 'like', '%Shelf%');
        })->sum('quantity');

        $defectDamageCount = WasteRecordItem::whereHas('wasteRecord', function ($q) {
            $q->where('status', '!=', 'CANCELLED');
        })->where(function ($q) {
            $q->where('reason_code', 'like', '%Damage%')
              ->orWhere('reason_code', 'like', '%Defect%')
              ->orWhere('reason_code', 'like', '%Spoil%');
        })->sum('quantity');

        // Track at-risk batches/lots from recent receipts, production orders and waste items
        $trackedLots = StockLedger::whereNotNull('expiry_date')
            ->select('sku', 'item_name', 'batch_lot_no', 'expiry_date', 'storage_location', 'unit_cost')
            ->selectRaw('MAX(after_quantity) as est_quantity')
            ->whereDate('expiry_date', '>=', now()->subDays(30))
            ->groupBy('sku', 'item_name', 'batch_lot_no', 'expiry_date', 'storage_location', 'unit_cost')
            ->orderBy('expiry_date')
            ->get();

        if ($trackedLots->isEmpty()) {
            $trackedLots = GoodsReceiptItem::whereNotNull('expiry_date')
                ->select('sku', 'item_name', 'lot_number as batch_lot_no', 'expiry_date')
                ->selectRaw('received_qty as est_quantity, unit_cost')
                ->orderBy('expiry_date')
                ->get();
        }

        // Attach days remaining and risk status to tracked lots
        $now = now()->startOfDay();
        $atRiskLots = $trackedLots->map(function ($lot) use ($now) {
            $exp = \Carbon\Carbon::parse($lot->expiry_date)->startOfDay();
            $daysDiff = $now->diffInDays($exp, false);
            $lot->days_remaining = (int) $daysDiff;
            if ($daysDiff < 0) {
                $lot->risk_status = 'EXPIRED';
                $lot->badge_class = 'hr-badge-red';
            } elseif ($daysDiff <= 3) {
                $lot->risk_status = 'CRITICAL';
                $lot->badge_class = 'hr-badge-amber';
            } elseif ($daysDiff <= 7) {
                $lot->risk_status = 'WARNING';
                $lot->badge_class = 'hr-badge-purple';
            } else {
                $lot->risk_status = 'HEALTHY';
                $lot->badge_class = 'hr-badge-green';
            }
            return $lot;
        });

        return view('inventory.waste-expiry', [
            'wasteRecords' => $wasteRecords,
            'products' => $products,
            'categories' => $categories,
            'branches' => $branches,
            'atRiskLots' => $atRiskLots,
            'kpiStats' => [
                'totalWasteValue' => (float) $totalWasteValue,
                'totalWasteItemsCount' => (float) $totalWasteItemsCount,
                'expiredCount' => (float) $expiredCount,
                'defectDamageCount' => (float) $defectDamageCount,
            ],
        ]);
    }

    /**
     * API: Get Waste & Expiry Data Payload
     */
    public function apiGetWasteExpiryData(): JsonResponse
    {
        $wasteRecords = WasteRecord::with(['items', 'branch'])->orderByDesc('id')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();

        $totalWasteValue = WasteRecord::where('status', '!=', 'CANCELLED')->sum('total_cost');
        $totalWasteItemsCount = WasteRecordItem::whereHas('wasteRecord', function ($q) {
            $q->where('status', '!=', 'CANCELLED');
        })->sum('quantity');

        return response()->json([
            'success' => true,
            'data' => [
                'wasteRecords' => $wasteRecords,
                'products' => $products,
                'stats' => [
                    'totalWasteValue' => (float) $totalWasteValue,
                    'totalWasteItemsCount' => (float) $totalWasteItemsCount,
                ],
            ],
        ]);
    }

    /**
     * API: Create Waste / Defect / Spoilage Record
     * Transactional stock deduction, immutable StockLedger record
     */
    public function apiCreateWasteRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'waste_type' => 'required|in:EXPIRED,SPOILAGE,DAMAGED,DEFECTIVE,PREP_FALLOUT,STORAGE_FAILURE,OTHER',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'branch_name' => 'nullable|string|max:100',
            'storage_location' => 'nullable|string|max:100',
            'disposal_method' => 'required|string|max:100',
            'reported_by' => 'required|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'waste_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'force_override' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:item_master,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason_code' => 'required|string|max:100',
            'items.*.batch_lot_no' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.action_taken' => 'nullable|string|max:100',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // Generate sequential waste number
            $yearMonth = now()->format('Ym');
            $latestWaste = WasteRecord::where('waste_number', 'like', "WST-{$yearMonth}-%")
                ->orderByDesc('id')
                ->first();

            $nextSequence = 1;
            if ($latestWaste) {
                $parts = explode('-', $latestWaste->waste_number);
                $nextSequence = intval(end($parts)) + 1;
            }
            $wasteNumber = sprintf("WST-%s-%04d", $yearMonth, $nextSequence);

            $branchCtx = app(CompanyBranchContext::class);
            $activeBranch = $branchCtx->getActiveBranch();
            $effectiveBranchId = $validated['branch_id'] ?? ($activeBranch ? (int) $activeBranch->branch_id : null);
            $effectiveCompanyId = $activeBranch?->company_id !== null ? (int) $activeBranch->company_id : null;
            $branchStockSvc = app(BranchStockService::class);

            $wasteRecord = WasteRecord::create([
                'waste_number' => $wasteNumber,
                'waste_type' => $validated['waste_type'],
                'branch_id' => $effectiveBranchId,
                'branch_name' => $validated['branch_name'] ?? ($activeBranch?->branch_name ?? 'Central Commissary'),
                'storage_location' => $validated['storage_location'] ?? 'Main Storage / Dry Warehouse',
                'total_cost' => 0.00,
                'total_items_count' => count($validated['items']),
                'disposal_method' => $validated['disposal_method'],
                'status' => 'APPROVED',
                'reported_by' => $validated['reported_by'],
                'approved_by' => $validated['approved_by'] ?? $validated['reported_by'],
                'waste_date' => $validated['waste_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalCost = 0.00;

            foreach ($validated['items'] as $line) {
                $qty = (float) $line['quantity'];
                
                // Lock inventory item row
                $item = InventoryItem::where('id', $line['inventory_item_id'])->lockForUpdate()->firstOrFail();
                $beforeStock = (float) $item->current_stock;

                // Validate stock availability unless force_override is set
                if ($beforeStock < $qty && empty($request->input('force_override'))) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient physical stock for SKU {$item->sku} ({$item->name}). Available: {$beforeStock} {$item->uom}, requested write-off: {$qty} {$item->uom}.",
                    ], 422);
                }

                $afterStock = max(0, $beforeStock - $qty);
                $unitCost = (float) $item->cost_price;
                $lineTotalCost = $unitCost * $qty;
                $totalCost += $lineTotalCost;

                // Deduct stock from InventoryItem & Active Branch Balance
                $item->current_stock = $afterStock;
                $item->save();

                if ($effectiveBranchId !== null) {
                    $branchStockSvc->adjustBranchOnHand((int) $effectiveBranchId, (int) $item->id, -$qty, $unitCost, $effectiveCompanyId);
                }

                // Create WasteRecordItem line
                WasteRecordItem::create([
                    'waste_record_id' => $wasteRecord->id,
                    'inventory_item_id' => $item->id,
                    'sku' => $item->sku,
                    'item_name' => $item->name,
                    'category' => $item->category,
                    'uom' => $item->uom,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotalCost,
                    'reason_code' => $line['reason_code'],
                    'batch_lot_no' => $line['batch_lot_no'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'action_taken' => $line['action_taken'] ?? $validated['disposal_method'],
                    'notes' => $line['notes'] ?? null,
                ]);

                // Append to immutable Stock Ledger (SUBTRACTION / WASTE)
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'branch_id' => $effectiveBranchId,
                    'sku' => $item->sku,
                    'inventory_item_id' => $item->id,
                    'item_name' => $item->name,
                    'transaction_type' => 'WASTE',
                    'reference_type' => 'waste_records',
                    'reference_id' => $wasteRecord->id,
                    'reference_no' => $wasteNumber,
                    'before_quantity' => $beforeStock,
                    'quantity_change' => -$qty,
                    'after_quantity' => $afterStock,
                    'unit_cost' => $unitCost,
                    'total_value' => $lineTotalCost,
                    'batch_lot_no' => $line['batch_lot_no'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'storage_location' => $item->storage_location ?? $wasteRecord->storage_location,
                    'performed_by' => $validated['reported_by'],
                    'notes' => "Waste write-off [{$validated['waste_type']} - {$line['reason_code']}]: " . ($line['notes'] ?? 'Item disposed/damaged'),
                ]);
            }

            // Update total cost on header
            $wasteRecord->update([
                'total_cost' => $totalCost,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Waste record {$wasteNumber} successfully logged. Stock decremented by write-off quantity.",
                'data' => [
                    'wasteRecord' => $wasteRecord->load('items'),
                ],
            ]);
        });
    }

    /**
     * API: Update Waste Status (e.g. Approve, Mark Disposed, or Cancel)
     */
    public function apiUpdateWasteStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'waste_record_id' => 'required|exists:stock_waste_header,id',
            'status' => 'required|in:APPROVED,DISPOSED,CANCELLED',
            'notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated) {
            $record = WasteRecord::with('items')->findOrFail($validated['waste_record_id']);
            $oldStatus = $record->status;
            $newStatus = $validated['status'];

            // If cancelling an approved record, reverse stock deduction
            if ($newStatus === 'CANCELLED' && $oldStatus !== 'CANCELLED') {
                foreach ($record->items as $lineItem) {
                    $item = InventoryItem::where('id', $lineItem->inventory_item_id)->lockForUpdate()->first();
                    if ($item) {
                        $beforeStock = (float) $item->current_stock;
                        $restoreQty = (float) $lineItem->quantity;
                        $afterStock = $beforeStock + $restoreQty;

                        $item->current_stock = $afterStock;
                        $item->save();

                        StockLedger::create([
                            'transaction_uuid' => (string) Str::uuid(),
                            'sku' => $item->sku,
                            'inventory_item_id' => $item->id,
                            'item_name' => $item->name,
                            'transaction_type' => 'WASTE_REVERSAL',
                            'reference_type' => 'waste_records',
                            'reference_id' => $record->id,
                            'reference_no' => $record->waste_number,
                            'before_quantity' => $beforeStock,
                            'quantity_change' => +$restoreQty,
                            'after_quantity' => $afterStock,
                            'unit_cost' => $lineItem->unit_cost,
                            'total_value' => $lineItem->total_cost,
                            'batch_lot_no' => $lineItem->batch_lot_no,
                            'expiry_date' => $lineItem->expiry_date,
                            'storage_location' => $item->storage_location ?? 'Main Storage',
                            'performed_by' => auth()->user()->full_name ?? 'System Admin',
                            'notes' => "Reversal of cancelled waste record {$record->waste_number}",
                        ]);
                    }
                }
            }

            $record->status = $newStatus;
            if (!empty($validated['notes'])) {
                $record->notes = ($record->notes ? $record->notes . ' | ' : '') . $validated['notes'];
            }
            $record->save();

            return response()->json([
                'success' => true,
                'message' => "Waste record {$record->waste_number} status updated to {$newStatus}.",
                'data' => ['wasteRecord' => $record],
            ]);
        });
    }

    /**
     * Configuration Module 1: Warehouse & Storage Area
     */
    public function warehouseStorageAreas(): View
    {
        return view('inventory.warehouse-storage', [
            'initialWarehouses' => $this->getDefaultWarehousesPayload(),
            'initialStorageAreas' => $this->getDefaultStorageAreasPayload(),
            'initialBoundaryAreas' => $this->getDefaultBoundaryAreasPayload(),
        ]);
    }

    /**
     * Configuration Module 2: Movement Type & Supply Flows
     */
    public function movementSupplyFlows(): View
    {
        $categories = InventoryCategory::orderBy('name')->pluck('name')->values()->toArray();
        if (empty($categories)) {
            $categories = ['Dry Goods & Grains', 'Chilled & Dairy', 'Frozen Proteins', 'Beverages & Syrups', 'Packaging & Disposables'];
        }

        $products = InventoryItem::orderBy('name')
            ->limit(50)
            ->get(['sku', 'name', 'category'])
            ->toArray();

        return view('inventory.movement-supply-flows', [
            'initialWarehouses' => $this->getDefaultWarehousesPayload(),
            'initialStorageAreas' => $this->getDefaultStorageAreasPayload(),
            'initialBoundaryAreas' => $this->getDefaultBoundaryAreasPayload(),
            'initialMovementTypes' => $this->getDefaultMovementTypesPayload(),
            'initialSupplyFlows' => $this->getDefaultSupplyFlowsPayload(),
            'initialFlowRules' => $this->getDefaultFlowRulesPayload(),
            'initialCategories' => $categories,
            'initialProducts' => $products,
        ]);
    }

    /**
     * JSON API: Get Warehouses, Physical Storage Areas & Boundary Areas
     */
    public function apiGetWarehousesAndAreas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'warehouses' => $this->getDefaultWarehousesPayload(),
            'storageAreas' => $this->getDefaultStorageAreasPayload(),
            'boundaryAreas' => $this->getDefaultBoundaryAreasPayload(),
        ]);
    }

    /**
     * JSON API: Get Movement Types, Supply Flows & Flow Rules
     */
    public function apiGetMovementSupplyFlows(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'movementTypes' => $this->getDefaultMovementTypesPayload(),
            'supplyFlows' => $this->getDefaultSupplyFlowsPayload(),
            'flowRules' => $this->getDefaultFlowRulesPayload(),
        ]);
    }

    public static function getDefaultBoundaryAreasPayload(): array
    {
        return [
            [
                'id' => 'BND-SUPPLIER',
                'name' => 'Supplier / Vendor Origin',
                'code' => 'PARTNER-SUPPLIER',
                'barcode' => 'BND-SUPPLIER-00',
                'kind' => 'Boundary',
                'behavior' => 'Boundary',
                'description' => 'Virtual origin for vendor purchase receipts. Ledger-tracked only; holds no physical balance.',
            ],
            [
                'id' => 'BND-CUSTOMER',
                'name' => 'Customer / Branch Destination',
                'code' => 'PARTNER-CUSTOMER',
                'barcode' => 'BND-CUSTOMER-00',
                'kind' => 'Boundary',
                'behavior' => 'Boundary',
                'description' => 'Virtual destination for outbound dispatches & kitchen requisitions. Ledger-tracked only.',
            ],
            [
                'id' => 'BND-SCRAP',
                'name' => 'Scrap / Spoilage & Write-Off',
                'code' => 'VIRTUAL-SCRAP',
                'barcode' => 'BND-SCRAP-00',
                'kind' => 'Boundary',
                'behavior' => 'Boundary',
                'description' => 'Virtual sink for damaged, expired, or failed-QC inventory write-offs.',
            ],
            [
                'id' => 'BND-ADJUSTMENT',
                'name' => 'Inventory Cycle Adjustment',
                'code' => 'VIRTUAL-ADJUST',
                'barcode' => 'BND-ADJUST-00',
                'kind' => 'Boundary',
                'behavior' => 'Boundary',
                'description' => 'Virtual counterpart for physical count variances and opening balances.',
            ],
        ];
    }

    public static function getDefaultWarehousesPayload(): array
    {
        return [
            [
                'id' => 'WH-MAIN',
                'code' => 'WH-MAIN',
                'name' => 'Central Commissary & Dry Warehouse',
                'address' => 'Bldg 4 Industrial Logistics Park, Pasig Blvd, Metro Manila',
                'is_active' => true,
                'created_at' => '2026-01-10',
            ],
            [
                'id' => 'WH-COLD',
                'code' => 'WH-COLD',
                'name' => 'North Cold-Chain & Perishables Hub',
                'address' => 'Cold Storage Complex C-2, Mindanao Ave, Quezon City',
                'is_active' => true,
                'created_at' => '2026-02-04',
            ],
            [
                'id' => 'WH-BGC',
                'code' => 'WH-BGC',
                'name' => 'BGC Flagship Back-of-House Storeroom',
                'address' => 'High Street South Block, Bonifacio Global City, Taguig',
                'is_active' => true,
                'created_at' => '2026-03-15',
            ],
        ];
    }

    public static function getDefaultStorageAreasPayload(): array
    {
        return [
            // WH-MAIN Default Stock (Physical + Virtual) & Nested Storage Areas
            ['id' => 'AREA-MAIN-STK', 'warehouse_id' => 'WH-MAIN', 'parent_id' => null, 'location_kind' => 'Physical', 'name' => 'Stock Zone A (Dry Goods)', 'code' => 'ZONE-A', 'area_type' => 'Zone', 'behavior' => 'Storage', 'barcode' => 'WH-MAIN-ZONE-A', 'stock_qty' => 0, 'is_active' => true, 'is_default' => true],
            ['id' => 'AREA-MAIN-AA', 'warehouse_id' => 'WH-MAIN', 'parent_id' => 'AREA-MAIN-STK', 'location_kind' => 'Physical', 'name' => 'Aisle A — Bulk Staples & Coffee', 'code' => 'AISLE-A', 'area_type' => 'Aisle', 'behavior' => 'Storage', 'barcode' => 'WH-MAIN-ZA-AA', 'stock_qty' => 0, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-RA', 'warehouse_id' => 'WH-MAIN', 'parent_id' => 'AREA-MAIN-AA', 'location_kind' => 'Physical', 'name' => 'Rack A1 (Upper & Lower Tiers)', 'code' => 'RACK-A1', 'area_type' => 'Rack', 'behavior' => 'Storage', 'barcode' => 'WH-MAIN-ZA-AA-RA1', 'stock_qty' => 0, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-B01', 'warehouse_id' => 'WH-MAIN', 'parent_id' => 'AREA-MAIN-RA', 'location_kind' => 'Physical', 'name' => 'Bin A1-01 (Arabica Beans)', 'code' => 'BIN-A1-01', 'area_type' => 'Bin', 'behavior' => 'Storage', 'barcode' => 'WH-MAIN-ZA-RA1-B01', 'stock_qty' => 140, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-B02', 'warehouse_id' => 'WH-MAIN', 'parent_id' => 'AREA-MAIN-RA', 'location_kind' => 'Physical', 'name' => 'Bin A1-02 (Specialty Syrups)', 'code' => 'BIN-A1-02', 'area_type' => 'Bin', 'behavior' => 'Storage', 'barcode' => 'WH-MAIN-ZA-RA1-B02', 'stock_qty' => 85, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-B03', 'warehouse_id' => 'WH-MAIN', 'parent_id' => 'AREA-MAIN-RA', 'location_kind' => 'Physical', 'name' => 'Bin A1-03 (Spare Dry Bin)', 'code' => 'BIN-A1-03', 'area_type' => 'Bin', 'behavior' => 'Storage', 'barcode' => 'WH-MAIN-ZA-RA1-B03', 'stock_qty' => 0, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-QC', 'warehouse_id' => 'WH-MAIN', 'parent_id' => null, 'location_kind' => 'Physical', 'name' => 'Inbound QA & Inspection Bench', 'code' => 'QA-BENCH-01', 'area_type' => 'Packing Table', 'behavior' => 'Staging', 'barcode' => 'WH-MAIN-QA-B01', 'stock_qty' => 15, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-PACK', 'warehouse_id' => 'WH-MAIN', 'parent_id' => null, 'location_kind' => 'Physical', 'name' => 'Outbound Packing & Kitting Table', 'code' => 'PACK-TBL-01', 'area_type' => 'Packing Table', 'behavior' => 'Staging', 'barcode' => 'WH-MAIN-PACK-01', 'stock_qty' => 24, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-MAIN-VSTK', 'warehouse_id' => 'WH-MAIN', 'parent_id' => null, 'location_kind' => 'Virtual', 'name' => 'Virtual Stock Location (Main Ledger)', 'code' => 'VRT-STOCK', 'area_type' => 'Virtual Location', 'behavior' => 'Virtual', 'barcode' => 'WH-MAIN-VRT-STK', 'stock_qty' => 0, 'is_active' => true, 'is_default' => true],
            ['id' => 'AREA-MAIN-VADJ', 'warehouse_id' => 'WH-MAIN', 'parent_id' => null, 'location_kind' => 'Virtual', 'name' => 'Virtual Inventory Adjustment / Scrap', 'code' => 'VRT-ADJ', 'area_type' => 'Virtual Loss / Adj', 'behavior' => 'Virtual', 'barcode' => 'WH-MAIN-VRT-ADJ', 'stock_qty' => 0, 'is_active' => true, 'is_default' => false],

            // WH-COLD Default Stock (Physical + Virtual) & Nested Areas
            ['id' => 'AREA-COLD-STK', 'warehouse_id' => 'WH-COLD', 'parent_id' => null, 'location_kind' => 'Physical', 'name' => 'Stock Zone (Walk-In Freezer -18°C)', 'code' => 'ZONE-FREEZE', 'area_type' => 'Zone', 'behavior' => 'Storage', 'barcode' => 'WH-COLD-STK-ZONE', 'stock_qty' => 0, 'is_active' => true, 'is_default' => true],
            ['id' => 'AREA-COLD-R1', 'warehouse_id' => 'WH-COLD', 'parent_id' => 'AREA-COLD-STK', 'location_kind' => 'Physical', 'name' => 'Stainless Rack F1', 'code' => 'RACK-F1', 'area_type' => 'Rack', 'behavior' => 'Storage', 'barcode' => 'WH-COLD-ZF-RF1', 'stock_qty' => 0, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-COLD-B01', 'warehouse_id' => 'WH-COLD', 'parent_id' => 'AREA-COLD-R1', 'location_kind' => 'Physical', 'name' => 'Bin F1-01 (Prime Beef Cuts)', 'code' => 'BIN-F1-01', 'area_type' => 'Bin', 'behavior' => 'Storage', 'barcode' => 'WH-COLD-ZF-RF1-B01', 'stock_qty' => 62, 'is_active' => true, 'is_default' => false],
            ['id' => 'AREA-COLD-VSTK', 'warehouse_id' => 'WH-COLD', 'parent_id' => null, 'location_kind' => 'Virtual', 'name' => 'Virtual Cold Stock Location', 'code' => 'VRT-STOCK', 'area_type' => 'Virtual Location', 'behavior' => 'Virtual', 'barcode' => 'WH-COLD-VRT-STK', 'stock_qty' => 0, 'is_active' => true, 'is_default' => true],

            // WH-BGC Default Stock (Physical + Virtual)
            ['id' => 'AREA-BGC-STK', 'warehouse_id' => 'WH-BGC', 'parent_id' => null, 'location_kind' => 'Physical', 'name' => 'Stock Zone (BOH Dry & Line Pantry)', 'code' => 'ZONE-BOH', 'area_type' => 'Zone', 'behavior' => 'Storage', 'barcode' => 'WH-BGC-STK-ZONE', 'stock_qty' => 95, 'is_active' => true, 'is_default' => true],
            ['id' => 'AREA-BGC-VSTK', 'warehouse_id' => 'WH-BGC', 'parent_id' => null, 'location_kind' => 'Virtual', 'name' => 'Virtual BGC Stock Location', 'code' => 'VRT-STOCK', 'area_type' => 'Virtual Location', 'behavior' => 'Virtual', 'barcode' => 'WH-BGC-VRT-STK', 'stock_qty' => 0, 'is_active' => true, 'is_default' => true],
        ];
    }

    public static function getDefaultMovementTypesPayload(): array
    {
        return [
            [
                'id' => 'MT-MAIN-RCV',
                'code' => 'WH-MAIN-IN',
                'name' => 'Vendor Goods Receipt (Dry Goods)',
                'warehouse_id' => 'WH-MAIN',
                'operation_kind' => 'Inbound Receipt',
                'stock_effect' => 'Receipt',
                'route_preset' => '2-Step Staged',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Receive at Dock',
                        'from_area' => 'Supplier / Vendor Origin (Boundary)',
                        'to_area' => 'Receiving Dock',
                        'scan_source' => false,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => true,
                    ],
                    [
                        'step_no' => 2,
                        'name' => 'Put Away to Storage',
                        'from_area' => 'Receiving Dock',
                        'to_area' => 'Stock Zone A (Dry Goods)',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => false,
                    ],
                ],
            ],
            [
                'id' => 'MT-COLD-QA-RCV',
                'code' => 'WH-COLD-QA',
                'name' => 'Cold-Chain Meat & Dairy Inbound with QA',
                'warehouse_id' => 'WH-COLD',
                'operation_kind' => 'Inbound Receipt',
                'stock_effect' => 'Receipt',
                'route_preset' => '3-Step Quality Check',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Receive at Airlock',
                        'from_area' => 'Supplier / Vendor Origin (Boundary)',
                        'to_area' => 'Receiving Dock (Chilled Airlock)',
                        'scan_source' => false,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => true,
                    ],
                    [
                        'step_no' => 2,
                        'name' => 'Quality Check & Temp Probe',
                        'from_area' => 'Receiving Dock (Chilled Airlock)',
                        'to_area' => 'Inbound QA & Inspection Bench',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => true,
                    ],
                    [
                        'step_no' => 3,
                        'name' => 'Put Away to Freezer',
                        'from_area' => 'Inbound QA & Inspection Bench',
                        'to_area' => 'Stock Zone (Walk-In Freezer -18°C)',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => false,
                    ],
                ],
            ],
            [
                'id' => 'MT-BGC-DIRECT-RCV',
                'code' => 'WH-BGC-DIR',
                'name' => 'BGC Store Direct Pantry Delivery',
                'warehouse_id' => 'WH-BGC',
                'operation_kind' => 'Inbound Receipt',
                'stock_effect' => 'Receipt',
                'route_preset' => '1-Step Direct',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Direct Kitchen Delivery',
                        'from_area' => 'Supplier / Vendor Origin (Boundary)',
                        'to_area' => 'Stock Zone (BOH Dry & Line Pantry)',
                        'scan_source' => false,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => true,
                    ],
                ],
            ],
            [
                'id' => 'MT-KITCHEN-DIRECT',
                'code' => 'WH-KITCHEN-OUT',
                'name' => 'Direct Kitchen Line Issue (1-Step)',
                'warehouse_id' => 'WH-MAIN',
                'operation_kind' => 'Outbound Dispatch',
                'stock_effect' => 'Dispatch',
                'route_preset' => '1-Step Direct',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Issue from Storage to Kitchen Line',
                        'from_area' => 'Stock Zone A (Dry Goods)',
                        'to_area' => 'Main Kitchen - Hot Line (Saute & Grill)',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => false,
                        'auto_print_label' => false,
                    ],
                ],
            ],
            [
                'id' => 'MT-MAIN-DISPATCH',
                'code' => 'WH-MAIN-OUT',
                'name' => 'Branch Store Daily Delivery Dispatch',
                'warehouse_id' => 'WH-MAIN',
                'operation_kind' => 'Outbound Dispatch',
                'stock_effect' => 'Dispatch',
                'route_preset' => '2-Step Staged',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Pick from Bins to Packing Table',
                        'from_area' => 'Stock Zone A (Dry Goods)',
                        'to_area' => 'Outbound Packing & Kitting Table',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => false,
                    ],
                    [
                        'step_no' => 2,
                        'name' => 'Dispatch to Branch Logistics',
                        'from_area' => 'Outbound Packing & Kitting Table',
                        'to_area' => 'Customer / Branch Destination (Boundary)',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => false,
                        'auto_print_label' => true,
                    ],
                ],
            ],
            [
                'id' => 'MT-MAIN-PPS',
                'code' => 'WH-MAIN-PPS',
                'name' => 'Full Pick, Pack & Ship Fulfillment (3-Step)',
                'warehouse_id' => 'WH-MAIN',
                'operation_kind' => 'Outbound Dispatch',
                'stock_effect' => 'Dispatch',
                'route_preset' => '3-Step Pick-Pack-Ship',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Pick from Storage Bins',
                        'from_area' => 'Stock Zone A (Dry Goods)',
                        'to_area' => 'Picking Staging Cage',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => false,
                    ],
                    [
                        'step_no' => 2,
                        'name' => 'Pack & Kitting Verification',
                        'from_area' => 'Picking Staging Cage',
                        'to_area' => 'Outbound Packing & Kitting Table',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => true,
                    ],
                    [
                        'step_no' => 3,
                        'name' => 'Ship & Dispatch at Loading Bay',
                        'from_area' => 'Outbound Packing & Kitting Table',
                        'to_area' => 'Customer / Branch Destination (Boundary)',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => false,
                        'auto_print_label' => true,
                    ],
                ],
            ],
            [
                'id' => 'MT-MAIN-INTERNAL-TRF',
                'code' => 'WH-MAIN-TRF',
                'name' => 'Internal Warehouse Relocation (Bin to Bin)',
                'warehouse_id' => 'WH-MAIN',
                'operation_kind' => 'Internal Transfer',
                'stock_effect' => 'Transfer',
                'route_preset' => '1-Step Direct',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Relocate Stock Between Bins',
                        'from_area' => 'Stock Zone A (Dry Goods)',
                        'to_area' => 'Outbound Packing & Kitting Table',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => true,
                        'auto_print_label' => false,
                    ],
                ],
            ],
            [
                'id' => 'MT-MAIN-SCRAP',
                'code' => 'WH-MAIN-SCRAP',
                'name' => 'Waste, Spoilage & Scrap Write-Off',
                'warehouse_id' => 'WH-MAIN',
                'operation_kind' => 'Stock Adjustment',
                'stock_effect' => 'Adjustment',
                'route_preset' => '1-Step Direct',
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'name' => 'Dispose to Scrap Sink',
                        'from_area' => 'Stock Zone A (Dry Goods)',
                        'to_area' => 'Scrap / Spoilage & Write-Off (Boundary)',
                        'scan_source' => true,
                        'scan_product' => true,
                        'scan_destination' => false,
                        'auto_print_label' => false,
                    ],
                ],
            ],
        ];
    }

    private function getDefaultSupplyFlowsPayload(): array
    {
        return [
            [
                'id' => 'FLOW-OUT-2STEP',
                'code' => 'SF-OUT-2S',
                'name' => 'Standard 2-Step Outbound (Pick → Dispatch)',
                'warehouse_id' => 'WH-MAIN',
                'direction' => 'Outbound',
                'allocation_strategy' => 'FEFO (Earliest Expiry First)',
                'is_default' => true,
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'movement_type_id' => 'MT-PICK',
                        'movement_name' => 'Pick',
                        'from_area' => 'Stock Zone A (Dry Goods)',
                        'to_area' => 'Outbound Packing & Kitting Table',
                        'release_when' => 'Immediately',
                        'qty_source' => 'Original Order Demand',
                        'shortage_policy' => 'Create Backorder',
                        'branching' => null,
                    ],
                    [
                        'step_no' => 2,
                        'movement_type_id' => 'MT-DISPATCH',
                        'movement_name' => 'Dispatch',
                        'from_area' => 'Outbound Packing & Kitting Table',
                        'to_area' => 'Shipping Dock → Customer (Boundary)',
                        'release_when' => 'After Previous Fully Done',
                        'qty_source' => 'Actual Completed Qty from Previous Step',
                        'shortage_policy' => 'Create Backorder',
                        'branching' => null,
                    ],
                ],
            ],
            [
                'id' => 'FLOW-IN-2STEP',
                'code' => 'SF-IN-2S',
                'name' => 'Standard 2-Step Inbound (Receive → Put Away)',
                'warehouse_id' => 'WH-MAIN',
                'direction' => 'Inbound',
                'allocation_strategy' => 'FIFO (Oldest Receipt First)',
                'is_default' => true,
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'movement_type_id' => 'MT-RECEIVE',
                        'movement_name' => 'Receive',
                        'from_area' => 'Supplier / Vendor Origin (Boundary)',
                        'to_area' => 'Receiving Dock',
                        'release_when' => 'Immediately',
                        'qty_source' => 'Original Order Demand',
                        'shortage_policy' => 'Create Backorder',
                        'branching' => null,
                    ],
                    [
                        'step_no' => 2,
                        'movement_type_id' => 'MT-PUTAWAY',
                        'movement_name' => 'Put Away',
                        'from_area' => 'Receiving Dock',
                        'to_area' => 'Stock Zone A (Dry Goods)',
                        'release_when' => 'After Previous Partly Done',
                        'qty_source' => 'Actual Completed Qty from Previous Step',
                        'shortage_policy' => 'Cancel Remainder',
                        'branching' => null,
                    ],
                ],
            ],
            [
                'id' => 'FLOW-IN-3STEP-QC',
                'code' => 'SF-IN-3QC',
                'name' => 'Perishables 3-Step QA Inbound (Receive → QC → Put Away)',
                'warehouse_id' => 'WH-COLD',
                'direction' => 'Inbound',
                'allocation_strategy' => 'FEFO (Earliest Expiry First)',
                'is_default' => false,
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'movement_type_id' => 'MT-RECEIVE',
                        'movement_name' => 'Receive',
                        'from_area' => 'Supplier / Vendor Origin (Boundary)',
                        'to_area' => 'Receiving Dock (Chilled Airlock)',
                        'release_when' => 'Immediately',
                        'qty_source' => 'Original Order Demand',
                        'shortage_policy' => 'Create Backorder',
                        'branching' => null,
                    ],
                    [
                        'step_no' => 2,
                        'movement_type_id' => 'MT-QC',
                        'movement_name' => 'Quality Check',
                        'from_area' => 'Receiving Dock (Chilled Airlock)',
                        'to_area' => 'Inbound QA & Inspection Bench',
                        'release_when' => 'After Previous Partly Done',
                        'qty_source' => 'Actual Completed Qty from Previous Step',
                        'shortage_policy' => 'Cancel Remainder',
                        'branching' => [
                            'pass_exit' => 'Step 3: Put Away → Stock Zone (Walk-In Freezer -18°C)',
                            'fail_exit' => 'Exit to Scrap / Vendor Return Dock',
                        ],
                    ],
                    [
                        'step_no' => 3,
                        'movement_type_id' => 'MT-PUTAWAY',
                        'movement_name' => 'Put Away',
                        'from_area' => 'Inbound QA & Inspection Bench',
                        'to_area' => 'Stock Zone (Walk-In Freezer -18°C)',
                        'release_when' => 'After Previous Fully Done',
                        'qty_source' => 'Actual Completed Qty from Previous Step',
                        'shortage_policy' => 'Cancel Remainder',
                        'branching' => null,
                    ],
                ],
            ],
            [
                'id' => 'FLOW-IN-1STEP-DIRECT',
                'code' => 'SF-IN-1D',
                'name' => 'Express 1-Step BOH Direct Receipt',
                'warehouse_id' => 'WH-BGC',
                'direction' => 'Inbound',
                'allocation_strategy' => 'FEFO (Earliest Expiry First)',
                'is_default' => false,
                'is_active' => true,
                'steps' => [
                    [
                        'step_no' => 1,
                        'movement_type_id' => 'MT-RECEIVE',
                        'movement_name' => 'Receive',
                        'from_area' => 'Supplier / Vendor Origin (Boundary)',
                        'to_area' => 'Stock Zone (BOH Dry & Line Pantry)',
                        'release_when' => 'Immediately',
                        'qty_source' => 'Original Order Demand',
                        'shortage_policy' => 'Create Backorder',
                        'branching' => null,
                    ],
                ],
            ],
        ];
    }

    private function getDefaultFlowRulesPayload(): array
    {
        return [
            [
                'id' => 'RULE-01',
                'priority' => 10,
                'name' => 'Cold-Chain Proteins & Dairy Mandatory QA Gate',
                'warehouse_id' => 'WH-COLD',
                'order_type' => 'PO Inbound',
                'category' => 'Frozen Proteins',
                'product_sku' => '*',
                'partner' => '*',
                'flow_id' => 'FLOW-IN-3STEP-QC',
                'is_active' => true,
            ],
            [
                'id' => 'RULE-02',
                'priority' => 20,
                'name' => 'BGC Store Express Direct Pantry Receipt',
                'warehouse_id' => 'WH-BGC',
                'order_type' => 'PO Inbound',
                'category' => '*',
                'product_sku' => '*',
                'partner' => '*',
                'flow_id' => 'FLOW-IN-1STEP-DIRECT',
                'is_active' => true,
            ],
            [
                'id' => 'RULE-03',
                'priority' => 30,
                'name' => 'Central Commissary Standard 2-Step Inbound',
                'warehouse_id' => 'WH-MAIN',
                'order_type' => 'PO Inbound',
                'category' => '*',
                'product_sku' => '*',
                'partner' => '*',
                'flow_id' => 'FLOW-IN-2STEP',
                'is_active' => true,
            ],
            [
                'id' => 'RULE-04',
                'priority' => 40,
                'name' => 'Central Commissary 2-Step Pick & Dispatch Outbound',
                'warehouse_id' => 'WH-MAIN',
                'order_type' => 'Customer / Branch Outbound',
                'category' => '*',
                'product_sku' => '*',
                'partner' => '*',
                'flow_id' => 'FLOW-OUT-2STEP',
                'is_active' => true,
            ],
        ];
    }

    /**
     * =========================================================================
     * REORDERING RULES & REPLENISHMENT CONTROL TOWER
     * =========================================================================
     */

    /**
     * View: Reordering Rules Dashboard & Control Tower
     */
    public function reorderingRules(): View
    {
        $engine = new ReplenishmentEngine();
        $simulation = $engine->simulateRules();

        $branches = DB::table('hr_branches')
            ->select('branch_id', 'branch_name', 'branch_code')
            ->where('branch_is_active', 1)
            ->get();

        if ($branches->isEmpty()) {
            $branches = collect([
                (object) ['branch_id' => 1, 'branch_name' => 'Makati Flagship Branch', 'branch_code' => 'MAK-01'],
                (object) ['branch_id' => 2, 'branch_name' => 'BGC Taguig Branch', 'branch_code' => 'BGC-02'],
                (object) ['branch_id' => 3, 'branch_name' => 'Quezon City Branch', 'branch_code' => 'QC-03'],
            ]);
        }

        $items = DB::table('item_master')
            ->select('id', 'sku', 'name', 'category', 'uom', 'cost_price', 'current_stock')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $vendors = DB::table('vendor_master')
            ->select('id', 'vendor_code', 'legal_name')
            ->where('is_active', 1)
            ->orderBy('legal_name')
            ->get();

        $warehouses = DB::table('warehouses')
            ->select('warehouse_id', 'warehouse_code', 'warehouse_name')
            ->get();

        if ($warehouses->isEmpty()) {
            $warehouses = collect(self::getDefaultWarehousesPayload())->map(fn ($wh) => (object) [
                'warehouse_id' => $wh['id'],
                'warehouse_code' => $wh['code'],
                'warehouse_name' => $wh['name'],
            ]);
        }

        return view('inventory.reorder-rules', [
            'initialRules' => ReorderingRule::orderBy('branch_name')->orderBy('product_name')->get(),
            'initialSimulation' => $simulation,
            'branches' => $branches,
            'items' => $items,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * API: Get Reordering Rules Data & Simulator State
     */
    public function apiGetReorderRulesData(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id') ? (int) $request->query('branch_id') : null;
        $strategy = $request->query('strategy');

        $query = ReorderingRule::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($strategy) {
            $query->where('strategy', $strategy);
        }

        $rules = $query->orderBy('branch_name')->orderBy('product_name')->get();

        $engine = new ReplenishmentEngine();
        $simulation = $engine->simulateRules([], $branchId);

        return response()->json([
            'success' => true,
            'rules' => $rules,
            'simulation' => $simulation,
        ]);
    }

    /**
     * API: Save or Update Reordering Rule (Supports Single Rule OR Multi-SKU / Category Batch)
     */
    public function apiSaveReorderRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'rule_code' => 'nullable|string|max:50',
            'branch_id' => 'required|integer',
            'branch_name' => 'required|string|max:150',
            'product_id' => 'required_without:items_batch|nullable|integer',
            'product_sku' => 'required_without:items_batch|nullable|string|max:100',
            'product_name' => 'required_without:items_batch|nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'uom' => 'required_without:items_batch|nullable|string|max:50',
            'items_batch' => 'nullable|array|min:1',
            'items_batch.*.product_id' => 'required_with:items_batch|integer',
            'items_batch.*.product_sku' => 'required_with:items_batch|string|max:100',
            'items_batch.*.product_name' => 'required_with:items_batch|string|max:255',
            'items_batch.*.category' => 'nullable|string|max:100',
            'items_batch.*.uom' => 'required_with:items_batch|string|max:50',
            'strategy' => 'required|string|in:min_max,commissary_push,jit_back_to_back',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'required|numeric|gt:min_stock',
            'reorder_point' => 'nullable|numeric|min:0',
            'moq' => 'required|numeric|min:1',
            'safety_buffer' => 'nullable|numeric|min:0',
            'lead_time_days' => 'required|integer|min:0',
            'action_route' => 'required|string|in:purchase_order,internal_transfer',
            'preferred_vendor_id' => 'nullable|integer',
            'preferred_vendor_name' => 'nullable|string|max:255',
            'source_warehouse_id' => 'nullable|string|max:100',
            'source_warehouse_name' => 'nullable|string|max:255',
            'target_warehouse_id' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'auto_generate_draft' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $id = $validated['id'] ?? null;
        $batchItems = $validated['items_batch'] ?? null;

        $prefix = match ((int) $validated['branch_id']) {
            1 => 'MAK',
            2 => 'BGC',
            default => 'QC',
        };

        $generateUniqueRuleCode = function () use ($prefix): string {
            $seq = ReorderingRule::count() + 1;
            do {
                $candidate = 'RUL-' . $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $exists = ReorderingRule::where('rule_code', $candidate)->exists();
                $seq++;
            } while ($exists);
            return $candidate;
        };

        $sharedAttrs = [
            'branch_id' => (int) $validated['branch_id'],
            'branch_name' => $validated['branch_name'],
            'strategy' => $validated['strategy'],
            'min_stock' => $validated['min_stock'],
            'max_stock' => $validated['max_stock'],
            'reorder_point' => $validated['reorder_point'] ?? $validated['min_stock'],
            'moq' => $validated['moq'],
            'safety_buffer' => $validated['safety_buffer'] ?? 0,
            'lead_time_days' => $validated['lead_time_days'],
            'action_route' => $validated['action_route'],
            'preferred_vendor_id' => $validated['preferred_vendor_id'] ?? null,
            'preferred_vendor_name' => $validated['preferred_vendor_name'] ?? null,
            'source_warehouse_id' => $validated['source_warehouse_id'] ?? null,
            'source_warehouse_name' => $validated['source_warehouse_name'] ?? null,
            'target_warehouse_id' => $validated['target_warehouse_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'auto_generate_draft' => $request->boolean('auto_generate_draft', false),
            'notes' => $validated['notes'] ?? null,
            'last_evaluated_at' => now(),
        ];

        $savedRules = DB::transaction(function () use ($id, $batchItems, $validated, $sharedAttrs, $generateUniqueRuleCode) {
            if (!empty($batchItems) && !$id) {
                $results = [];
                foreach ($batchItems as $item) {
                    $existing = ReorderingRule::where('branch_id', $sharedAttrs['branch_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();

                    $attrs = array_merge($sharedAttrs, [
                        'product_id' => (int) $item['product_id'],
                        'product_sku' => $item['product_sku'],
                        'product_name' => $item['product_name'],
                        'category' => $item['category'] ?? 'General',
                        'uom' => $item['uom'],
                    ]);

                    if (!$existing) {
                        $attrs['rule_code'] = $generateUniqueRuleCode();
                    }

                    $results[] = ReorderingRule::updateOrCreate(
                        ['branch_id' => $sharedAttrs['branch_id'], 'product_id' => (int) $item['product_id']],
                        $attrs
                    );
                }
                return $results;
            }

            $attrs = array_merge($sharedAttrs, [
                'product_id' => (int) $validated['product_id'],
                'product_sku' => $validated['product_sku'],
                'product_name' => $validated['product_name'],
                'category' => $validated['category'] ?? 'General',
                'uom' => $validated['uom'],
            ]);

            if (!$id && empty($validated['rule_code'])) {
                $attrs['rule_code'] = $generateUniqueRuleCode();
            } elseif (!empty($validated['rule_code'])) {
                $attrs['rule_code'] = $validated['rule_code'];
            }

            return [ReorderingRule::updateOrCreate(['id' => $id], $attrs)];
        });

        $count = count($savedRules);
        $message = $count > 1
            ? "{$count} reordering rules applied across selected SKUs!"
            : ($id ? 'Reordering rule successfully updated!' : 'Reordering rule successfully created!');

        return response()->json([
            'success' => true,
            'message' => $message,
            'rule' => $savedRules[0] ?? null,
            'rules' => $savedRules,
        ]);
    }

    /**
     * API: Toggle Rule Active State
     */
    public function apiToggleReorderRule(Request $request, $id): JsonResponse
    {
        $rule = ReorderingRule::findOrFail($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();

        return response()->json([
            'success' => true,
            'message' => "Rule {$rule->rule_code} " . ($rule->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $rule->is_active,
        ]);
    }

    /**
     * API: Delete Reordering Rule
     */
    public function apiDeleteReorderRule(Request $request, $id): JsonResponse
    {
        $rule = ReorderingRule::findOrFail($id);
        $code = $rule->rule_code;
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => "Reordering rule {$code} successfully deleted.",
        ]);
    }

    /**
     * API: Run Live Replenishment Simulation Check
     */
    public function apiRunReplenishmentSimulation(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id') ? (int) $request->query('branch_id') : null;
        $ruleIds = $request->input('rule_ids', []);

        $engine = new ReplenishmentEngine();
        $simulation = $engine->simulateRules((array) $ruleIds, $branchId);

        return response()->json([
            'success' => true,
            'simulation' => $simulation,
        ]);
    }

    /**
     * API: Batch Generate Draft Purchase Orders & Transfers
     */
    public function apiBatchGenerateDrafts(Request $request): JsonResponse
    {
        $ruleIds = (array) $request->input('rule_ids', []);
        $performedBy = auth()->check() ? auth()->user()->name : 'Automated Replenishment Engine';

        $engine = new ReplenishmentEngine();
        $result = $engine->batchGenerateDrafts($ruleIds, $performedBy);

        return response()->json($result);
    }
}

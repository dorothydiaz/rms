<?php

namespace App\Http\Controllers;

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
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
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
     * Stocks Overview - Live inventory balances and audit ledger
     */
    public function stocksOverview(Request $request): View
    {
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();
        $ledger = StockLedger::orderByDesc('id')->take(50)->get();

        // Production movements aggregated per SKU
        $productionIn = StockLedger::where('transaction_type', 'PRODUCTION_IN')
            ->selectRaw('sku, SUM(quantity_change) as total_produced')
            ->groupBy('sku')
            ->pluck('total_produced', 'sku');

        $productionOut = StockLedger::where('transaction_type', 'PRODUCTION_OUT')
            ->selectRaw('sku, SUM(ABS(quantity_change)) as total_consumed')
            ->groupBy('sku')
            ->pluck('total_consumed', 'sku');

        // Waste & Expiry movements aggregated per SKU
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

        $products = $products->map(function ($p) use ($productionIn, $productionOut, $wasteAggregates) {
            $produced = (float) ($productionIn[$p->sku] ?? 0);
            $consumed = (float) ($productionOut[$p->sku] ?? 0);
            $p->production_produced = $produced;
            $p->production_consumed = $consumed;
            $p->production_net = $produced - $consumed;

            $wasteInfo = $wasteAggregates[$p->sku] ?? null;
            $p->waste_total = (float) ($wasteInfo->total_waste ?? 0);
            $p->waste_expired = (float) ($wasteInfo->total_expired ?? 0);
            $p->waste_damaged = (float) ($wasteInfo->total_damaged ?? 0);
            $p->waste_cost = (float) ($wasteInfo->total_cost ?? 0);
            return $p;
        });

        return view('inventory.stocks-overview', [
            'initialProducts' => $products,
            'initialCategories' => $categories,
            'initialLedger' => $ledger,
        ]);
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

        return view('inventory.stock-in', [
            'initialPurchaseOrders' => $purchaseOrders,
            'initialVendors' => $vendors,
            'initialProducts' => $products,
            'initialReceipts' => $recentReceipts,
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
            ],
        ]);
    }

    /**
     * API: Stocks Overview Live Payload
     */
    public function apiGetStocksOverviewData(): JsonResponse
    {
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();
        $ledger = StockLedger::orderByDesc('id')->take(100)->get();

        // Production movements aggregated per SKU
        $productionIn = StockLedger::where('transaction_type', 'PRODUCTION_IN')
            ->selectRaw('sku, SUM(quantity_change) as total_produced')
            ->groupBy('sku')
            ->pluck('total_produced', 'sku');

        $productionOut = StockLedger::where('transaction_type', 'PRODUCTION_OUT')
            ->selectRaw('sku, SUM(ABS(quantity_change)) as total_consumed')
            ->groupBy('sku')
            ->pluck('total_consumed', 'sku');

        // Waste & Expiry movements aggregated per SKU
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

        $products = $products->map(function ($p) use ($productionIn, $productionOut, $wasteAggregates) {
            $produced = (float) ($productionIn[$p->sku] ?? 0);
            $consumed = (float) ($productionOut[$p->sku] ?? 0);
            $p->production_produced = $produced;
            $p->production_consumed = $consumed;
            $p->production_net = $produced - $consumed;

            $wasteInfo = $wasteAggregates[$p->sku] ?? null;
            $p->waste_total = (float) ($wasteInfo->total_waste ?? 0);
            $p->waste_expired = (float) ($wasteInfo->total_expired ?? 0);
            $p->waste_damaged = (float) ($wasteInfo->total_damaged ?? 0);
            $p->waste_cost = (float) ($wasteInfo->total_cost ?? 0);
            return $p;
        });

        // Calculate summary KPI figures
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
                'categories' => $categories,
                'stocksLedger' => $ledger,
                'productionSummary' => [
                    'produced' => $productionIn,
                    'consumed' => $productionOut,
                ],
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
     * API: Receive Stock (PO fulfillment or Direct Receiving)
     * Strictly adheres to the 17-point Architectural Blueprint:
     * - Master Ledger System (SYS_LEDGER): Immutable append-only record with Before_Quantity & After_Quantity
     * - Atomic Database Transaction (Deferred Writes & Rollback Guard)
     * - Concurrency Guard: Lock rows with lockForUpdate()
     * - Bottom-up ledger state synchronization
     * - Server-side native user identity stamp
     */
    public function apiReceiveStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_number' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|string|max:100',
            'vendor_name' => 'required|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'delivery_slip' => 'nullable|string|max:100',
            'carrier' => 'nullable|string|max:255',
            'reason_code' => 'nullable|string|max:100',
            'delivery_location' => 'required|string|max:255',
            'settlement_mode' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'payment_ref' => 'nullable|string|max:100',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'fund_source' => 'nullable|string|max:255',
            'payment_remarks' => 'nullable|string|max:500',
            'freight' => 'nullable|numeric|min:0',
            'customs' => 'nullable|numeric|min:0',
            'handling' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.name' => 'required|string|max:255',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.purchasing_unit' => 'nullable|string|max:50',
            'items.*.uom_multiplier' => 'nullable|numeric|min:0.01',
            'items.*.received_qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.lot_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $year = date('Y');
            $count = GoodsReceipt::whereYear('created_at', $year)->count() + 1;
            $prefix = !empty($validated['po_number']) ? 'GRN' : 'DIR-REC';
            $grnNumber = sprintf('%s-%s-%04d', $prefix, $year, $count);

            $freight = (float) ($validated['freight'] ?? 0);
            $customs = (float) ($validated['customs'] ?? 0);
            $handling = (float) ($validated['handling'] ?? 0);
            $landedCosts = $freight + $customs + $handling;

            $itemsSubtotal = 0.00;
            foreach ($validated['items'] as $it) {
                $qty = (float) $it['received_qty'];
                $cost = (float) $it['unit_cost'];
                $itemsSubtotal += ($qty * $cost);
            }
            $grossTotal = $itemsSubtotal + $landedCosts;

            $amountPaid = (float) ($validated['amount_paid'] ?? 0);
            $paymentStatus = ($amountPaid >= $grossTotal && $grossTotal > 0) ? 'Paid in Full / Cash Out' : ($amountPaid > 0 ? 'Partial Payment' : 'Unpaid / Credit');
            $payRef = !empty($validated['payment_ref']) ? " (Ref: {$validated['payment_ref']})" : '';
            $userRemarks = !empty($validated['payment_remarks']) ? " | Remarks: {$validated['payment_remarks']}" : '';

            $po = null;
            if (!empty($validated['po_number'])) {
                $po = PurchaseOrder::where('po_number', $validated['po_number'])->lockForUpdate()->first();
            }

            // Stamped native user identity
            $performedBy = auth()->user()->name ?? auth()->user()->email ?? 'Warehouse Logistics Supervisor';

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
                'reason_code' => $validated['reason_code'] ?? 'Standard PO Delivery',
                'delivery_location' => $validated['delivery_location'],
                'settlement_mode' => $validated['settlement_mode'] ?? ($po ? 'PURCHASE_ORDER' : 'IMMEDIATE_COD'),
                'payment_method' => $validated['payment_method'] ?? 'Trade Credit (Net 30/15)',
                'payment_ref' => $validated['payment_ref'] ?? null,
                'freight_cost' => $freight,
                'customs_cost' => $customs,
                'handling_cost' => $handling,
                'total_landed_costs' => $landedCosts,
                'items_subtotal' => $itemsSubtotal,
                'gross_total' => $grossTotal,
                'received_at' => now(),
                'received_by' => $performedBy,
                'status' => 'COMPLETED',
                'notes' => ($po ? "Fulfillment for PO {$po->po_number}" : "Direct inbound stock receiving") . $userRemarks,
            ]);

            // Process line items & update inventory / ledger atomically
            foreach ($validated['items'] as $itemData) {
                $sku = trim($itemData['sku']);
                $receivedQty = (float) $itemData['received_qty'];
                $multiplier = (float) ($itemData['uom_multiplier'] ?? 1.0);
                if ($multiplier <= 0) $multiplier = 1.0;
                $baseQty = $receivedQty * $multiplier;
                $unitCost = (float) $itemData['unit_cost'];
                $totalCost = $receivedQty * $unitCost;

                // Lock the inventory item for atomic update
                $inventoryItem = InventoryItem::where('sku', $sku)->lockForUpdate()->first();
                if (!$inventoryItem) {
                    $inventoryItem = InventoryItem::create([
                        'sku' => $sku,
                        'name' => $itemData['name'],
                        'category' => 'Raw Ingredients',
                        'uom' => $itemData['unit'] ?? 'Kg',
                        'cost_price' => $unitCost,
                        'selling_price' => 0.00,
                        'current_stock' => 0.00,
                        'min_stock' => 10.00,
                        'max_stock' => 100.00,
                        'storage_location' => $validated['delivery_location'],
                        'is_active' => true,
                    ]);
                }

                $poItem = null;
                if ($po) {
                    $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->where('sku', $sku)
                        ->lockForUpdate()
                        ->first();

                    if ($poItem) {
                        $poItem->received_quantity += $receivedQty;
                        if ($poItem->received_quantity >= $poItem->quantity) {
                            $poItem->status = 'FULFILLED';
                        } else {
                            $poItem->status = 'PARTIAL';
                        }
                        $poItem->save();
                    }
                }

                // 1. Create Goods Receipt Item
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $poItem ? $poItem->id : null,
                    'sku' => $sku,
                    'item_name' => $itemData['name'],
                    'uom' => $itemData['unit'] ?? $inventoryItem->uom,
                    'purchasing_uom' => $itemData['purchasing_unit'] ?? $itemData['unit'] ?? $inventoryItem->uom,
                    'uom_multiplier' => $multiplier,
                    'received_qty' => $receivedQty,
                    'base_qty' => $baseQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'lot_number' => $itemData['lot_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // 2. Master Ledger System (SYS_LEDGER) - Append-Only Immutable Record
                $beforeQty = (float) $inventoryItem->current_stock;
                $afterQty = $beforeQty + $baseQty;

                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'sku' => $sku,
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'transaction_type' => $po ? 'PURCHASE_RECEIPT' : 'DIRECT_RECEIVING',
                    'reference_type' => 'goods_receipts',
                    'reference_id' => $receipt->id,
                    'reference_no' => $receipt->grn_number,
                    'before_quantity' => $beforeQty,
                    'quantity_change' => $baseQty,
                    'after_quantity' => $afterQty,
                    'unit_cost' => $unitCost,
                    'total_value' => round($baseQty * $unitCost, 2),
                    'batch_lot_no' => $itemData['lot_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'storage_location' => $validated['delivery_location'],
                    'performed_by' => $performedBy,
                    'notes' => "Dock intake under {$receipt->grn_number}" . ($po ? " (PO: {$po->po_number})" : ""),
                    'created_at' => now(),
                ]);

                // 3. Update Current Physical Stock & Cost
                $inventoryItem->current_stock = $afterQty;
                if ($unitCost > 0) {
                    $inventoryItem->cost_price = $unitCost;
                }
                $inventoryItem->save();
            }

            // Update Purchase Order overall fulfillment status
            if ($po) {
                $allPoItems = PurchaseOrderItem::where('purchase_order_id', $po->id)->get();
                $allFulfilled = $allPoItems->every(function ($it) {
                    return (float) $it->received_quantity >= (float) $it->quantity;
                });
                $anyReceived = $allPoItems->some(function ($it) {
                    return (float) $it->received_quantity > 0;
                });

                if ($allFulfilled) {
                    $po->status = 'Fully Received';
                } elseif ($anyReceived) {
                    $po->status = 'Partially Received';
                }
                $po->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully received into inventory. Voucher {$receipt->grn_number} created and posted to Stock Ledger.",
                'data' => [
                    'grn_number' => $receipt->grn_number,
                    'po_number' => $receipt->po_number,
                    'received_at' => $receipt->received_at->toIso8601String(),
                    'gross_total' => $receipt->gross_total,
                    'amount_paid' => $amountPaid,
                    'balance_due' => max(0, $grossTotal - $amountPaid),
                    'payment_status' => $paymentStatus,
                    'items_count' => count($validated['items']),
                ],
            ]);
        });
    }

    public function begBalance(): View
    {
        return view('inventory.beg-balance');
    }

    public function stockOut(): View
    {
        $orders = StockOutOrder::with(['items', 'vendor'])->orderByDesc('id')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        return view('inventory.stock-out', [
            'initialOrders' => $orders,
            'initialProducts' => $products,
            'initialVendors' => $vendors,
            'initialCategories' => $categories,
        ]);
    }

    /**
     * API: Stock Out Initial Hydration Payload
     */
    public function apiGetStockOutData(): JsonResponse
    {
        $orders = StockOutOrder::with(['items', 'vendor'])->orderByDesc('id')->get();
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
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            $year = date('Y');
            $count = StockOutOrder::whereYear('created_at', $year)->count() + 1;
            $orderNumber = sprintf('SO-%s-%04d', $year, $count);

            $totalItemsCount = count($validated['items']);
            $totalRequestedQty = 0.00;
            $totalCostValue = 0.00;

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['requested_qty'];
                $cost = (float) $item['unit_cost'];
                $totalRequestedQty += $qty;
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
                'total_packed_qty' => 0.00,
                'total_cost_value' => $totalCostValue,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $invItem = null;
                if (!empty($item['inventory_item_id'])) {
                    $invItem = InventoryItem::find($item['inventory_item_id']);
                } elseif (!empty($item['sku'])) {
                    $invItem = InventoryItem::where('sku', $item['sku'])->first();
                }

                $availableStock = $invItem ? (float) $invItem->current_stock : 0.00;
                $qty = (float) $item['requested_qty'];
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
                    'packed_qty' => 0.00,
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
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
            'order_id' => 'required|exists:stock_out_orders,id',
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
            'order_id' => 'required|exists:stock_out_orders,id',
            'picker_name' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'tracking_waybill' => 'nullable|string|max:100',
            'items' => 'nullable|array',
            'items.*.sku' => 'required_with:items|string|max:100',
            'items.*.packed_qty' => 'required_with:items|numeric|min:0',
            'items.*.batch_lot_no' => 'nullable|string|max:100',
        ]);

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

            $totalShippedQty = 0.00;
            $totalActualCost = 0.00;

            foreach ($order->items as $item) {
                $qtyToDeduct = (float) $item->packed_qty;
                if ($qtyToDeduct <= 0) {
                    continue; // Skip items not packed
                }

                $inventoryItem = InventoryItem::where('sku', $item->sku)->lockForUpdate()->first();
                if (!$inventoryItem) {
                    throw new \RuntimeException("Inventory item with SKU {$item->sku} not found in Item Master.");
                }

                $beforeQty = (float) $inventoryItem->current_stock;
                $afterQty = $beforeQty - $qtyToDeduct;

                // Update inventory_items current stock
                $inventoryItem->current_stock = $afterQty;
                $inventoryItem->save();

                // Append to immutable StockLedger
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
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

            // Update order status to SHIPPED
            $order->update([
                'status' => 'SHIPPED',
                'dispatched_at' => now(),
                'picker_name' => $validated['picker_name'] ?? $order->picker_name ?? $performedBy,
                'carrier_name' => $validated['carrier_name'] ?? $order->carrier_name,
                'tracking_waybill' => $validated['tracking_waybill'] ?? $order->tracking_waybill,
                'total_packed_qty' => $totalShippedQty,
                'total_cost_value' => $totalActualCost,
            ]);

            $order->load(['items', 'vendor']);

            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_number} successfully dispatched & inventory deducted.",
                'data' => [
                    'order' => $order,
                    'dispatched_at' => $order->dispatched_at->format('Y-m-d H:i:s'),
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
            'order_id' => 'required|exists:stock_out_orders,id',
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
     * Internal Transfer Module (Masterlist, Pending Requests & Custom Push)
     */
    public function internalTransfer(): View
    {
        $transfers = InternalTransfer::with(['items', 'sourceBranch', 'destinationBranch'])->orderByDesc('id')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        return view('inventory.internal-transfer', [
            'initialTransfers' => $transfers,
            'initialProducts' => $products,
            'initialBranches' => $branches,
            'initialCategories' => $categories,
        ]);
    }

    /**
     * API: Internal Transfer Data Payload
     */
    public function apiGetInternalTransferData(): JsonResponse
    {
        $transfers = InternalTransfer::with(['items', 'sourceBranch', 'destinationBranch'])->orderByDesc('id')->get();
        $products = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'totalTransfers' => $transfers->count(),
            'pendingRequestsCount' => $transfers->where('status', 'PENDING')->count(),
            'inTransitCount' => $transfers->where('status', 'IN_TRANSIT')->count(),
            'completedCount' => $transfers->where('status', 'COMPLETED')->count(),
            'totalValuation' => (float) $transfers->whereIn('status', ['IN_TRANSIT', 'COMPLETED'])->sum('total_valuation'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'transfers' => $transfers,
                'products' => $products,
                'branches' => $branches,
                'categories' => $categories,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * API: Create Custom Transfer (Direct Push without Branch Requisition)
     * Performs strict quantity validation against Item Master stock on hand.
     */
    public function apiCreateCustomTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_branch_id' => 'nullable|integer',
            'destination_location' => 'required|string|max:255',
            'source_location' => 'required|string|max:255',
            'source_branch_id' => 'nullable|integer',
            'reason_code' => 'nullable|string|max:100',
            'dispatched_by' => 'required|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'driver_plate' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.inventory_item_id' => 'nullable|integer',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.transferred_qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Strict Stock Quantity Validation Guard
            foreach ($validated['items'] as $itemData) {
                $sku = $itemData['sku'];
                $qty = (float) $itemData['transferred_qty'];
                $invItem = InventoryItem::where('sku', $sku)->lockForUpdate()->first();

                if (!$invItem) {
                    return response()->json([
                        'success' => false,
                        'message' => "Item with SKU '{$sku}' not found in Item Master.",
                    ], 422);
                }

                $availableStock = (float) $invItem->current_stock;
                if ($qty > $availableStock) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for '{$invItem->name}' (SKU: {$sku}). Requested transfer quantity ({$qty}) exceeds available on-hand stock ({$availableStock} {$invItem->uom}).",
                    ], 422);
                }
            }

            // 2. Generate Transfer Reference Number
            $year = date('Y');
            $count = InternalTransfer::whereYear('created_at', $year)->count() + 1;
            $transferNumber = sprintf('DIR-TRF-%s-%04d', $year, $count);

            $totalItemsCount = count($validated['items']);
            $totalTransferredQty = 0.00;
            $totalValuation = 0.00;

            foreach ($validated['items'] as $it) {
                $q = (float) $it['transferred_qty'];
                $c = (float) $it['unit_cost'];
                $totalTransferredQty += $q;
                $totalValuation += ($q * $c);
            }

            // 3. Create InternalTransfer Header
            $transfer = InternalTransfer::create([
                'transfer_number' => $transferNumber,
                'transfer_type' => 'CUSTOM_PUSH',
                'status' => 'IN_TRANSIT',
                'priority' => $validated['priority'] ?? 'NORMAL',
                'source_location' => $validated['source_location'],
                'source_branch_id' => $validated['source_branch_id'] ?? null,
                'destination_location' => $validated['destination_location'],
                'destination_branch_id' => $validated['destination_branch_id'] ?? null,
                'reason_code' => $validated['reason_code'] ?? 'Commissary Bulk Batch Push',
                'dispatched_by' => $validated['dispatched_by'],
                'carrier_name' => $validated['carrier_name'] ?? null,
                'driver_plate' => $validated['driver_plate'] ?? null,
                'waybill_number' => $validated['waybill_number'] ?? null,
                'dispatched_at' => now(),
                'total_items_count' => $totalItemsCount,
                'total_requested_qty' => $totalTransferredQty,
                'total_transferred_qty' => $totalTransferredQty,
                'total_received_qty' => 0.00,
                'total_valuation' => $totalValuation,
                'notes' => $validated['notes'] ?? null,
            ]);

            $performedBy = auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? $validated['dispatched_by'];

            // 4. Create Items & Atomically Deduct Stock from Central Inventory
            foreach ($validated['items'] as $itemData) {
                $sku = $itemData['sku'];
                $qty = (float) $itemData['transferred_qty'];
                $unitCost = (float) $itemData['unit_cost'];

                $invItem = InventoryItem::where('sku', $sku)->lockForUpdate()->first();
                $beforeQty = (float) $invItem->current_stock;
                $afterQty = $beforeQty - $qty;

                // Update stock balance
                $invItem->current_stock = $afterQty;
                $invItem->save();

                // Create Transfer Item
                InternalTransferItem::create([
                    'internal_transfer_id' => $transfer->id,
                    'inventory_item_id' => $invItem->id,
                    'sku' => $sku,
                    'item_name' => $itemData['item_name'],
                    'category' => $itemData['category'] ?? $invItem->category,
                    'uom' => $itemData['uom'] ?? $invItem->uom,
                    'source_available_stock' => $beforeQty,
                    'requested_qty' => $qty,
                    'transferred_qty' => $qty,
                    'received_qty' => 0.00,
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
                    'storage_location' => $itemData['storage_location'] ?? $invItem->storage_location,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Append Immutable Master Ledger Entry
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'sku' => $sku,
                    'inventory_item_id' => $invItem->id,
                    'item_name' => $invItem->name,
                    'transaction_type' => 'STOCK_OUT',
                    'reference_type' => 'internal_transfers',
                    'reference_id' => $transfer->id,
                    'reference_no' => $transfer->transfer_number,
                    'before_quantity' => $beforeQty,
                    'quantity_change' => -$qty,
                    'after_quantity' => $afterQty,
                    'unit_cost' => $unitCost,
                    'total_value' => $qty * $unitCost,
                    'storage_location' => $invItem->storage_location,
                    'performed_by' => $performedBy,
                    'notes' => "Direct Transfer to {$transfer->destination_location} [{$transfer->reason_code}]",
                ]);
            }

            $transfer->load(['items', 'sourceBranch', 'destinationBranch']);

            return response()->json([
                'success' => true,
                'message' => "Custom Direct Transfer {$transferNumber} dispatched successfully.",
                'data' => [
                    'transfer' => $transfer,
                ],
            ], 201);
        });
    }

    /**
     * API: Dispatch Pending Branch Transfer Request
     */
    public function apiDispatchTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|exists:internal_transfers,id',
            'dispatched_by' => 'required|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'driver_plate' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:100',
            'items.*.transferred_qty' => 'required|numeric|min:0.001',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $transfer = InternalTransfer::where('id', $validated['transfer_id'])->lockForUpdate()->firstOrFail();

            if (in_array($transfer->status, ['IN_TRANSIT', 'COMPLETED', 'CANCELLED'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Transfer {$transfer->transfer_number} is already in {$transfer->status} status.",
                ], 422);
            }

            // Quantity validation guard
            foreach ($validated['items'] as $itemData) {
                $sku = $itemData['sku'];
                $qty = (float) $itemData['transferred_qty'];
                $invItem = InventoryItem::where('sku', $sku)->lockForUpdate()->first();

                if (!$invItem) {
                    return response()->json([
                        'success' => false,
                        'message' => "Item with SKU '{$sku}' not found in Item Master.",
                    ], 422);
                }

                $availableStock = (float) $invItem->current_stock;
                if ($qty > $availableStock) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for '{$invItem->name}' (SKU: {$sku}). Transfer quantity ({$qty}) exceeds available on-hand stock ({$availableStock} {$invItem->uom}).",
                    ], 422);
                }
            }

            $performedBy = auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? $validated['dispatched_by'];
            $totalDispatched = 0.00;
            $totalVal = 0.00;

            foreach ($validated['items'] as $itemData) {
                $sku = $itemData['sku'];
                $qty = (float) $itemData['transferred_qty'];

                $lineItem = InternalTransferItem::where('internal_transfer_id', $transfer->id)
                    ->where('sku', $sku)
                    ->first();

                $invItem = InventoryItem::where('sku', $sku)->lockForUpdate()->first();
                $beforeQty = (float) $invItem->current_stock;
                $afterQty = $beforeQty - $qty;

                // Decrement stock
                $invItem->current_stock = $afterQty;
                $invItem->save();

                if ($lineItem) {
                    $lineItem->transferred_qty = $qty;
                    $lineItem->total_cost = $qty * (float)$lineItem->unit_cost;
                    $lineItem->save();
                    $unitCost = (float) $lineItem->unit_cost;
                } else {
                    $unitCost = (float) $invItem->cost_price;
                }

                $totalDispatched += $qty;
                $totalVal += ($qty * $unitCost);

                // Immutable Master Ledger
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'sku' => $sku,
                    'inventory_item_id' => $invItem->id,
                    'item_name' => $invItem->name,
                    'transaction_type' => 'STOCK_OUT',
                    'reference_type' => 'internal_transfers',
                    'reference_id' => $transfer->id,
                    'reference_no' => $transfer->transfer_number,
                    'before_quantity' => $beforeQty,
                    'quantity_change' => -$qty,
                    'after_quantity' => $afterQty,
                    'unit_cost' => $unitCost,
                    'total_value' => $qty * $unitCost,
                    'storage_location' => $invItem->storage_location,
                    'performed_by' => $performedBy,
                    'notes' => "Dispatched Transfer Requisition to {$transfer->destination_location}",
                ]);
            }

            $transfer->update([
                'status' => 'IN_TRANSIT',
                'dispatched_by' => $validated['dispatched_by'],
                'carrier_name' => $validated['carrier_name'] ?? $transfer->carrier_name,
                'driver_plate' => $validated['driver_plate'] ?? $transfer->driver_plate,
                'waybill_number' => $validated['waybill_number'] ?? $transfer->waybill_number,
                'dispatched_at' => now(),
                'total_transferred_qty' => $totalDispatched,
                'total_valuation' => $totalVal,
            ]);

            $transfer->load(['items', 'sourceBranch', 'destinationBranch']);

            return response()->json([
                'success' => true,
                'message' => "Transfer {$transfer->transfer_number} dispatched and in-transit.",
                'data' => [
                    'transfer' => $transfer,
                ],
            ]);
        });
    }

    /**
     * API: Confirm Receipt of Internal Transfer at Destination Branch
     */
    public function apiReceiveTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|exists:internal_transfers,id',
            'received_by' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            $transfer = InternalTransfer::where('id', $validated['transfer_id'])->lockForUpdate()->firstOrFail();

            if ($transfer->status === 'COMPLETED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfer is already marked as COMPLETED.',
                ], 422);
            }

            $transfer->update([
                'status' => 'COMPLETED',
                'received_by' => $validated['received_by'],
                'received_at' => now(),
                'total_received_qty' => $transfer->total_transferred_qty,
                'notes' => ($transfer->notes ? $transfer->notes . ' | ' : '') . 'Received: ' . ($validated['notes'] ?? 'Acknowledged by destination branch'),
            ]);

            // Set all items received_qty = transferred_qty
            foreach ($transfer->items as $item) {
                $item->received_qty = $item->transferred_qty;
                $item->save();
            }

            $transfer->load(['items', 'sourceBranch', 'destinationBranch']);

            return response()->json([
                'success' => true,
                'message' => "Transfer {$transfer->transfer_number} marked as COMPLETED.",
                'data' => [
                    'transfer' => $transfer,
                ],
            ]);
        });
    }

    /**
     * API: Cancel Internal Transfer
     */
    public function apiCancelTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transfer_id' => 'required|exists:internal_transfers,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $transfer = InternalTransfer::findOrFail($validated['transfer_id']);
        if ($transfer->status === 'IN_TRANSIT' || $transfer->status === 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a transfer that has already been dispatched and deducted from inventory.',
            ], 422);
        }

        $transfer->status = 'CANCELLED';
        $transfer->notes = ($transfer->notes ? $transfer->notes . ' | ' : '') . 'Cancelled: ' . ($validated['reason'] ?? 'Cancelled by warehouse supervisor');
        $transfer->save();

        return response()->json([
            'success' => true,
            'message' => "Transfer {$transfer->transfer_number} has been cancelled.",
            'data' => ['transfer' => $transfer],
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
            'bill_of_materials_id' => 'required|exists:bill_of_materials,id',
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

            // Append to immutable Stock Ledger (ADDITION / PRODUCTION_IN)
            StockLedger::create([
                'transaction_uuid' => (string) Str::uuid(),
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
            'production_order_id' => 'required|exists:production_orders,id',
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
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
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

            $wasteRecord = WasteRecord::create([
                'waste_number' => $wasteNumber,
                'waste_type' => $validated['waste_type'],
                'branch_id' => $validated['branch_id'] ?? null,
                'branch_name' => $validated['branch_name'] ?? 'Central Commissary',
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

                // Deduct stock from InventoryItem
                $item->current_stock = $afterStock;
                $item->save();

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
            'waste_record_id' => 'required|exists:waste_records,id',
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
}

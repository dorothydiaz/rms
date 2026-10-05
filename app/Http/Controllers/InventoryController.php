<?php

namespace App\Http\Controllers;

use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\GoodsReceiptItem;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\StockOutOrder;
use App\Models\Inventory\StockOutOrderItem;
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
        $recentReceipts = GoodsReceipt::with('items')->orderByDesc('id')->take(20)->get();

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
        $recentReceipts = GoodsReceipt::with('items')->orderByDesc('id')->take(20)->get();

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
                'notes' => $po ? "Fulfillment for PO {$po->poNumber}" : "Direct inbound stock receiving",
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

    public function stockAdjustment(): View
    {
        return view('inventory.stock-adjustment');
    }

    public function wasteExpiry(): View
    {
        return view('inventory.waste-expiry');
    }

    public function productCategories(): View
    {
        return view('inventory.product-categories');
    }

    public function recipeManagement(): View
    {
        return view('inventory.recipe-management');
    }
}

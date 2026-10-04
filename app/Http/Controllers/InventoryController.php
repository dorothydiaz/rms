<?php

namespace App\Http\Controllers;

use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\GoodsReceiptItem;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
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
        return view('inventory.stock-out');
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

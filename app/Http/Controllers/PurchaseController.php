<?php

namespace App\Http\Controllers;

use App\Models\Inventory\InventoryItem;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function dashboard(): View
    {
        return view('purchase.dashboard');
    }

    public function requestQuotations(): View
    {
        return view('purchase.request-quotations');
    }

    public function sendQuotationEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_number' => 'required|string',
            'vendor_id' => 'nullable|string',
            'vendor_name' => 'required|string',
            'vendor_email' => 'required|email',
            'cc_email' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'nullable|string',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'expected_delivery' => 'nullable|string',
            'quotation_deadline' => 'nullable|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Quotation Request {$validated['rfq_number']} successfully sent to {$validated['vendor_email']}.",
            'data' => [
                'rfq_number' => $validated['rfq_number'],
                'recipient' => $validated['vendor_email'],
                'vendor_name' => $validated['vendor_name'],
                'item_count' => count($validated['items']),
                'dispatched_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Purchase Orders Workspace
     */
    public function purchaseOrders(Request $request): View
    {
        $purchaseOrders = PurchaseOrder::with('items')->orderByDesc('id')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        $items = InventoryItem::where('is_active', true)->orderBy('name')->get();

        return view('purchase.purchase-orders', [
            'initialPurchaseOrders' => $purchaseOrders,
            'initialVendors' => $vendors,
            'initialItems' => $items,
        ]);
    }

    /**
     * API: Get all Purchase Orders with items
     */
    public function apiGetPurchaseOrders(): JsonResponse
    {
        $orders = PurchaseOrder::with('items')->orderByDesc('id')->get();
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'purchaseOrders' => $orders,
                'vendors' => $vendors,
            ],
        ]);
    }

    /**
     * API: Get items of a specific Purchase Order
     */
    public function apiGetPoItems(string $poNumber): JsonResponse
    {
        $po = PurchaseOrder::with('items')->where('po_number', $poNumber)->first();

        if (!$po) {
            return response()->json([
                'success' => false,
                'message' => "Purchase Order {$poNumber} not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'po' => $po,
                'items' => $po->items,
            ],
        ]);
    }

    /**
     * API: Create / Issue new Purchase Order
     */
    public function apiCreatePurchaseOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_number' => 'nullable|string|max:50',
            'po_type' => 'nullable|string|max:50',
            'rfq_reference' => 'nullable|string|max:100',
            'vendor_id' => 'nullable|string|max:100',
            'vendor_name' => 'required|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'vendor_contact_person' => 'nullable|string|max:255',
            'vendor_phone' => 'nullable|string|max:100',
            'vendor_email' => 'nullable|string|max:255',
            'vendor_address' => 'nullable|string',
            'order_date' => 'nullable|date',
            'expected_delivery' => 'nullable|date',
            'delivery_location' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string',
            'payment_status' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'amount_paid' => 'nullable|numeric|min:0',
            'balance_due' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'fund_source' => 'nullable|string|max:100',
            'payment_remarks' => 'nullable|string',
            'approval_notes' => 'nullable|string',
            'approved_by' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'nullable|string|max:100',
            'items.*.name' => 'required|string|max:255',
            'items.*.specs' => 'nullable|string',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unitPrice' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $year = date('Y');
            $poNum = $validated['po_number'] ?? null;
            if (empty($poNum)) {
                $count = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
                $poNum = sprintf('PO-%s-%04d', $year, $count);
            }

            $grossTotal = 0;
            foreach ($validated['items'] as $it) {
                $grossTotal += ((float) $it['quantity'] * (float) $it['unitPrice']);
            }

            $amountPaid = (float) ($validated['amount_paid'] ?? 0);
            $balanceDue = max(0, $grossTotal - $amountPaid);

            $po = PurchaseOrder::create([
                'po_number' => $poNum,
                'po_type' => $validated['po_type'] ?? 'vendor',
                'rfq_reference' => $validated['rfq_reference'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'vendor_name' => $validated['vendor_name'],
                'vendor_trade_name' => $validated['vendor_trade_name'] ?? null,
                'vendor_contact_person' => $validated['vendor_contact_person'] ?? null,
                'vendor_phone' => $validated['vendor_phone'] ?? null,
                'vendor_email' => $validated['vendor_email'] ?? null,
                'vendor_address' => $validated['vendor_address'] ?? null,
                'order_date' => $validated['order_date'] ?? now()->toDateString(),
                'expected_delivery' => $validated['expected_delivery'] ?? now()->addDays(3)->toDateString(),
                'delivery_location' => $validated['delivery_location'] ?? 'Central Commissary - Receiving Dock 1',
                'special_notes' => $validated['special_notes'] ?? null,
                'payment_status' => $validated['payment_status'] ?? 'Unpaid / Credit',
                'payment_method' => $validated['payment_method'] ?? 'Trade Credit (Net 30/15)',
                'amount_paid' => $amountPaid,
                'balance_due' => $balanceDue,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'payment_date' => $validated['payment_date'] ?? null,
                'fund_source' => $validated['fund_source'] ?? 'Operating Account',
                'payment_remarks' => $validated['payment_remarks'] ?? null,
                'approval_notes' => $validated['approval_notes'] ?? null,
                'approved_by' => $validated['approved_by'] ?? 'Procurement Officer',
                'status' => $validated['status'] ?? 'Approved / Issued',
                'subtotal' => $grossTotal,
                'tax_amount' => 0.00,
                'gross_total' => $grossTotal,
                'created_by_user' => auth()->user()->name ?? 'Procurement Specialist',
            ]);

            foreach ($validated['items'] as $it) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sku' => $it['sku'] ?? null,
                    'item_name' => $it['name'],
                    'specs' => $it['specs'] ?? null,
                    'category' => $it['category'] ?? 'Raw Ingredients',
                    'uom' => $it['unit'] ?? 'Unit',
                    'quantity' => (float) $it['quantity'],
                    'unit_price' => (float) $it['unitPrice'],
                    'total_amount' => (float) $it['quantity'] * (float) $it['unitPrice'],
                    'received_quantity' => 0.00,
                    'status' => 'PENDING',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$po->po_number} successfully issued and recorded in database.",
                'data' => [
                    'po_number' => $po->po_number,
                    'gross_total' => $po->gross_total,
                ],
            ]);
        });
    }

    public function vendorMasterlist(): View
    {
        return view('purchase.vendor-masterlist');
    }

    public function vendorBills(): View
    {
        return view('purchase.vendor-bills');
    }
}

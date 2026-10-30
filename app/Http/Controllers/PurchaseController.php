<?php

namespace App\Http\Controllers;

use App\Models\Inventory\InventoryItem;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use App\Services\RfqAlternativeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    protected RfqAlternativeService $rfqAlternativeService;

    public function __construct(RfqAlternativeService $rfqAlternativeService)
    {
        $this->rfqAlternativeService = $rfqAlternativeService;
    }

    public function dashboard(): View
    {
        return view('purchase.dashboard');
    }

    public function requestQuotations(): View
    {
        // Windowed initial payload (most recent 30 records for instant DOM render)
        $rfqs = RfqHeader::with(['lines', 'vendor', 'purchaseOrder'])->orderByDesc('rfq_created_at')->take(30)->get();
        $vendors = \Illuminate\Support\Facades\Cache::remember('active_procurement_vendors', 3600, function () {
            return ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        });
        $items = \Illuminate\Support\Facades\Cache::remember('active_inventory_items_lookup', 3600, function () {
            return InventoryItem::where('is_active', true)->orderBy('name')->get();
        });
        $linkedPoMap = PurchaseOrder::whereNotNull('rfq_reference')
            ->whereNotIn('status', ['Cancelled', 'Archived'])
            ->pluck('po_number', 'rfq_reference')
            ->toArray();

        return view('purchase.request-quotations', [
            'initialRfqs' => $rfqs,
            'initialVendors' => $vendors,
            'initialItems' => $items,
            'linkedPoMap' => $linkedPoMap,
        ]);
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
        // Windowed initial payload (most recent 30 records for instant DOM render)
        $purchaseOrders = PurchaseOrder::with('items')->orderByDesc('id')->take(30)->get();
        $vendors = \Illuminate\Support\Facades\Cache::remember('active_procurement_vendors', 3600, function () {
            return ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();
        });
        $items = \Illuminate\Support\Facades\Cache::remember('active_inventory_items_lookup', 3600, function () {
            return InventoryItem::where('is_active', true)->orderBy('name')->get();
        });

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
            'vendor_id' => 'nullable',
            'vendor_name' => 'required|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'vendor_contact_person' => 'nullable|string|max:255',
            'vendor_phone' => 'nullable|string|max:100',
            'vendor_email' => 'nullable|string|max:255',
            'vendor_address' => 'nullable|string',
            'order_date' => 'nullable|string|max:50',
            'expected_delivery' => 'nullable|string|max:50',
            'delivery_location' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string',
            'payment_status' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'amount_paid' => 'nullable|numeric|min:0',
            'balance_due' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|string|max:50',
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

            $po = PurchaseOrder::updateOrCreate(
                ['po_number' => $poNum],
                [
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
                ]
            );

            PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();

            foreach ($validated['items'] as $it) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sku' => $it['sku'] ?? null,
                    'item_name' => $it['name'],
                    'specs' => $it['specs'] ?? null,
                    'category' => $it['category'] ?? 'Raw Ingredients',
                    'uom' => $it['unit'] ?? 'PCS',
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
                    'status' => $po->status,
                    'po' => $po->load('items'),
                ],
            ]);
        });
    }

    /**
     * API: Get all RFQs with lines
     */
    public function apiGetRfqs(): JsonResponse
    {
        $rfqs = RfqHeader::with(['lines', 'vendor'])->orderByDesc('rfq_created_at')->get();
        return response()->json([
            'success' => true,
            'data' => $rfqs,
        ]);
    }

    /**
     * API: Save or update RFQ Draft (Enforces Draft-only edits)
     */
    public function apiSaveRfqDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_number' => 'required|string|max:50',
            'rfq_date' => 'nullable|date',
            'vendor_id' => 'nullable|string',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_contact_id' => 'nullable|string',
            'submission_deadline' => 'nullable|date',
            'quote_valid_until_date' => 'nullable|date',
            'rfq_requested_delivery_date' => 'nullable|date',
            'rfq_currency_code' => 'nullable|string|max:3',
            'rfq_payment_terms_code' => 'nullable|string|max:30',
            'rfq_incoterms_code' => 'nullable|string|max:10',
            'rfq_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|string',
            'items.*.item_name' => 'nullable|string',
            'items.*.purchase_uom_code' => 'nullable|string',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.target_unit_price' => 'nullable|numeric|min:0',
            'items.*.rfq_line_notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $rfq = RfqHeader::where('rfq_number', $validated['rfq_number'])->first();

            if ($rfq && in_array(strtoupper($rfq->rfq_status), ['SUBMITTED', 'APPROVED', 'AWARDED', 'REFUSED'])) {
                return response()->json([
                    'success' => false,
                    'message' => "RFQ {$rfq->rfq_number} is locked in {$rfq->rfq_status} status and cannot be edited.",
                ], 422);
            }

            if (!$rfq) {
                $rfq = new RfqHeader();
                $rfq->rfq_id = (string) Str::uuid();
                $rfq->rfq_number = $validated['rfq_number'];
                $rfq->rfq_status = 'DRAFT';
                $rfq->rfq_approval_status = 'PENDING';
            }

            $rfq->rfq_date = $validated['rfq_date'] ?? now()->toDateString();
            $rfq->vendor_id = $validated['vendor_id'] ?? $rfq->vendor_id;
            $rfq->vendor_contact_id = $validated['vendor_contact_id'] ?? $rfq->vendor_contact_id;
            $rfq->submission_deadline = $validated['submission_deadline'] ?? null;
            $rfq->quote_valid_until_date = $validated['quote_valid_until_date'] ?? null;
            $rfq->rfq_requested_delivery_date = $validated['rfq_requested_delivery_date'] ?? null;
            $rfq->rfq_currency_code = $validated['rfq_currency_code'] ?? 'PHP';
            $rfq->rfq_payment_terms_code = $validated['rfq_payment_terms_code'] ?? 'NET30';
            $rfq->rfq_incoterms_code = $validated['rfq_incoterms_code'] ?? 'FOB';
            $rfq->rfq_notes = $validated['rfq_notes'] ?? null;

            $estTotal = 0.0;
            foreach ($validated['items'] as $itemData) {
                $qty = (float) $itemData['requested_quantity'];
                $price = (float) ($itemData['target_unit_price'] ?? 0.0);
                $estTotal += ($qty * $price);
            }
            $rfq->rfq_estimated_total_amount = $estTotal;
            $rfq->save();

            // Recreate lines
            RfqLine::where('rfq_id', $rfq->rfq_id)->delete();
            $lineIdx = 1;
            foreach ($validated['items'] as $itemData) {
                $qty = (float) $itemData['requested_quantity'];
                $price = (float) ($itemData['target_unit_price'] ?? 0.0);
                RfqLine::create([
                    'rfq_line_id' => (string) Str::uuid(),
                    'rfq_id' => $rfq->rfq_id,
                    'line_number' => $lineIdx++,
                    'item_id' => $itemData['item_id'],
                    'requested_quantity' => $qty,
                    'quoted_quantity' => $itemData['quoted_quantity'] ?? null,
                    'purchase_uom_code' => $itemData['purchase_uom_code'] ?? 'PCS',
                    'target_unit_price' => $price,
                    'quoted_unit_price' => $itemData['quoted_unit_price'] ?? 0.0,
                    'quoted_line_total' => isset($itemData['quoted_quantity'], $itemData['quoted_unit_price'])
                        ? ((float)$itemData['quoted_quantity'] * (float)$itemData['quoted_unit_price'])
                        : 0.0,
                    'lead_time_days' => $itemData['lead_time_days'] ?? 0,
                    'is_awarded' => false,
                    'rfq_line_notes' => $itemData['rfq_line_notes'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "RFQ {$rfq->rfq_number} draft successfully saved.",
                'data' => $rfq->load('lines'),
            ]);
        });
    }

    /**
     * API: Transition RFQ Status along FSM:
     * DRAFT -> SUBMITTED -> APPROVAL -> APPROVED / REFUSED / REVISED
     */
    public function apiTransitionRfqStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_number' => 'required|string',
            'target_status' => 'required|string',
            'vendor_id' => 'nullable|string',
            'vendor_name' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            'revision_notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.line_id' => 'nullable|string',
            'items.*.item_id' => 'nullable|string',
            'items.*.requested_quantity' => 'nullable|numeric|min:0',
            'items.*.target_unit_price' => 'nullable|numeric|min:0',
            'items.*.purchase_uom_code' => 'nullable|string',
            'items.*.quoted_quantity' => 'nullable|numeric|min:0',
            'items.*.quoted_unit_price' => 'nullable|numeric|min:0',
            'items.*.lead_time_days' => 'nullable|integer|min:0',
            'items.*.rfq_line_notes' => 'nullable|string',
        ]);

        $rfq = RfqHeader::where('rfq_number', $validated['rfq_number'])->first();
        $targetStatus = strtoupper($validated['target_status']);

        if (!$rfq) {
            // Auto-create newly submitted or transitioning RFQ record if not previously persisted
            $initialStatus = in_array($targetStatus, ['SUBMITTED', 'DRAFT'])
                ? 'DRAFT'
                : ($targetStatus === 'APPROVAL' ? 'SUBMITTED' : 'APPROVAL');

            $rfq = new RfqHeader();
            $rfq->rfq_id = (string) Str::uuid();
            $rfq->rfq_number = $validated['rfq_number'];
            $rfq->rfq_status = $initialStatus;
            $rfq->rfq_approval_status = 'PENDING';
            $rfq->vendor_id = $validated['vendor_id'] ?? null;
            $rfq->rfq_date = now()->toDateString();
            $rfq->rfq_currency_code = 'PHP';
            $rfq->rfq_payment_terms_code = 'NET30';
            $rfq->save();

            if (!empty($validated['items'])) {
                $lIdx = 1;
                $estTotal = 0;
                foreach ($validated['items'] as $it) {
                    $qty = (float)($it['requested_quantity'] ?? $it['quantity'] ?? 1);
                    $targetPrice = (float)($it['target_unit_price'] ?? 0);
                    $quotedPrice = (float)($it['quoted_unit_price'] ?? 0);
                    $quotedQty = isset($it['quoted_quantity']) ? (float)$it['quoted_quantity'] : $qty;
                    $estTotal += ($qty * $targetPrice);
                    RfqLine::create([
                        'rfq_line_id' => (string) Str::uuid(),
                        'rfq_id' => $rfq->rfq_id,
                        'line_number' => $lIdx++,
                        'item_id' => $it['item_id'] ?? ('ITEM-' . $lIdx),
                        'requested_quantity' => $qty,
                        'quoted_quantity' => $quotedQty,
                        'purchase_uom_code' => $it['purchase_uom_code'] ?? 'PCS',
                        'target_unit_price' => $targetPrice,
                        'quoted_unit_price' => $quotedPrice,
                        'quoted_line_total' => $quotedQty * $quotedPrice,
                        'lead_time_days' => (int)($it['lead_time_days'] ?? 0),
                        'is_awarded' => ($targetStatus === 'APPROVED'),
                        'rfq_line_notes' => $it['rfq_line_notes'] ?? null,
                    ]);
                }
                $rfq->rfq_estimated_total_amount = $estTotal;
                $rfq->save();
            }
        }

        $currentStatus = strtoupper($rfq->rfq_status);

        // FSM rule 1: Direct jump from SUBMITTED to APPROVED without APPROVAL validation is forbidden
        if ($currentStatus === 'SUBMITTED' && $targetStatus === 'APPROVED') {
            return response()->json([
                'success' => false,
                'message' => "Direct transition from SUBMITTED to APPROVED is prohibited. Must go through APPROVAL validation.",
            ], 422);
        }

        // FSM rule 2: Check allowed transitions (including idempotent re-confirmations)
        $allowedTransitions = [
            'DRAFT' => ['SUBMITTED', 'DRAFT'],
            'SUBMITTED' => ['APPROVAL', 'DRAFT', 'SUBMITTED'],
            'APPROVAL' => ['APPROVED', 'REFUSED', 'DRAFT', 'REVISED', 'APPROVAL'],
            'APPROVED' => ['AWARDED', 'APPROVED'],
            'REFUSED' => ['DRAFT', 'REFUSED'],
            'AWARDED' => ['AWARDED'],
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($targetStatus, $allowedTransitions[$currentStatus])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition RFQ from {$currentStatus} to {$targetStatus}.",
            ], 422);
        }

        return DB::transaction(function () use ($rfq, $validated, $targetStatus) {
            if (!empty($validated['vendor_id'])) {
                $rfq->vendor_id = $validated['vendor_id'];
            }

            if ($targetStatus === 'REVISED') {
                $rfq->rfq_status = 'DRAFT';
                $rfq->revision_notes = $validated['revision_notes'] ?? null;
            } elseif ($targetStatus === 'REFUSED') {
                $rfq->rfq_status = 'REFUSED';
                $rfq->rejection_reason = $validated['rejection_reason'] ?? 'Refused by Approver';
            } elseif ($targetStatus === 'APPROVED') {
                $rfq->rfq_status = 'APPROVED';
                $rfq->rfq_approval_status = 'APPROVED';
            } else {
                $rfq->rfq_status = $targetStatus;
            }

            // Update vendor quotation inputs if provided
            if (!empty($validated['items'])) {
                $quotedTotal = 0.0;
                foreach ($validated['items'] as $itemQuote) {
                    $lineQuery = RfqLine::where('rfq_id', $rfq->rfq_id);
                    if (!empty($itemQuote['line_id'])) {
                        $lineQuery->where('rfq_line_id', $itemQuote['line_id']);
                    } elseif (!empty($itemQuote['item_id'])) {
                        $lineQuery->where('item_id', $itemQuote['item_id']);
                    }
                    $line = $lineQuery->first();
                    if ($line) {
                        $qQty = isset($itemQuote['quoted_quantity']) ? (float)$itemQuote['quoted_quantity'] : (float)$line->requested_quantity;
                        $qPrice = isset($itemQuote['quoted_unit_price']) ? (float)$itemQuote['quoted_unit_price'] : (float)$line->target_unit_price;
                        $lineTotal = $qQty * $qPrice;

                        $line->quoted_quantity = $qQty;
                        $line->quoted_unit_price = $qPrice;
                        $line->quoted_line_total = $lineTotal;
                        if (isset($itemQuote['lead_time_days'])) {
                            $line->lead_time_days = (int)$itemQuote['lead_time_days'];
                        }
                        if ($targetStatus === 'APPROVED') {
                            $line->is_awarded = true;
                        }
                        $line->save();
                        $quotedTotal += $lineTotal;
                    } else {
                        $qQty = isset($itemQuote['quoted_quantity']) ? (float)$itemQuote['quoted_quantity'] : (float)($itemQuote['requested_quantity'] ?? 1);
                        $qPrice = isset($itemQuote['quoted_unit_price']) ? (float)$itemQuote['quoted_unit_price'] : (float)($itemQuote['target_unit_price'] ?? 0);
                        $lineTotal = $qQty * $qPrice;
                        RfqLine::create([
                            'rfq_line_id' => (string) Str::uuid(),
                            'rfq_id' => $rfq->rfq_id,
                            'line_number' => RfqLine::where('rfq_id', $rfq->rfq_id)->count() + 1,
                            'item_id' => $itemQuote['item_id'] ?? 'ITEM-NEW',
                            'requested_quantity' => $qQty,
                            'quoted_quantity' => $qQty,
                            'purchase_uom_code' => $itemQuote['purchase_uom_code'] ?? 'PCS',
                            'target_unit_price' => (float)($itemQuote['target_unit_price'] ?? 0),
                            'quoted_unit_price' => $qPrice,
                            'quoted_line_total' => $lineTotal,
                            'lead_time_days' => (int)($itemQuote['lead_time_days'] ?? 0),
                            'is_awarded' => ($targetStatus === 'APPROVED'),
                        ]);
                        $quotedTotal += $lineTotal;
                    }
                }
                if ($quotedTotal > 0) {
                    $rfq->rfq_quoted_total_amount = $quotedTotal;
                }
            }

            $rfq->save();

            return response()->json([
                'success' => true,
                'message' => "RFQ {$rfq->rfq_number} status updated to {$rfq->rfq_status}.",
                'data' => $rfq->fresh()->load('lines'),
            ]);
        });
    }

    /**
     * API: Convert Approved RFQ to Purchase Order (Idempotent Atomic Transaction)
     */
    public function apiConvertRfqToPo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_number' => 'required|string',
            'vendor_id' => 'nullable|string|max:100',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'vendor_contact_person' => 'nullable|string|max:255',
            'vendor_phone' => 'nullable|string|max:100',
            'vendor_email' => 'nullable|string|max:255',
            'vendor_address' => 'nullable|string',
            'expected_delivery' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_location' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.sku' => 'nullable|string|max:100',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.specs' => 'nullable|string',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $rfq = RfqHeader::with(['lines', 'vendor'])->where('rfq_number', $validated['rfq_number'])->first();

            // 1. Check if a Purchase Order is ALREADY created for this RFQ (Idempotency guard)
            $existingPo = PurchaseOrder::with('items')
                ->where('rfq_reference', $validated['rfq_number'])
                ->whereNotIn('status', ['Cancelled', 'Archived'])
                ->first();

            if (!$existingPo && $rfq && $rfq->purchase_order_id) {
                $existingPo = PurchaseOrder::with('items')->find($rfq->purchase_order_id);
            }

            if ($existingPo) {
                if ($rfq && $rfq->rfq_status !== 'AWARDED') {
                    $rfq->rfq_status = 'AWARDED';
                    $rfq->purchase_order_id = $existingPo->id;
                    $rfq->save();
                }

                return response()->json([
                    'success' => true,
                    'already_exists' => true,
                    'message' => "Purchase Order {$existingPo->po_number} is already created for RFQ {$validated['rfq_number']}.",
                    'data' => [
                        'po_number' => $existingPo->po_number,
                        'gross_total' => $existingPo->gross_total,
                        'rfq_number' => $validated['rfq_number'],
                        'redirect_url' => '/purchase/purchase-orders?po=' . urlencode($existingPo->po_number),
                        'po' => $existingPo,
                    ],
                ]);
            }

            // 2. If RFQ is not yet persisted in rfq_header (e.g., seeded/local Approved RFQ), auto-create when items are supplied
            if (!$rfq) {
                if (empty($validated['items'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "RFQ {$validated['rfq_number']} not found.",
                    ], 404);
                }

                $rfq = RfqHeader::create([
                    'rfq_id' => (string) Str::uuid(),
                    'rfq_number' => $validated['rfq_number'],
                    'vendor_id' => $validated['vendor_id'] ?? null,
                    'rfq_date' => now()->toDateString(),
                    'rfq_requested_delivery_date' => !empty($validated['expected_delivery']) ? $validated['expected_delivery'] : now()->addDays(3)->toDateString(),
                    'rfq_payment_terms_code' => $validated['payment_terms'] ?? 'Net 30 Days',
                    'rfq_status' => 'APPROVED',
                    'rfq_approval_status' => 'APPROVED',
                ]);

                foreach ($validated['items'] as $idx => $it) {
                    $qty = (float) ($it['quantity'] ?? 1);
                    $price = (float) ($it['unitPrice'] ?? 0);
                    RfqLine::create([
                        'rfq_line_id' => (string) Str::uuid(),
                        'rfq_id' => $rfq->rfq_id,
                        'line_number' => $idx + 1,
                        'item_id' => $it['sku'] ?? ('ITEM-' . ($idx + 1)),
                        'requested_quantity' => $qty,
                        'quoted_quantity' => $qty,
                        'purchase_uom_code' => $it['unit'] ?? 'PCS',
                        'target_unit_price' => $price,
                        'quoted_unit_price' => $price,
                        'quoted_line_total' => $qty * $price,
                        'is_awarded' => true,
                    ]);
                }

                $rfq->load(['lines', 'vendor']);
            }

            if (!in_array(strtoupper((string) $rfq->rfq_status), ['APPROVED', 'AWARDED'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Only APPROVED RFQs can be converted to Purchase Orders.",
                ], 422);
            }

            // 3. Collision-free PO Number generation
            $year = date('Y');
            $seq = max(1, PurchaseOrder::whereYear('created_at', $year)->count() + 1);
            do {
                $poNum = sprintf('PO-%s-%04d', $year, $seq);
                $seq++;
            } while (PurchaseOrder::where('po_number', $poNum)->exists());

            // 4. Calculate gross total from RFQ lines or fallback payload items
            $grossTotal = 0.0;
            $payloadItems = $validated['items'] ?? [];
            if ($rfq->lines->count() > 0) {
                foreach ($rfq->lines as $line) {
                    $qty = (float) ($line->quoted_quantity ?: $line->requested_quantity);
                    $price = (float) ($line->quoted_unit_price ?: $line->target_unit_price);
                    $grossTotal += ($qty * $price);
                }
            } else {
                foreach ($payloadItems as $it) {
                    $qty = (float) ($it['quantity'] ?? 1);
                    $price = (float) ($it['unitPrice'] ?? 0);
                    $grossTotal += ($qty * $price);
                }
            }

            $vendorName = $validated['vendor_name']
                ?? ($rfq->vendor ? ($rfq->vendor->trade_name ?? $rfq->vendor->legal_name) : 'Approved Vendor');

            $po = PurchaseOrder::create([
                'po_number' => $poNum,
                'po_type' => 'vendor',
                'rfq_reference' => $rfq->rfq_number,
                'vendor_id' => $validated['vendor_id'] ?? $rfq->vendor_id,
                'vendor_name' => $vendorName,
                'vendor_trade_name' => $validated['vendor_trade_name'] ?? $vendorName,
                'vendor_contact_person' => $validated['vendor_contact_person'] ?? null,
                'vendor_phone' => $validated['vendor_phone'] ?? null,
                'vendor_email' => $validated['vendor_email'] ?? null,
                'vendor_address' => $validated['vendor_address'] ?? null,
                'order_date' => now()->toDateString(),
                'expected_delivery' => !empty($validated['expected_delivery'])
                    ? $validated['expected_delivery']
                    : ($rfq->rfq_requested_delivery_date ?: now()->addDays(3)->toDateString()),
                'delivery_location' => $validated['delivery_location'] ?? 'Central Commissary - Receiving Dock 1',
                'payment_terms' => $validated['payment_terms'] ?? ($rfq->rfq_payment_terms_code ?: 'Net 30 Days'),
                'payment_status' => 'Unpaid / Credit',
                'payment_method' => 'Trade Credit (Net 30/15)',
                'amount_paid' => 0.00,
                'balance_due' => $grossTotal,
                'approved_by' => $validated['approved_by'] ?? 'Procurement Officer',
                'status' => 'Approved / Issued',
                'subtotal' => $grossTotal,
                'tax_amount' => 0.00,
                'gross_total' => $grossTotal,
                'created_by_user' => auth()->user()->name ?? 'Procurement Specialist',
            ]);

            if ($rfq->lines->count() > 0) {
                foreach ($rfq->lines as $idx => $line) {
                    $qty = (float) ($line->quoted_quantity ?: $line->requested_quantity);
                    $price = (float) ($line->quoted_unit_price ?: $line->target_unit_price);
                    $fallbackItem = $payloadItems[$idx] ?? [];
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'sku' => $line->item_id ?: ($fallbackItem['sku'] ?? 'ITEM'),
                        'item_name' => $line->item ? $line->item->name : ($fallbackItem['name'] ?? $line->item_id),
                        'specs' => $fallbackItem['specs'] ?? $line->rfq_line_notes,
                        'category' => $fallbackItem['category'] ?? 'Raw Ingredients',
                        'uom' => $line->purchase_uom_code ?: ($fallbackItem['unit'] ?? 'PCS'),
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_amount' => $qty * $price,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING',
                    ]);
                }
            } else {
                foreach ($payloadItems as $it) {
                    $qty = (float) ($it['quantity'] ?? 1);
                    $price = (float) ($it['unitPrice'] ?? 0);
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'sku' => $it['sku'] ?? 'ITEM',
                        'item_name' => $it['name'] ?? ($it['sku'] ?? 'Custom Item'),
                        'specs' => $it['specs'] ?? null,
                        'category' => $it['category'] ?? 'Raw Ingredients',
                        'uom' => $it['unit'] ?? 'PCS',
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_amount' => $qty * $price,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING',
                    ]);
                }
            }

            // Update RFQ status to AWARDED and link PO
            $rfq->rfq_status = 'AWARDED';
            $rfq->purchase_order_id = $po->id;
            $rfq->save();

            return response()->json([
                'success' => true,
                'already_exists' => false,
                'message' => "Purchase Order {$po->po_number} successfully generated from RFQ {$rfq->rfq_number}.",
                'data' => [
                    'po_number' => $po->po_number,
                    'gross_total' => $po->gross_total,
                    'rfq_number' => $rfq->rfq_number,
                    'redirect_url' => '/purchase/purchase-orders?po=' . urlencode($po->po_number),
                    'po' => $po->load('items'),
                ],
            ]);
        });
    }

    public function vendorMasterlist(): View
    {
        $vendors = ProcurementVendor::where('is_active', true)->orderBy('legal_name')->get();

        return view('purchase.vendor-masterlist', [
            'initialVendors' => $vendors,
        ]);
    }

    public function vendorBills(): View
    {
        return view('purchase.vendor-bills');
    }

    /**
     * API: Create alternative Draft RFQs for selected vendors
     */
    public function apiCreateRfqAlternatives(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_rfq_number' => 'required|string',
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'required',
        ]);

        try {
            $created = $this->rfqAlternativeService->createAlternatives(
                $validated['base_rfq_number'],
                $validated['vendor_ids'],
                auth()->user()->name ?? 'Procurement Officer'
            );

            return response()->json([
                'success' => true,
                'message' => count($created) . " alternative RFQ(s) successfully generated in Draft status.",
                'data' => $created,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API: Get side-by-side vendor quotation comparison matrix for an RFQ bidding group
     */
    public function apiGetRfqGroupBids(string $groupId): JsonResponse
    {
        try {
            $matrix = $this->rfqAlternativeService->getComparisonMatrix($groupId);
            return response()->json([
                'success' => true,
                'data' => $matrix,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * API: Execute Full or Split Award and generate Purchase Orders
     */
    public function apiAwardRfqBids(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_group_id' => 'required|string',
            'award_type' => 'required|string|in:FULL,SPLIT',
            'awards' => 'required|array|min:1',
            'awards.*.item_id' => 'required|string',
            'awards.*.rfq_id' => 'required|string',
            'po_status' => 'nullable|string',
        ]);

        try {
            $result = $this->rfqAlternativeService->awardBids(
                $validated['rfq_group_id'],
                $validated['award_type'],
                $validated['awards'],
                auth()->user()->name ?? 'Procurement Officer',
                $validated['po_status'] ?? 'Approved / Issued'
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully awarded tender package. Generated " . $result['total_awarded_pos'] . " Purchase Order(s).",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API: Batch Delete RFQs and their associated lines
     */
    public function apiBatchDeleteRfqs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_numbers' => 'required|array|min:1',
            'rfq_numbers.*' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $headers = RfqHeader::whereIn('rfq_number', $validated['rfq_numbers'])->get();
            $rfqIds = $headers->pluck('rfq_id')->filter()->all();

            if (!empty($rfqIds)) {
                RfqLine::whereIn('rfq_id', $rfqIds)->delete();
            }

            $deletedCount = RfqHeader::whereIn('rfq_number', $validated['rfq_numbers'])->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} Request(s) for Quotation.",
                'deleted_count' => $deletedCount,
            ]);
        });
    }

    /**
     * API: Batch Archive RFQs
     */
    public function apiBatchArchiveRfqs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_numbers' => 'required|array|min:1',
            'rfq_numbers.*' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $archivedCount = RfqHeader::whereIn('rfq_number', $validated['rfq_numbers'])
                ->update(['rfq_status' => 'ARCHIVED']);

            return response()->json([
                'success' => true,
                'message' => "Successfully archived {$archivedCount} Request(s) for Quotation.",
                'archived_count' => $archivedCount,
            ]);
        });
    }

    /**
     * API: Save or Update Purchase Order Draft
     */
    public function apiSavePoDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_number' => 'nullable|string|max:50',
            'po_type' => 'nullable|string|max:50',
            'rfq_reference' => 'nullable|string|max:100',
            'vendor_id' => 'nullable',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'vendor_contact_person' => 'nullable|string|max:255',
            'vendor_phone' => 'nullable|string|max:100',
            'vendor_email' => 'nullable|string|max:255',
            'vendor_address' => 'nullable|string',
            'order_date' => 'nullable|string|max:50',
            'expected_delivery' => 'nullable|string|max:50',
            'delivery_location' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string',
            'payment_status' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'amount_paid' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.sku' => 'nullable|string|max:100',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.specs' => 'nullable|string',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $year = date('Y');
            $poNum = $validated['po_number'] ?? null;
            if (empty($poNum)) {
                $count = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
                $poNum = sprintf('PO-%s-%04d', $year, $count);
            }

            $items = $validated['items'] ?? [];
            $grossTotal = 0.0;
            foreach ($items as $it) {
                $grossTotal += ((float) ($it['quantity'] ?? 0) * (float) ($it['unitPrice'] ?? 0));
            }

            $amountPaid = (float) ($validated['amount_paid'] ?? 0);
            $balanceDue = max(0, $grossTotal - $amountPaid);

            $po = PurchaseOrder::updateOrCreate(
                ['po_number' => $poNum],
                [
                    'po_type' => $validated['po_type'] ?? 'vendor',
                    'rfq_reference' => $validated['rfq_reference'] ?? null,
                    'vendor_id' => $validated['vendor_id'] ?? null,
                    'vendor_name' => $validated['vendor_name'] ?? 'Draft Vendor',
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
                    'status' => 'Draft PO',
                    'subtotal' => $grossTotal,
                    'tax_amount' => 0.00,
                    'gross_total' => $grossTotal,
                    'created_by_user' => auth()->user()->name ?? 'Procurement Specialist',
                ]
            );

            PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();

            foreach ($items as $it) {
                $qty = (float) ($it['quantity'] ?? 0);
                $price = (float) ($it['unitPrice'] ?? 0);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sku' => $it['sku'] ?? null,
                    'item_name' => $it['name'] ?? ($it['sku'] ?? 'Custom Item'),
                    'specs' => $it['specs'] ?? null,
                    'category' => $it['category'] ?? 'Raw Ingredients',
                    'uom' => $it['unit'] ?? 'PCS',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_amount' => $qty * $price,
                    'received_quantity' => 0.00,
                    'status' => 'PENDING',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Draft Purchase Order {$po->po_number} saved.",
                'data' => $po->load('items'),
            ]);
        });
    }

    /**
     * API: Transition Purchase Order Lifecycle Status (4-Stage FSM)
     */
    public function apiTransitionPoStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_number' => 'required|string|max:50',
            'target_status' => 'required|string|max:50',
            'rfq_reference' => 'nullable|string|max:100',
            'vendor_id' => 'nullable',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_trade_name' => 'nullable|string|max:255',
            'vendor_contact_person' => 'nullable|string|max:255',
            'vendor_phone' => 'nullable|string|max:100',
            'vendor_email' => 'nullable|string|max:255',
            'vendor_address' => 'nullable|string',
            'order_date' => 'nullable|string|max:50',
            'expected_delivery' => 'nullable|string|max:50',
            'delivery_location' => 'nullable|string|max:255',
            'special_notes' => 'nullable|string',
            'approved_by' => 'nullable|string|max:255',
            'approval_notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($validated) {
            $po = PurchaseOrder::where('po_number', $validated['po_number'])->lockForUpdate()->first();

            if (!$po) {
                $po = PurchaseOrder::create([
                    'po_number' => $validated['po_number'],
                    'rfq_reference' => $validated['rfq_reference'] ?? null,
                    'vendor_id' => $validated['vendor_id'] ?? null,
                    'vendor_name' => $validated['vendor_name'] ?? 'Approved Vendor',
                    'vendor_trade_name' => $validated['vendor_trade_name'] ?? ($validated['vendor_name'] ?? 'Approved Vendor'),
                    'vendor_contact_person' => $validated['vendor_contact_person'] ?? null,
                    'vendor_phone' => $validated['vendor_phone'] ?? null,
                    'vendor_email' => $validated['vendor_email'] ?? null,
                    'vendor_address' => $validated['vendor_address'] ?? null,
                    'order_date' => !empty($validated['order_date']) ? $validated['order_date'] : now()->toDateString(),
                    'expected_delivery' => !empty($validated['expected_delivery']) ? $validated['expected_delivery'] : now()->addDays(3)->toDateString(),
                    'delivery_location' => $validated['delivery_location'] ?? 'Central Commissary - Receiving Dock 1',
                    'special_notes' => $validated['special_notes'] ?? null,
                    'status' => 'Draft PO',
                    'subtotal' => 0,
                    'gross_total' => 0,
                    'balance_due' => 0,
                ]);
            }

            $normalizeStage = function (string $status): string {
                $u = strtoupper(trim($status));
                if (in_array($u, ['DRAFT', 'DRAFT PO'])) return 'DRAFT';
                if (in_array($u, ['APPROVAL', 'PENDING APPROVAL', 'PENDING_APPROVAL'])) return 'APPROVAL';
                if (in_array($u, ['ISSUED', 'APPROVED / ISSUED', 'SENT', 'PARTIALLY RECEIVED', 'PARTIALLY_RECEIVED'])) return 'ISSUED';
                if (in_array($u, ['FULFILLED', 'FULLY RECEIVED', 'FULLY_RECEIVED', 'CLOSED', 'COMPLETED'])) return 'FULFILLED';
                if (in_array($u, ['REFUSED', 'CANCELLED', 'SHORT-CLOSED'])) return 'REFUSED';
                return 'DRAFT';
            };

            $currentStage = $normalizeStage($po->status);
            $targetStage = $normalizeStage($validated['target_status']);

            $allowedTransitions = [
                'DRAFT' => ['DRAFT', 'APPROVAL', 'ISSUED', 'REFUSED'],
                'APPROVAL' => ['DRAFT', 'APPROVAL', 'ISSUED', 'REFUSED'],
                'ISSUED' => ['DRAFT', 'APPROVAL', 'ISSUED', 'FULFILLED', 'REFUSED'],
                'FULFILLED' => ['ISSUED', 'FULFILLED'],
                'REFUSED' => ['DRAFT', 'REFUSED'],
            ];

            if (!in_array($targetStage, $allowedTransitions[$currentStage] ?? [], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid Purchase Order lifecycle transition from {$po->status} to {$validated['target_status']}.",
                ], 422);
            }

            $canonicalStatusMap = [
                'DRAFT' => 'Draft PO',
                'APPROVAL' => 'Pending Approval',
                'ISSUED' => 'Approved / Issued',
                'FULFILLED' => 'Fully Received',
                'REFUSED' => 'Cancelled',
            ];

            if (in_array(strtoupper(trim($validated['target_status'])), ['PARTIALLY RECEIVED', 'PARTIALLY_RECEIVED'], true)) {
                $po->status = 'Partially Received';
            } else {
                $po->status = $canonicalStatusMap[$targetStage] ?? $validated['target_status'];
            }
            if (!empty($validated['vendor_name'])) {
                $po->vendor_name = $validated['vendor_name'];
            }
            if (!empty($validated['vendor_trade_name'])) {
                $po->vendor_trade_name = $validated['vendor_trade_name'];
            }
            if (!empty($validated['vendor_id'])) {
                $po->vendor_id = $validated['vendor_id'];
            }
            if (!empty($validated['rfq_reference'])) {
                $po->rfq_reference = $validated['rfq_reference'];
            }
            if (!empty($validated['approved_by'])) {
                $po->approved_by = $validated['approved_by'];
            }
            if (!empty($validated['approval_notes'])) {
                $po->approval_notes = $validated['approval_notes'];
            }

            if (!empty($validated['items']) && is_array($validated['items'])) {
                $grossTotal = 0.0;
                PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();
                foreach ($validated['items'] as $it) {
                    $qty = (float) ($it['quantity'] ?? 0);
                    $price = (float) ($it['unitPrice'] ?? 0);
                    $recQty = (float) ($it['receivedQuantity'] ?? 0);
                    if ($targetStage === 'FULFILLED' && $recQty <= 0) {
                        $recQty = $qty;
                    }
                    $grossTotal += ($qty * $price);
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'sku' => $it['sku'] ?? null,
                        'item_name' => $it['name'] ?? ($it['sku'] ?? 'Custom Item'),
                        'specs' => $it['specs'] ?? null,
                        'category' => $it['category'] ?? 'Raw Ingredients',
                        'uom' => $it['unit'] ?? 'PCS',
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_amount' => $qty * $price,
                        'received_quantity' => $recQty,
                        'status' => ($recQty >= $qty && $qty > 0) ? 'RECEIVED' : ($recQty > 0 ? 'PARTIAL' : 'PENDING'),
                    ]);
                }
                $po->subtotal = $grossTotal;
                $po->gross_total = $grossTotal;
                $po->balance_due = max(0, $grossTotal - (float) $po->amount_paid);
            }

            $po->save();

            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$po->po_number} transitioned to {$po->status}.",
                'data' => $po->fresh('items'),
            ]);
        });
    }

    /**
     * API: Delete / Batch Delete Purchase Orders
     */
    public function apiBatchDeletePos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_numbers' => 'required|array|min:1',
            'po_numbers.*' => 'required|string',
            'force' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $query = PurchaseOrder::whereIn('po_number', $validated['po_numbers']);
            if (empty($validated['force'])) {
                $query->whereIn('status', ['Draft PO', 'DRAFT', 'Draft', 'Cancelled', 'Refused']);
            }
            $headers = $query->get();

            $poIds = $headers->pluck('id')->all();
            $poNums = $headers->pluck('po_number')->all();

            if (!empty($poIds)) {
                // Unlink any RFQ referencing these POs so the RFQ can be re-converted if needed
                RfqHeader::whereIn('purchase_order_id', $poIds)->update([
                    'purchase_order_id' => null,
                    'rfq_status' => 'APPROVED',
                ]);
                RfqHeader::whereIn('rfq_number', $headers->pluck('rfq_reference')->filter()->all())->update([
                    'purchase_order_id' => null,
                    'rfq_status' => 'APPROVED',
                ]);

                // Clean up any GoodsReceipt lines/headers referencing these POs
                $grnIds = \App\Models\Inventory\GoodsReceipt::whereIn('purchase_order_id', $poIds)->pluck('id')->all();
                if (!empty($grnIds)) {
                    \App\Models\Inventory\GoodsReceiptItem::whereIn('goods_receipt_id', $grnIds)->delete();
                    \App\Models\Inventory\GoodsReceipt::whereIn('id', $grnIds)->delete();
                }

                PurchaseOrderItem::whereIn('purchase_order_id', $poIds)->delete();
            }

            $deletedCount = !empty($poIds)
                ? PurchaseOrder::whereIn('id', $poIds)->delete()
                : 0;

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} Purchase Order(s).",
                'deleted_count' => $deletedCount,
                'deleted_po_numbers' => $poNums,
            ]);
        });
    }

    /**
     * API: Batch Archive Purchase Orders
     */
    public function apiBatchArchivePos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'po_numbers' => 'required|array|min:1',
            'po_numbers.*' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $archivedCount = PurchaseOrder::whereIn('po_number', $validated['po_numbers'])
                ->update(['status' => 'Archived']);

            return response()->json([
                'success' => true,
                'message' => "Successfully archived {$archivedCount} Purchase Order(s).",
                'archived_count' => $archivedCount,
            ]);
        });
    }
}


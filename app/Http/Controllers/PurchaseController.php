<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // Audit trace and simulated email dispatch
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

    public function purchaseOrders(): View
    {
        return view('purchase.purchase-orders');
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

<?php

namespace App\Http\Controllers;

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

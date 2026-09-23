<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SalesController extends Controller
{
    public function dashboard(): View
    {
        return view('sales.dashboard');
    }

    public function dailySales(): View
    {
        return view('sales.daily-sales');
    }

    public function paymentReport(): View
    {
        return view('sales.payment-report');
    }

    public function reconciliations(): View
    {
        return view('sales.reconciliations');
    }

    public function discountConfig(): View
    {
        return view('sales.discount-config');
    }

    public function voucherConfig(): View
    {
        return view('sales.voucher-config');
    }

    public function bundlePromotions(): View
    {
        return view('sales.bundle-promotions');
    }

    public function customerMasterlist(): View
    {
        return view('sales.customer-masterlist');
    }
}

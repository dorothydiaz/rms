<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InventoryController extends Controller
{
    public function dashboard(): View
    {
        return view('inventory.dashboard');
    }

    public function stocksOverview(): View
    {
        return view('inventory.stocks-overview');
    }

    public function begBalance(): View
    {
        return view('inventory.beg-balance');
    }

    public function stockIn(): View
    {
        return view('inventory.stock-in');
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

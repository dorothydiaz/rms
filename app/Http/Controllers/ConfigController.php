<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ConfigController extends Controller
{
    public function businessSettings(): View
    {
        return view('config.business-settings');
    }

    public function accountSettings(): View
    {
        return view('config.account-settings');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CreditsController extends Controller
{
    public function tickets(): View
    {
        return view('credits.tickets');
    }

    public function developers(): View
    {
        return view('credits.developers');
    }
}

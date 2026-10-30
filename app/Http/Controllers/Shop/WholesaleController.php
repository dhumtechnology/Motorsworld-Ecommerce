<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WholesaleController extends Controller
{
    public function index(): View
    {
        return view('shop.wholesale.index', [
            'contact' => config('shop.contact'),
        ]);
    }
}

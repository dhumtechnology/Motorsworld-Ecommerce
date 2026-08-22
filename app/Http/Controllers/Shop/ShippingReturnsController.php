<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ShippingReturnsController extends Controller
{
    public function index(): View
    {
        return view('shop.shipping-returns.index');
    }
}

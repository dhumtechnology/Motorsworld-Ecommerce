<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shipping\ShippingRate;
use Illuminate\Contracts\View\View;

class ShippingReturnsController extends Controller
{
    public function index(): View
    {
        $rates = ShippingRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('shop.shipping-returns.index', [
            'rates' => $rates,
        ]);
    }
}

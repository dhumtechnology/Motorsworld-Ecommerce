<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PaymentPromotionsController extends Controller
{
    public function index(): View
    {
        return view('shop.payment-promotions.index', [
            'contact' => config('shop.contact'),
        ]);
    }
}

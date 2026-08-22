<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        return view('shop.faq.index', [
            'contact' => config('shop.contact'),
        ]);
    }
}

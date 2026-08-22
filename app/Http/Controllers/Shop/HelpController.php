<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HelpController extends Controller
{
    public function index(): View
    {
        return view('shop.help.index', [
            'contact' => config('shop.contact'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\GetHomePageDataAction;
use App\Http\Controllers\Controller;
use App\Services\Cart\CartResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(
        Request $request,
        GetHomePageDataAction $getHomePageData,
        CartResolver $cartResolver,
    ): View {
        $data = $getHomePageData->execute();

        $cart = $cartResolver->resolve($request->user(), $request->session()->getId());
        $data['cartQuantities'] = $cart->items()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();

        return view('shop.home.index', $data);
    }
}

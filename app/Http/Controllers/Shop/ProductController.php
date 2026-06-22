<?php

namespace App\Http\Controllers\Shop;

use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        if ($product->status !== ProductStatus::Active) {
            throw new NotFoundHttpException;
        }

        $product->load(['category', 'vehicleModel.brand', 'inventory']);

        return view('shop.product.show', [
            'product' => $product,
        ]);
    }
}

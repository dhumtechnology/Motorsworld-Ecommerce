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

        $product->load([
            'category',
            'vehicleModel.brand',
            'inventory',
            'images',
            'activeOffer',
            'reviews.user.customerProfile',
        ]);

        $this->applyCatalogPresentationAttributes($product);

        $reviews = $product->reviews;

        return view('shop.product.show', [
            'product' => $product,
            'reviews' => $reviews,
            'reviewSummary' => [
                'count' => $reviews->count(),
                'average_stars' => $reviews->isEmpty()
                    ? null
                    : round((float) $reviews->avg('stars'), 1),
            ],
        ]);
    }

    private function applyCatalogPresentationAttributes(Product $product): void
    {
        $pricing = $product->currentPricing();

        $product->setAttribute('is_on_sale', $pricing->hasOffer());
        $product->setAttribute('sale_price', $pricing->hasOffer() ? $pricing->unitPrice : null);
        $product->setAttribute('list_price', $pricing->listUnitPrice);
        $product->setAttribute('effective_price', $pricing->unitPrice);
        $product->setAttribute('image', $product->catalogImageUrl());

        if ($offer = $product->activeOfferAt()) {
            $product->setAttribute('offer_ends_at', $offer->ends_at);
        }
    }
}

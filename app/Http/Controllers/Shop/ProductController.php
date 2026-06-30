<?php

namespace App\Http\Controllers\Shop;

use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use App\Services\Cart\CartResolver;
use App\Services\Products\RelatedProductsResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    private const RELATED_LIMIT = 8;

    public function __construct(
        private readonly RelatedProductsResolver $relatedProducts,
        private readonly CartResolver $cartResolver,
    ) {}

    public function show(Request $request, Product $product): View
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

        $relatedProducts = $this->relatedProducts
            ->resolve($product, self::RELATED_LIMIT)
            ->map(fn (Product $related) => $this->applyCatalogPresentationAttributes($related));

        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());
        $cartLineQuantity = (int) $cart->items()
            ->where('product_id', $product->id)
            ->value('quantity');

        return view('shop.product.show', [
            'product' => $product,
            'reviews' => $reviews,
            'reviewSummary' => [
                'count' => $reviews->count(),
                'average_stars' => $reviews->isEmpty()
                    ? null
                    : round((float) $reviews->avg('stars'), 1),
            ],
            'relatedProducts' => $relatedProducts,
            'cartLineQuantity' => $cartLineQuantity,
        ]);
    }

    private function applyCatalogPresentationAttributes(Product $product): Product
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

        return $product;
    }
}

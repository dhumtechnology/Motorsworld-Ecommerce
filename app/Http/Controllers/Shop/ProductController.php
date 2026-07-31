<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\GetPopularNonMotoProductsAction;
use App\Enums\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use App\Services\Cart\CartResolver;
use App\Services\Products\ProductOfferPresenter;
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
        private readonly ProductOfferPresenter $offerPresenter,
        private readonly GetPopularNonMotoProductsAction $popularProducts,
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

        $cartQuantities = $cart->items()
            ->get(['product_id', 'quantity'])
            ->pluck('quantity', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();

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
            'popularProducts' => $this->popularProducts->execute(10),
            'cartQuantities' => $cartQuantities,
        ]);
    }

    private function applyCatalogPresentationAttributes(Product $product): Product
    {
        return $this->offerPresenter->apply($product);
    }
}

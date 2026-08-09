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
            'inventories',
            'images',
            'activeOffer',
            'variants.colors',
            'variants.inventory',
            'variants.images',
        ]);

        $this->applyCatalogPresentationAttributes($product);

        $relatedProducts = $this->relatedProducts
            ->resolve($product, self::RELATED_LIMIT)
            ->map(fn (Product $related) => $this->applyCatalogPresentationAttributes($related));

        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());

        $cartQuantitiesByVariant = $cart->items()
            ->where('product_id', $product->id)
            ->get(['product_variant_id', 'quantity'])
            ->pluck('quantity', 'product_variant_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();

        $cartQuantitiesByProduct = $cart->items()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();

        $defaultProductImages = $product->images
            ->filter(fn ($image) => $image->product_variant_id === null && filled($image->path))
            ->values()
            ->map(fn ($image) => [
                'path' => $image->path,
                'is_primary' => (bool) $image->is_primary,
            ])
            ->all();

        $variantsPayload = $product->variants
            ->filter(fn ($variant) => (bool) $variant->is_active)
            ->values()
            ->map(function ($variant) use ($cartQuantitiesByVariant, $defaultProductImages) {
                $images = $variant->images
                    ->filter(fn ($image) => filled($image->path))
                    ->values()
                    ->map(fn ($image) => [
                        'path' => $image->path,
                        'is_primary' => (bool) $image->is_primary,
                    ])
                    ->all();

                if ($images === [] && $variant->colors->isEmpty() && $defaultProductImages !== []) {
                    $images = $defaultProductImages;
                }

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'label' => $variant->colorLabel(),
                    'has_colors' => $variant->colors->isNotEmpty(),
                    'available_stock' => (int) ($variant->inventory?->available_stock ?? 0),
                    'cart_quantity' => (int) ($cartQuantitiesByVariant[$variant->id] ?? 0),
                    'colors' => $variant->colors->map(fn ($color) => [
                        'name' => $color->name,
                        'hex' => $color->hex,
                    ])->values()->all(),
                    'images' => $images,
                ];
            })
            ->all();

        // Si no hay imágenes a nivel producto, usar las de la variante estándar.
        if ($defaultProductImages === []) {
            $stdPayload = collect($variantsPayload)->first(fn (array $v) => ! $v['has_colors']);
            if ($stdPayload && $stdPayload['images'] !== []) {
                $defaultProductImages = $stdPayload['images'];
            }
        }

        $coloredVariants = collect($variantsPayload)->filter(fn (array $variant) => $variant['has_colors'])->values();
        $hasColorChoices = $coloredVariants->isNotEmpty();

        $pickerVariants = $hasColorChoices
            ? $coloredVariants->all()
            : collect($variantsPayload)->all();

        $defaultVariant = collect($pickerVariants)->first(
            fn (array $variant) => $variant['available_stock'] > 0
        ) ?? collect($pickerVariants)->first();

        $defaultVariantId = $defaultVariant['id'] ?? null;

        $cartLineQuantity = $defaultVariantId
            ? (int) ($cartQuantitiesByVariant[$defaultVariantId] ?? 0)
            : 0;

        return view('shop.product.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'cartLineQuantity' => $cartLineQuantity,
            'popularProducts' => $this->popularProducts->execute(10),
            'cartQuantities' => $cartQuantitiesByProduct,
            'variantsPayload' => $pickerVariants,
            'defaultVariantId' => $defaultVariantId,
            'hasColorChoices' => $hasColorChoices,
            'defaultProductImages' => $defaultProductImages,
        ]);
    }

    private function applyCatalogPresentationAttributes(Product $product): Product
    {
        return $this->offerPresenter->apply($product);
    }
}

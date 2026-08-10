<?php

namespace App\Actions\Cart;

use App\Models\Cart\Cart;
use App\Services\Orders\ProductPricingService;
use Illuminate\Support\Collection;

class BuildCartLinesAction
{
    public function __construct(
        private readonly ProductPricingService $pricing,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(Cart $cart): Collection
    {
        $cart->loadMissing([
            'items.product.category',
            'items.product.primaryImage',
            'items.product.activeOffer',
            'items.variant.inventory',
            'items.variant.images',
            'items.variant.colors',
        ]);

        return $cart->items
            ->filter(fn ($item) => $item->product !== null && $item->variant !== null)
            ->map(function ($item) {
                $product = $item->product;
                $variant = $item->variant;
                $price = $this->pricing->resolve($product);

                return [
                    'item' => $item,
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $price->unitPrice,
                    'list_unit_price' => (float) $price->listUnitPrice,
                    'line_total' => (float) $price->unitPrice * (int) $item->quantity,
                    'is_on_sale' => $price->hasOffer(),
                    'image' => $variant->catalogImageUrl() ?? $product->catalogImageUrl(),
                    'max_quantity' => max(0, (int) ($variant->inventory?->available_stock ?? 0)),
                    'color_label' => $variant->colorLabel(),
                    'currency' => strtoupper((string) ($price->currency ?: 'PEN')),
                    'currency_symbol' => \App\Support\Currency::symbol($price->currency ?: 'PEN'),
                ];
            })
            ->values();
    }
}

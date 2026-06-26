<?php

namespace App\Services\Orders;

use App\Models\Products\Product;
use App\Models\Products\ProductOffer;
use Illuminate\Support\Carbon;

class ProductPricingService
{
    /**
     * Resolves the unit price to charge at order time, applying an active offer when present.
     */
    public function resolve(Product $product, ?Carbon $at = null): ProductPricing
    {
        $at ??= now();

        $offer = $this->activeOfferFor($product, $at);

        if ($offer === null) {
            return new ProductPricing(
                unitPrice: (string) $product->price_amount,
                listUnitPrice: (string) $product->price_amount,
                currency: $product->currency,
            );
        }

        return new ProductPricing(
            unitPrice: (string) $offer->offer_price_amount,
            listUnitPrice: (string) $product->price_amount,
            currency: $offer->currency,
            productOfferId: $offer->id,
        );
    }

    private function activeOfferFor(Product $product, Carbon $at): ?ProductOffer
    {
        if ($product->relationLoaded('offers')) {
            return $product->offers
                ->filter(fn (ProductOffer $offer) => $offer->isActiveAt($at))
                ->sortBy('offer_price_amount')
                ->first();
        }

        return $product->offers()
            ->activeAt($at)
            ->orderBy('offer_price_amount')
            ->first();
    }
}

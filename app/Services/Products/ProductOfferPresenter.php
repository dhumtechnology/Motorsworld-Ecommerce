<?php

namespace App\Services\Products;

use App\Models\Products\Product;
use App\Models\Products\ProductOffer;

/**
 * Adjunta precios de catálogo y el payload completo de la oferta activa
 * para las vistas shop (Blade / frontend).
 */
class ProductOfferPresenter
{
    public function apply(Product $product): Product
    {
        $pricing = $product->currentPricing();
        $offer = $product->activeOfferAt();

        $product->setAttribute('is_on_sale', $pricing->hasOffer());
        $product->setAttribute('sale_price', $pricing->hasOffer() ? $pricing->unitPrice : null);
        $product->setAttribute('list_price', $pricing->listUnitPrice);
        $product->setAttribute('effective_price', $pricing->unitPrice);
        $product->setAttribute('image', $product->catalogImageUrl());

        if ($offer === null) {
            $product->setAttribute('offer', null);
            $product->setAttribute('discount_percent', null);
            $product->setAttribute('offer_reason', null);
            $product->setAttribute('offer_starts_at', null);
            $product->setAttribute('offer_ends_at', null);

            return $product;
        }

        $listPrice = (float) $product->price_amount;
        $discountPercent = $offer->resolvedDiscountPercent($listPrice);

        $product->setAttribute('offer', $this->offerPayload($offer, $discountPercent));
        $product->setAttribute('discount_percent', $discountPercent);
        $product->setAttribute('offer_reason', $offer->reason);
        $product->setAttribute('offer_starts_at', $offer->starts_at);
        $product->setAttribute('offer_ends_at', $offer->ends_at);

        return $product;
    }

    /**
     * @return array{
     *     id: int,
     *     product_id: int,
     *     offer_price_amount: string,
     *     discount_percent: float,
     *     reason: ?string,
     *     currency: string,
     *     starts_at: ?string,
     *     ends_at: ?string,
     *     starts_at_formatted: ?string,
     *     ends_at_formatted: ?string,
     *     is_active: bool,
     *     lifecycle_status: string
     * }
     */
    private function offerPayload(ProductOffer $offer, float $discountPercent): array
    {
        return [
            'id' => $offer->id,
            'product_id' => $offer->product_id,
            'offer_price_amount' => (string) $offer->offer_price_amount,
            'discount_percent' => $discountPercent,
            'reason' => $offer->reason,
            'currency' => $offer->currency ?? 'PEN',
            'starts_at' => $offer->starts_at?->toIso8601String(),
            'ends_at' => $offer->ends_at?->toIso8601String(),
            'starts_at_formatted' => $offer->starts_at?->format('d/m/Y H:i'),
            'ends_at_formatted' => $offer->ends_at?->format('d/m/Y H:i'),
            'is_active' => $offer->isActiveAt(),
            'lifecycle_status' => $offer->lifecycleStatus(),
        ];
    }
}

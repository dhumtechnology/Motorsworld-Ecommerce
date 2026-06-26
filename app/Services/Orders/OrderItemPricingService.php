<?php

namespace App\Services\Orders;

use App\Models\Products\Product;
use Illuminate\Support\Carbon;

class OrderItemPricingService
{
    public function __construct(
        private readonly ProductPricingService $productPricing,
    ) {}

    /**
     * @return array{
     *     product_id: int,
     *     product_offer_id: int|null,
     *     quantity: int,
     *     unit_price: string,
     *     list_unit_price: string,
     *     currency: string
     * }
     */
    public function attributesFor(
        Product $product,
        int $quantity,
        ?Carbon $at = null,
    ): array {
        $pricing = $this->productPricing->resolve($product, $at);

        return [
            'product_id' => $product->id,
            'product_offer_id' => $pricing->productOfferId,
            'quantity' => $quantity,
            'unit_price' => $pricing->unitPrice,
            'list_unit_price' => $pricing->listUnitPrice,
            'currency' => $pricing->currency,
        ];
    }
}

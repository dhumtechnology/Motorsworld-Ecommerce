<?php

namespace App\Actions\Cart;

use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Products\ProductVariant;

class AddProductToCartAction
{
    public function __construct(
        private readonly UpdateCartItemQuantityAction $updateQuantity,
    ) {}

    public function execute(Cart $cart, ProductVariant $variant, int $quantity = 1): CartItem
    {
        $currentQuantity = (int) $cart->items()
            ->where('product_variant_id', $variant->id)
            ->value('quantity');

        return $this->updateQuantity->execute(
            $cart,
            $variant,
            $currentQuantity + $quantity,
        );
    }
}

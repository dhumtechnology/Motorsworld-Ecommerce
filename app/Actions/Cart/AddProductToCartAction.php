<?php

namespace App\Actions\Cart;

use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Products\Product;

class AddProductToCartAction
{
    public function __construct(
        private readonly UpdateCartItemQuantityAction $updateQuantity,
    ) {}

    public function execute(Cart $cart, Product $product, int $quantity = 1): CartItem
    {
        $currentQuantity = (int) $cart->items()
            ->where('product_id', $product->id)
            ->value('quantity');

        return $this->updateQuantity->execute(
            $cart,
            $product,
            $currentQuantity + $quantity,
        );
    }
}

<?php

namespace App\Actions\Cart;

use App\Enums\Products\ProductStatus;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Products\Product;
use Illuminate\Validation\ValidationException;

class UpdateCartItemQuantityAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Cart $cart, Product $product, int $quantity): ?CartItem
    {
        $this->assertProductCanBeAdded($product);

        if ($quantity <= 0) {
            $cart->items()->where('product_id', $product->id)->delete();

            return null;
        }

        $maxQuantity = $this->maxAllowedQuantity($product);

        if ($maxQuantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Este producto no tiene stock disponible.',
            ]);
        }

        if ($quantity > $maxQuantity) {
            throw ValidationException::withMessages([
                'quantity' => "Solo hay {$maxQuantity} unidad(es) disponible(s).",
            ]);
        }

        return $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $quantity],
        );
    }

    /**
     * @throws ValidationException
     */
    private function assertProductCanBeAdded(Product $product): void
    {
        if ($product->status !== ProductStatus::Active) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto no está disponible para la venta.',
            ]);
        }
    }

    private function maxAllowedQuantity(Product $product): int
    {
        return max(0, (int) ($product->inventory?->available_stock ?? 0));
    }
}

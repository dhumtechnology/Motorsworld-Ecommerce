<?php

namespace App\Actions\Cart;

use App\Enums\Products\ProductStatus;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Products\ProductVariant;
use Illuminate\Validation\ValidationException;

class UpdateCartItemQuantityAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Cart $cart, ProductVariant $variant, int $quantity): ?CartItem
    {
        $variant->loadMissing(['product', 'inventory']);
        $this->assertVariantCanBeAdded($variant);

        if ($quantity <= 0) {
            $cart->items()->where('product_variant_id', $variant->id)->delete();

            return null;
        }

        $maxQuantity = $this->maxAllowedQuantity($variant);

        if ($maxQuantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Este color no tiene stock disponible.',
            ]);
        }

        if ($quantity > $maxQuantity) {
            throw ValidationException::withMessages([
                'quantity' => "Solo hay {$maxQuantity} unidad(es) disponible(s) para este color.",
            ]);
        }

        return $cart->items()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'product_id' => $variant->product_id,
                'quantity' => $quantity,
            ],
        );
    }

    /**
     * @throws ValidationException
     */
    private function assertVariantCanBeAdded(ProductVariant $variant): void
    {
        if (! $variant->is_active || $variant->product?->status !== ProductStatus::Active) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'El producto o color no está disponible para la venta.',
            ]);
        }
    }

    private function maxAllowedQuantity(ProductVariant $variant): int
    {
        return max(0, (int) ($variant->inventory?->available_stock ?? 0));
    }
}

<?php

namespace App\Actions\Cart;

use App\Enums\Products\ProductStatus;
use App\Models\Auth\User;
use App\Models\Cart\Cart;
use App\Models\Products\ProductVariant;
use Illuminate\Support\Facades\DB;

class MergeGuestCartAction
{
    public function __construct(
        private readonly UpdateCartItemQuantityAction $updateQuantity,
    ) {}

    public function execute(User $user, string $sessionId): void
    {
        DB::transaction(function () use ($user, $sessionId): void {
            $guestCart = Cart::query()
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with('items')
                ->first();

            if ($guestCart === null || $guestCart->items->isEmpty()) {
                return;
            }

            $userCart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'session_id' => null,
                    'expiration_date' => null,
                ],
            );

            foreach ($guestCart->items as $guestItem) {
                $variant = ProductVariant::query()
                    ->with(['product', 'inventory'])
                    ->find($guestItem->product_variant_id);

                if ($variant === null || ! $variant->is_active || $variant->product?->status !== ProductStatus::Active) {
                    continue;
                }

                $existingQuantity = (int) $userCart->items()
                    ->where('product_variant_id', $variant->id)
                    ->value('quantity');

                $mergedQuantity = $existingQuantity + $guestItem->quantity;

                try {
                    $this->updateQuantity->execute($userCart, $variant, $mergedQuantity);
                } catch (\Illuminate\Validation\ValidationException) {
                    if ($existingQuantity > 0) {
                        continue;
                    }

                    $maxStock = max(0, (int) ($variant->inventory?->available_stock ?? 0));

                    if ($maxStock > 0) {
                        $this->updateQuantity->execute($userCart, $variant, $maxStock);
                    }
                }
            }

            $guestCart->delete();
        });
    }
}

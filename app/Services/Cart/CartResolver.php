<?php

namespace App\Services\Cart;

use App\Models\Auth\User;
use App\Models\Cart\Cart;
use Illuminate\Support\Carbon;

class CartResolver
{
    private const GUEST_CART_TTL_DAYS = 30;

    public function resolve(?User $user, string $sessionId): Cart
    {
        if ($user !== null) {
            return Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'session_id' => null,
                    'expiration_date' => null,
                ],
            );
        }

        return Cart::query()->firstOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => null,
                'expiration_date' => Carbon::now()->addDays(self::GUEST_CART_TTL_DAYS),
            ],
        );
    }
}

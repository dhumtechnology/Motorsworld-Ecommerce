<?php

namespace App\Services\Coupons;

use App\Models\Coupons\Coupon;

class CouponCodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const LENGTH = 8;

    public function generate(): string
    {
        do {
            $code = $this->randomCode();
        } while (Coupon::query()->where('code', $code)->exists());

        return $code;
    }

    private function randomCode(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }
}

<?php

namespace App\Enums\Coupons;

enum CouponType: string
{
    case Percent = 'percent';
    case Amount = 'amount';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Porcentaje',
            self::Amount => 'Cantidad exacta',
        };
    }
}

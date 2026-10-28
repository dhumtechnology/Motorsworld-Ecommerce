<?php

namespace App\Services\Coupons;

use App\Enums\Coupons\CouponType;
use App\Models\Coupons\Coupon;
use App\Support\Currency;

final class CouponQuote
{
    public function __construct(
        public readonly Coupon $coupon,
        public readonly float $discountAmount,
        public readonly string $currency,
        public readonly float $chargeAmount,
        public readonly float $subtotalAmount,
        public readonly float $grandPen,
        public readonly float $grandUsd,
        public readonly int $eligibleQuantity,
        public readonly float $discountPen,
        public readonly float $discountUsd,
        public readonly ?float $sellRate,
    ) {}

    public function appliedLabel(): string
    {
        return 'Cupón '.$this->coupon->code.' · '.$this->coupon->discountLabel();
    }

    public function conversionNote(): ?string
    {
        if ($this->coupon->type !== CouponType::Amount) {
            return null;
        }

        $from = Currency::normalize($this->coupon->currency ?: 'PEN');

        if ($from === $this->currency || $this->sellRate === null || $this->sellRate <= 0) {
            return null;
        }

        return 'Convertido de '.$this->coupon->discountLabel()
            .' al TC venta '.$this->formattedSellRate();
    }

    public function formattedSellRate(): string
    {
        return rtrim(rtrim(number_format((float) ($this->sellRate ?? 0), 4, '.', ''), '0'), '.') ?: '0';
    }
}

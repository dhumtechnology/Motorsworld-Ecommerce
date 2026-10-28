<?php

namespace App\Services\Shipping;

use App\Models\Shipping\ShippingRate;

final class ShippingQuote
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly float $amountPen,
        public readonly float $amountUsd,
        public readonly ?ShippingRate $rate,
    ) {}

    public function label(): string
    {
        return $this->rate?->name ?? 'Envío';
    }

    public function isFree(): bool
    {
        return $this->amount <= 0;
    }
}

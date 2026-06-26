<?php

namespace App\Services\Orders;

readonly class ProductPricing
{
    public function __construct(
        public string $unitPrice,
        public string $listUnitPrice,
        public string $currency,
        public ?int $productOfferId = null,
    ) {}

    public function hasOffer(): bool
    {
        return $this->productOfferId !== null;
    }

    public function discountAmount(): string
    {
        if (! $this->hasOffer()) {
            return '0.00';
        }

        return bcsub($this->listUnitPrice, $this->unitPrice, 2);
    }

    public function lineTotal(int $quantity): string
    {
        return bcmul($this->unitPrice, (string) $quantity, 2);
    }
}

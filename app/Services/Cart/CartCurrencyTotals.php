<?php

namespace App\Services\Cart;

final class CartCurrencyTotals
{
    public function __construct(
        public readonly float $totalPen,
        public readonly float $totalUsd,
        public readonly float $grandPen,
        public readonly float $grandUsd,
        public readonly ?float $sellRate,
        public readonly ?string $rateDate,
        public readonly bool $hasRate,
    ) {}

    public function hasPen(): bool
    {
        return $this->totalPen > 0;
    }

    public function hasUsd(): bool
    {
        return $this->totalUsd > 0;
    }

    public function isMixed(): bool
    {
        return $this->hasPen() && $this->hasUsd();
    }

    /**
     * Moneda de cobro preferida (Culqi PE): PEN si hay tasa o solo soles.
     */
    public function chargeCurrency(): string
    {
        if ($this->hasRate || ! $this->hasUsd()) {
            return 'PEN';
        }

        return 'USD';
    }

    public function chargeAmount(): float
    {
        return $this->chargeCurrency() === 'USD' ? $this->grandUsd : $this->grandPen;
    }

    public function amountCents(): int
    {
        return (int) round($this->chargeAmount() * 100);
    }
}

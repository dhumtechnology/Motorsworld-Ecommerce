<?php

namespace App\Services\Cart;

use App\Services\Finance\DecolectaExchangeRateService;
use App\Support\Currency;
use Illuminate\Support\Collection;

class CartTotalsService
{
    public function __construct(
        private readonly DecolectaExchangeRateService $exchangeRates,
    ) {}

    /**
     * Suma nativa por moneda y totales convertidos con el TC venta.
     * Usa BD primero; solo si no hay tasa, consulta la API una vez y la guarda.
     *
     * @param  iterable<int, array{line_total?: float|int|string, currency?: string|null}>|Collection<int, array<string, mixed>>  $lines
     */
    public function summarize(iterable $lines, ?float $sellRateOverride = null, ?string $rateDateOverride = null): CartCurrencyTotals
    {
        $totalPen = 0.0;
        $totalUsd = 0.0;

        foreach ($lines as $line) {
            $amount = round((float) ($line['line_total'] ?? 0), 2);
            $currency = Currency::normalize($line['currency'] ?? 'PEN');

            if ($currency === 'USD') {
                $totalUsd += $amount;
            } else {
                $totalPen += $amount;
            }
        }

        $totalPen = round($totalPen, 2);
        $totalUsd = round($totalUsd, 2);

        $sellRate = $sellRateOverride;
        $rateDate = $rateDateOverride;

        if ($sellRate === null || $sellRate <= 0) {
            $rate = $this->exchangeRates->currentOrFetch();
            $sellRate = $rate !== null ? (float) $rate->sell_price : null;
            $rateDate = $rate?->rate_date?->toDateString();
        }

        $hasRate = $sellRate !== null && $sellRate > 0;

        if ($hasRate) {
            $grandPen = round(
                $totalPen + Currency::convert($totalUsd, 'USD', 'PEN', $sellRate),
                2,
            );
            $grandUsd = round(
                $totalUsd + Currency::convert($totalPen, 'PEN', 'USD', $sellRate),
                2,
            );
        } else {
            $grandPen = $totalPen;
            $grandUsd = $totalUsd;
        }

        return new CartCurrencyTotals(
            totalPen: $totalPen,
            totalUsd: $totalUsd,
            grandPen: $grandPen,
            grandUsd: $grandUsd,
            sellRate: $hasRate ? $sellRate : null,
            rateDate: $rateDate,
            hasRate: $hasRate,
        );
    }
}

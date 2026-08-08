<?php

namespace App\Support;

final class Currency
{
    public static function symbol(?string $currency): string
    {
        return match (strtoupper(trim((string) $currency))) {
            'USD' => '$',
            default => 'S/',
        };
    }

    public static function normalize(?string $currency): string
    {
        $code = strtoupper(trim((string) $currency));

        return in_array($code, ['USD', 'PEN'], true) ? $code : 'PEN';
    }

    /**
     * Convierte montos entre PEN y USD usando el tipo de cambio venta (USD→PEN)
     * congelado en la compra. Si no hay tasa, no convierte (devuelve el monto original
     * solo cuando origen = destino).
     */
    public static function convert(
        float $amount,
        ?string $from,
        ?string $to,
        ?float $sellRate,
    ): float {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return round($amount, 2);
        }

        $rate = (float) ($sellRate ?? 0);

        if ($rate <= 0) {
            return round($amount, 2);
        }

        if ($from === 'USD' && $to === 'PEN') {
            return round($amount * $rate, 2);
        }

        if ($from === 'PEN' && $to === 'USD') {
            return round($amount / $rate, 2);
        }

        return round($amount, 2);
    }

    public static function format(float $amount, ?string $currency): string
    {
        return self::symbol($currency).' '.number_format($amount, 2, '.', ',');
    }
}

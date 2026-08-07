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
}

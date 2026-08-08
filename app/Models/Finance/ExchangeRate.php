<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'rate_date',
    'buy_price',
    'sell_price',
    'base_currency',
    'quote_currency',
    'source',
    'payload',
    'fetched_at',
])]
class ExchangeRate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_date' => 'date',
            'buy_price' => 'decimal:4',
            'sell_price' => 'decimal:4',
            'payload' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    public static function latestAvailable(): ?self
    {
        return static::query()
            ->orderByDesc('rate_date')
            ->orderByDesc('fetched_at')
            ->first();
    }
}

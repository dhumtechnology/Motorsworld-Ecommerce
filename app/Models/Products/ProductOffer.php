<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'product_id',
    'offer_price_amount',
    'currency',
    'starts_at',
    'ends_at',
])]
class ProductOffer extends Model
{
    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActiveAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->starts_at->lte($at) && $this->ends_at->gte($at);
    }

    /**
     * @param  Builder<ProductOffer>  $query
     */
    public function scopeActiveAt(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();

        $query
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offer_price_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}

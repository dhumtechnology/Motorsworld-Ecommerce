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
    'discount_percent',
    'reason',
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

    public function resolvedDiscountPercent(?float $listPrice = null): float
    {
        if ($this->discount_percent !== null) {
            return (float) $this->discount_percent;
        }

        $listPrice ??= (float) ($this->product?->price_amount ?? 0);
        $offerPrice = (float) $this->offer_price_amount;

        if ($listPrice <= 0 || $offerPrice >= $listPrice) {
            return 0.0;
        }

        return round((($listPrice - $offerPrice) / $listPrice) * 100, 2);
    }

    public function isActiveAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->starts_at->lte($at) && $this->ends_at->gte($at);
    }

    /**
     * @return 'active'|'scheduled'|'expired'
     */
    public function lifecycleStatus(?Carbon $at = null): string
    {
        $at ??= now();

        if ($this->starts_at->gt($at)) {
            return 'scheduled';
        }

        if ($this->ends_at->lt($at)) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * @return array{label: string, class: string}
     */
    public function lifecycleMeta(?Carbon $at = null): array
    {
        return match ($this->lifecycleStatus($at)) {
            'active' => [
                'label' => 'Vigente',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'scheduled' => [
                'label' => 'Programada',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200',
            ],
            'expired' => [
                'label' => 'Expirada',
                'class' => 'bg-secondary text-muted border-border',
            ],
        };
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
            'discount_percent' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}

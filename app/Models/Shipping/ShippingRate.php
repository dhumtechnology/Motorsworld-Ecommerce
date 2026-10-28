<?php

namespace App\Models\Shipping;

use App\Models\Locations\District;
use App\Support\Currency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'description',
    'price',
    'currency',
    'is_fallback',
    'is_active',
    'sort_order',
])]
class ShippingRate extends Model
{
    /**
     * @return BelongsToMany<District, $this>
     */
    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'shipping_rate_district');
    }

    public function priceLabel(): string
    {
        return Currency::format((float) $this->price, $this->currency);
    }

    public function areasLabel(): string
    {
        $description = trim((string) ($this->description ?? ''));

        if ($description !== '') {
            return $description;
        }

        if ($this->is_fallback) {
            return 'Resto del Perú';
        }

        if ($this->relationLoaded('districts')) {
            return $this->districts->pluck('name')->filter()->implode(', ') ?: 'Sin distritos asignados';
        }

        return 'Zona de envío';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_fallback' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

<?php

namespace App\Models\Locations;

use App\Models\Shipping\ShippingRate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['department_id', 'province_id', 'name', 'ubigeo'])]
class District extends Model
{
    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @return BelongsToMany<ShippingRate, $this>
     */
    public function shippingRates(): BelongsToMany
    {
        return $this->belongsToMany(ShippingRate::class, 'shipping_rate_district');
    }

    public function displayName(): string
    {
        $province = $this->relationLoaded('province') ? $this->province?->name : null;
        $department = $this->relationLoaded('department') ? $this->department?->name : null;

        return trim($this->name.($province ? ' — '.$province : '').($department ? ' / '.$department : ''));
    }
}

<?php

namespace App\Models\Orders;

use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use App\Models\Locations\Country;
use App\Models\Locations\Department;
use App\Models\Locations\District;
use App\Models\Locations\Province;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'line1',
    'city',
    'postal_code',
    'country',
    'country_id',
    'department_id',
    'province_id',
    'district_id',
])]
class Address extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function countryRecord(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

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
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function locationLabel(): string
    {
        $parts = array_filter([
            $this->district?->name ?? $this->city,
            $this->province?->name,
            $this->department?->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function shippingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function billingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'billing_address_id');
    }

    /**
     * @return HasMany<CustomerProfile, $this>
     */
    public function defaultShippingProfiles(): HasMany
    {
        return $this->hasMany(CustomerProfile::class, 'default_shipping_address_id');
    }

    /**
     * @return HasMany<CustomerProfile, $this>
     */
    public function defaultBillingProfiles(): HasMany
    {
        return $this->hasMany(CustomerProfile::class, 'default_billing_address_id');
    }
}

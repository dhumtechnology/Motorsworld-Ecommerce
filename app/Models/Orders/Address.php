<?php

namespace App\Models\Orders;

use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'line1', 'city', 'postal_code', 'country'])]
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

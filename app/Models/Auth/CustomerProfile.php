<?php

namespace App\Models\Auth;

use App\Models\Orders\Address;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'document',
    'first_name',
    'last_name',
    'phone',
    'gender',
    'avatar',
    'default_shipping_address_id',
    'default_billing_address_id',
])]
class CustomerProfile extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function defaultShippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'default_shipping_address_id');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function defaultBillingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'default_billing_address_id');
    }
}

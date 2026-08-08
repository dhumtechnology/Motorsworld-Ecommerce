<?php

namespace App\Models\Orders;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'status',
    'payment_status',
    'total_amount',
    'currency',
    'exchange_rate_buy',
    'exchange_rate_sell',
    'exchange_rate_date',
    'fulfillment_method',
    'shipping_address_id',
    'billing_address_id',
])]
class Order extends Model
{
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
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest('id')->first();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'total_amount' => 'decimal:2',
            'exchange_rate_buy' => 'decimal:4',
            'exchange_rate_sell' => 'decimal:4',
            'exchange_rate_date' => 'date',
        ];
    }

    public function amountIn(string $targetCurrency): float
    {
        return \App\Support\Currency::convert(
            (float) $this->total_amount,
            $this->currency,
            $targetCurrency,
            $this->exchange_rate_sell !== null ? (float) $this->exchange_rate_sell : null,
        );
    }
}

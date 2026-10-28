<?php

namespace App\Models\Orders;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use App\Models\Coupons\CouponUsage;
use App\Models\Shipping\ShippingRate;
use App\Support\Currency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'status',
    'payment_status',
    'subtotal_amount',
    'discount_amount',
    'shipping_amount',
    'shipping_rate_id',
    'total_amount',
    'currency',
    'coupon_id',
    'coupon_code',
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

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<ShippingRate, $this>
     */
    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    /**
     * @return HasOne<CouponUsage, $this>
     */
    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function hasDiscount(): bool
    {
        return (float) ($this->discount_amount ?? 0) > 0;
    }

    public function hasShipping(): bool
    {
        return (float) ($this->shipping_amount ?? 0) > 0;
    }

    public function subtotalAmount(): float
    {
        if ($this->subtotal_amount !== null) {
            return (float) $this->subtotal_amount;
        }

        return round((float) $this->total_amount + (float) ($this->discount_amount ?? 0), 2);
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
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'exchange_rate_buy' => 'decimal:4',
            'exchange_rate_sell' => 'decimal:4',
            'exchange_rate_date' => 'date',
        ];
    }

    public function amountIn(string $targetCurrency): float
    {
        return Currency::convert(
            (float) $this->total_amount,
            $this->currency,
            $targetCurrency,
            $this->exchange_rate_sell !== null ? (float) $this->exchange_rate_sell : null,
        );
    }
}

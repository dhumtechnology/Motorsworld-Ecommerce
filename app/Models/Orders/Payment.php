<?php

namespace App\Models\Orders;

use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentRecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'provider',
    'method',
    'status',
    'amount_cents',
    'currency',
    'culqi_charge_id',
    'culqi_order_id',
    'payment_code',
    'qr_url',
    'payment_url',
    'source_id',
    'provider_payload',
    'paid_at',
    'expires_at',
])]
class Payment extends Model
{
    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentRecordStatus::Paid;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentRecordStatus::class,
            'provider_payload' => 'array',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}

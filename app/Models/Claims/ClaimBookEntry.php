<?php

namespace App\Models\Claims;

use App\Enums\Claims\ClaimBookGoodType;
use App\Enums\Claims\ClaimBookStatus;
use App\Enums\Claims\ClaimBookType;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code',
    'user_id',
    'handled_by',
    'first_name',
    'last_name',
    'document',
    'address',
    'phone',
    'email',
    'good_type',
    'good_description',
    'claimed_amount',
    'claim_type',
    'detail',
    'consumer_request',
    'status',
    'admin_notes',
    'admin_reply',
    'replied_at',
])]
class ClaimBookEntry extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function consumerFullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * @param  Builder<ClaimBookEntry>  $query
     * @return Builder<ClaimBookEntry>
     */
    public function scopeOfType(Builder $query, ClaimBookType $type): Builder
    {
        return $query->where('claim_type', $type->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'good_type' => ClaimBookGoodType::class,
            'claim_type' => ClaimBookType::class,
            'status' => ClaimBookStatus::class,
            'claimed_amount' => 'decimal:2',
            'replied_at' => 'datetime',
        ];
    }
}

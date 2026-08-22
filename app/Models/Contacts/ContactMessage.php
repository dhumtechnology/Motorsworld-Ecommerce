<?php

namespace App\Models\Contacts;

use App\Enums\Contacts\ContactMessageStatus;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code',
    'user_id',
    'handled_by',
    'first_name',
    'last_name',
    'document',
    'phone',
    'email',
    'message',
    'status',
    'admin_notes',
    'admin_reply',
    'replied_at',
])]
class ContactMessage extends Model
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

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'replied_at' => 'datetime',
        ];
    }
}

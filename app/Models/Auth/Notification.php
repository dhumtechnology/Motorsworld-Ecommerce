<?php

namespace App\Models\Auth;

use App\Enums\Auth\NotificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['email_address', 'type', 'body', 'html'])]
class Notification extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'created_at' => 'datetime',
        ];
    }
}

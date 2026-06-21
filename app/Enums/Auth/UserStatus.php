<?php

namespace App\Enums\Auth;

enum UserStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Disabled = 'disabled';
    case Locked = 'locked';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}

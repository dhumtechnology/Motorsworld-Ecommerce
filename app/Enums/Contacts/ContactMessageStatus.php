<?php

namespace App\Enums\Contacts;

enum ContactMessageStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En atención',
            self::Resolved => 'Resuelto',
            self::Closed => 'Cerrado',
        };
    }
}

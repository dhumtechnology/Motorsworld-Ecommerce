<?php

namespace App\Enums\Appointments;

enum ServiceStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
}

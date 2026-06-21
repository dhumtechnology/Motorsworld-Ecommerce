<?php

namespace App\Enums\Appointments;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Attended = 'attended';
    case Absent = 'absent';
    case Cancelled = 'cancelled';
}

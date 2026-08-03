<?php

namespace App\Enums\Appointments;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Attended = 'attended';
    case Absent = 'absent';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Accepted => 'Aceptada',
            self::InProgress => 'En curso',
            self::Attended => 'Atendida',
            self::Absent => 'Ausente',
            self::Cancelled => 'Cancelada',
        };
    }
}

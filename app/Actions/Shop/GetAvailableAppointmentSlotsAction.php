<?php

namespace App\Actions\Shop;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetAvailableAppointmentSlotsAction
{
    public const OPEN_HOUR = 8;

    public const CLOSE_HOUR = 18;

    /**
     * Horarios disponibles (inicio de cita) entre 08:00 y 17:00,
     * de modo que el servicio quepa dentro de la jornada hasta las 18:00.
     *
     * @return list<string> horas en formato H:i
     */
    public function execute(CarbonInterface|string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($day->isPast() && ! $day->isToday()) {
            return [];
        }

        $taken = Appointment::query()
            ->whereDate('appointment_at', $day->toDateString())
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled,
                AppointmentStatus::Absent,
            ])
            ->pluck('appointment_at')
            ->map(fn ($at) => Carbon::parse($at)->format('H:i'))
            ->unique()
            ->all();

        $takenLookup = array_fill_keys($taken, true);
        $slots = [];
        $now = now();

        for ($hour = self::OPEN_HOUR; $hour < self::CLOSE_HOUR; $hour++) {
            $slot = $day->copy()->setTime($hour, 0, 0);
            $label = $slot->format('H:i');

            if (isset($takenLookup[$label])) {
                continue;
            }

            if ($day->isToday() && $slot->lte($now)) {
                continue;
            }

            $slots[] = $label;
        }

        return $slots;
    }

    /**
     * @return Collection<int, string>
     */
    public function allDayHours(): Collection
    {
        $hours = collect();

        for ($hour = self::OPEN_HOUR; $hour < self::CLOSE_HOUR; $hour++) {
            $hours->push(sprintf('%02d:00', $hour));
        }

        return $hours;
    }
}

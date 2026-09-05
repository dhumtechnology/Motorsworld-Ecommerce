<?php

namespace App\Actions\Shop;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetAvailableAppointmentSlotsAction
{
    public const OPEN_HOUR = 9;

    public const OPEN_MINUTE = 30;

    public const CLOSE_HOUR = 18;

    public const SLOT_INTERVAL_MINUTES = 60;

    /**
     * Horarios disponibles (inicio de cita) entre 09:30 y 17:30,
     * de lunes a viernes, de modo que el servicio quepa hasta las 18:00.
     *
     * @return list<string> horas en formato H:i
     */
    public function execute(CarbonInterface|string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();

        // Citas solo de lunes a viernes
        if ($day->isWeekend()) {
            return [];
        }

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
            ->map(fn($at) => Carbon::parse($at)->format('H:i'))
            ->unique()
            ->all();

        $takenLookup = array_fill_keys($taken, true);
        $slots = [];
        $now = now();

        for (
            $slot = $day->copy()->setTime(self::OPEN_HOUR, self::OPEN_MINUTE, 0);
            $slot->lt($day->copy()->setTime(self::CLOSE_HOUR, 0, 0));
            $slot->addMinutes(self::SLOT_INTERVAL_MINUTES)
        ) {
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

        for (
            $slot = Carbon::createFromTime(self::OPEN_HOUR, self::OPEN_MINUTE);
            $slot->lt(Carbon::createFromTime(self::CLOSE_HOUR, 0));
            $slot->addMinutes(self::SLOT_INTERVAL_MINUTES)
        ) {
            $hours->push($slot->format('H:i'));
        }

        return $hours;
    }
}

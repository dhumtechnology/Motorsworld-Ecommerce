<?php

namespace App\Actions\Shop;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Appointments\Appointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetAvailableAppointmentSlotsAction
{
    public const SLOT_INTERVAL_MINUTES = 60;

    /**
     * Horarios de inicio de cita alineados al horario de atención:
     * lunes a viernes 9:00–13:00 y 14:00–18:30; sábados 9:00–14:00.
     * El último turno de cada bloque cabe en 60 minutos antes del cierre.
     *
     * @return list<string> horas en formato H:i
     */
    public function execute(CarbonInterface|string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($day->isSunday()) {
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
            ->map(fn ($at) => Carbon::parse($at)->format('H:i'))
            ->unique()
            ->all();

        $takenLookup = array_fill_keys($taken, true);
        $now = now();
        $slots = [];

        foreach ($this->windowsFor($day) as $window) {
            foreach ($this->startsInWindow($day, $window) as $label) {
                if (isset($takenLookup[$label])) {
                    continue;
                }

                $slot = $day->copy()->setTimeFromTimeString($label.':00');
                if ($day->isToday() && $slot->lte($now)) {
                    continue;
                }

                $slots[] = $label;
            }
        }

        return $slots;
    }

    /**
     * @return Collection<int, string>
     */
    public function allDayHours(): Collection
    {
        $monday = Carbon::now()->startOfWeek(CarbonInterface::MONDAY);

        return collect($this->startsForDay($monday));
    }

    /**
     * @return list<array{0: int, 1: int, 2: int, 3: int}>
     */
    private function windowsFor(CarbonInterface $day): array
    {
        if ($day->isSunday()) {
            return [];
        }

        if ($day->isSaturday()) {
            return [[9, 0, 14, 0]];
        }

        return [
            [9, 0, 13, 0],
            [14, 0, 18, 30],
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int, 3: int}  $window
     * @return list<string>
     */
    private function startsInWindow(CarbonInterface $day, array $window): array
    {
        [$startHour, $startMinute, $endHour, $endMinute] = $window;

        $start = $day->copy()->setTime($startHour, $startMinute, 0);
        $end = $day->copy()->setTime($endHour, $endMinute, 0);
        $lastStart = $end->copy()->subMinutes(self::SLOT_INTERVAL_MINUTES);

        if ($lastStart->lt($start)) {
            return [];
        }

        $labels = [];

        for ($slot = $start->copy(); $slot->lte($lastStart); $slot->addMinutes(self::SLOT_INTERVAL_MINUTES)) {
            $labels[] = $slot->format('H:i');
        }

        $lastLabel = $lastStart->format('H:i');
        if (! in_array($lastLabel, $labels, true)) {
            $labels[] = $lastLabel;
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    private function startsForDay(CarbonInterface $day): array
    {
        $labels = [];

        foreach ($this->windowsFor($day) as $window) {
            foreach ($this->startsInWindow($day, $window) as $label) {
                $labels[] = $label;
            }
        }

        return $labels;
    }
}

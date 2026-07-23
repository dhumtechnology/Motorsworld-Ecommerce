<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Appointments\UpdateAppointmentAction;
use App\Enums\Appointments\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentIndexRequest;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Models\Appointments\Appointment;
use App\Models\Appointments\ServiceType;
use App\Models\Products\VehicleModel;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class AppointmentController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpdateAppointmentAction $updateAppointment,
    ) {}

    public function index(AppointmentIndexRequest $request): View
    {
        $mode = $request->mode();
        $month = $request->month();
        $selectedDate = $request->selectedDate() ?? ($mode === 'calendar' ? now()->startOfDay() : null);

        $baseQuery = Appointment::query()
            ->with([
                'user.customerProfile',
                'serviceType:id,name',
                'vehicleModel.brand:id,name',
            ])
            ->when(
                $request->status(),
                fn (Builder $query, AppointmentStatus $status) => $query->where('status', $status),
            )
            ->when(
                $request->serviceTypeId(),
                fn (Builder $query, int $serviceTypeId) => $query->where('service_type_id', $serviceTypeId),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('plate', 'like', $like)
                            ->orWhere('comments', 'like', $like)
                            ->orWhere('id', 'like', $like)
                            ->orWhereHas('user', function (Builder $userQuery) use ($like) {
                                $userQuery
                                    ->where('email', 'like', $like)
                                    ->orWhereHas('customerProfile', function (Builder $profileQuery) use ($like) {
                                        $profileQuery
                                            ->where('first_name', 'like', $like)
                                            ->orWhere('last_name', 'like', $like)
                                            ->orWhere('document', 'like', $like)
                                            ->orWhere('phone', 'like', $like);
                                    });
                            });
                    });
                },
            );

        $appointments = null;
        $dayAppointments = collect();
        $calendarDays = [];
        $countsByDate = [];

        if ($mode === 'calendar') {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthAppointments = (clone $baseQuery)
                ->whereBetween('appointment_at', [$monthStart, $monthEnd])
                ->orderBy('appointment_at')
                ->get();

            $countsByDate = $monthAppointments
                ->groupBy(fn (Appointment $appointment) => $appointment->appointment_at->format('Y-m-d'))
                ->map->count()
                ->all();

            $calendarDays = $this->buildCalendarDays($month, $countsByDate);

            if ($selectedDate !== null) {
                $dayAppointments = $monthAppointments
                    ->filter(fn (Appointment $appointment) => $appointment->appointment_at->isSameDay($selectedDate))
                    ->values();
            }
        } else {
            $appointments = (clone $baseQuery)
                ->orderByDesc('appointment_at')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        }

        return view('admin.appointments.index', [
            'mode' => $mode,
            'appointments' => $appointments,
            'dayAppointments' => $dayAppointments,
            'calendarDays' => $calendarDays,
            'countsByDate' => $countsByDate,
            'month' => $month,
            'selectedDate' => $selectedDate,
            'statuses' => AppointmentStatus::cases(),
            'serviceTypes' => ServiceType::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->status()?->value,
                'service_type_id' => $request->serviceTypeId(),
                'mode' => $mode,
                'month' => $month->format('Y-m'),
                'date' => $selectedDate?->format('Y-m-d'),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function edit(Appointment $appointment): View
    {
        $appointment->load([
            'user.customerProfile',
            'serviceType',
            'vehicleModel.brand',
            'services.serviceType',
        ]);

        return view('admin.appointments.edit', [
            'appointment' => $appointment,
            'statuses' => AppointmentStatus::cases(),
            'serviceTypes' => ServiceType::query()->orderBy('name')->get(['id', 'name']),
            'models' => VehicleModel::query()
                ->with('brand:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'brand_id']),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment = $this->updateAppointment->execute(
            $appointment,
            $request->appointmentAttributes(),
        );

        return redirect()
            ->route('admin.appointments.index')
            ->with('status', "Reserva #{$appointment->id} actualizada correctamente.");
    }

    /**
     * @param  array<string, int>  $countsByDate
     * @return list<array{date: ?Carbon, inMonth: bool, count: int, isToday: bool, isSelected: bool}>
     */
    private function buildCalendarDays(Carbon $month, array $countsByDate): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $selected = request()->query('date');

        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $inMonth = $cursor->month === $month->month;

            $days[] = [
                'date' => $cursor->copy(),
                'inMonth' => $inMonth,
                'count' => $inMonth ? (int) ($countsByDate[$key] ?? 0) : 0,
                'isToday' => $cursor->isToday(),
                'isSelected' => $selected === $key,
            ];

            $cursor->addDay();
        }

        return $days;
    }
}

@extends('layouts.admin')

@section('title', 'Reservas — Admin')
@section('page-title', 'Reservas')
@section('page-subtitle', 'Citas y servicios agendados')

@section('content')
    @php
        $statusLabels = [
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'in_progress' => ['label' => 'En curso', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'attended' => ['label' => 'Atendida', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'absent' => ['label' => 'Ausente', 'class' => 'bg-secondary text-muted border-border'],
            'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];

        $queryBase = array_filter([
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'service_type_id' => $filters['service_type_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $listUrl = route('admin.appointments.index', array_merge($queryBase, ['mode' => 'list']));
        $calendarUrl = route('admin.appointments.index', array_merge($queryBase, [
            'mode' => 'calendar',
            'month' => $filters['month'] ?? now()->format('Y-m'),
            'date' => $filters['date'] ?? now()->format('Y-m-d'),
        ]));

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $prevMonthUrl = route('admin.appointments.index', array_merge($queryBase, [
            'mode' => 'calendar',
            'month' => $prevMonth,
            'date' => $prevMonth.'-01',
        ]));
        $nextMonthUrl = route('admin.appointments.index', array_merge($queryBase, [
            'mode' => 'calendar',
            'month' => $nextMonth,
            'date' => $nextMonth.'-01',
        ]));
    @endphp

    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.appointments.index') }}" id="admin-appointments-filters" class="space-y-4">
            <input type="hidden" name="mode" value="{{ $mode }}">
            @if ($mode === 'calendar')
                <input type="hidden" name="month" value="{{ $filters['month'] }}">
                <input type="hidden" name="date" value="{{ $filters['date'] }}">
            @endif

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Cliente, placa, email o #reserva..."
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>

                <div class="lg:col-span-3">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                    <select id="status" name="status" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ $statusLabels[$status->value]['label'] ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-4">
                    <label for="service_type_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Servicio</label>
                    <select id="service_type_id" name="service_type_id" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos los servicios</option>
                        @foreach ($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}" @selected((int) ($filters['service_type_id'] ?? 0) === $serviceType->id)>
                                {{ $serviceType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <p id="filters-live-hint" class="text-xs text-muted">Los filtros se aplican automáticamente</p>
                    @if ($hasActiveFilters)
                        <a
                            href="{{ route('admin.appointments.index', ['mode' => $mode, 'month' => $filters['month'] ?? null, 'date' => $filters['date'] ?? null]) }}"
                            class="rounded border border-border px-4 py-2 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
                        >
                            Limpiar
                        </a>
                    @endif
                </div>

                <div class="inline-flex rounded border border-border overflow-hidden">
                    <a href="{{ $listUrl }}" class="px-4 py-2 text-xs font-bold uppercase tracking-wide transition-colors {{ $mode === 'list' ? 'bg-primary text-white' : 'bg-secondary text-muted hover:text-text' }}">
                        Lista
                    </a>
                    <a href="{{ $calendarUrl }}" class="px-4 py-2 text-xs font-bold uppercase tracking-wide transition-colors border-l border-border {{ $mode === 'calendar' ? 'bg-primary text-white' : 'bg-secondary text-muted hover:text-text' }}">
                        Horario
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if ($mode === 'list')
        <div class="rounded-lg border border-border bg-surface overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <p class="text-sm text-muted">
                    <span class="text-text font-bold">{{ $appointments->total() }}</span>
                    {{ $appointments->total() === 1 ? 'reserva' : 'reservas' }}
                    @if ($hasActiveFilters)<span class="text-muted">(filtradas)</span>@endif
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                        <tr>
                            <th class="px-5 py-3 font-bold">Fecha</th>
                            <th class="px-5 py-3 font-bold">Cliente</th>
                            <th class="px-5 py-3 font-bold">Servicio</th>
                            <th class="px-5 py-3 font-bold">Vehículo</th>
                            <th class="px-5 py-3 font-bold">Estado</th>
                            <th class="px-5 py-3 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($appointments as $appointment)
                            @include('admin.appointments._row', ['appointment' => $appointment, 'statusLabels' => $statusLabels])
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-muted">No se encontraron reservas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($appointments->hasPages())
                <div class="px-5 py-4 border-t border-border">
                    {{ $appointments->links('vendor.pagination.admin') }}
                </div>
            @endif
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-5">
            <div class="xl:col-span-3 rounded-lg border border-border bg-surface p-5">
                <div class="flex items-center justify-between gap-3 mb-5">
                    <a href="{{ $prevMonthUrl }}" class="rounded border border-border px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong">←</a>
                    <h2 class="text-sm font-title text-text">
                        {{ $month->locale('es')->translatedFormat('F Y') }}
                    </h2>
                    <a href="{{ $nextMonthUrl }}" class="rounded border border-border px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong">→</a>
                </div>

                <div class="grid grid-cols-7 gap-1 mb-2">
                    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
                        <div class="px-1 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-muted">{{ $weekday }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-1">
                    @foreach ($calendarDays as $day)
                        @php
                            /** @var \Carbon\Carbon $date */
                            $date = $day['date'];
                            $dayUrl = route('admin.appointments.index', array_merge($queryBase, [
                                'mode' => 'calendar',
                                'month' => $month->format('Y-m'),
                                'date' => $date->format('Y-m-d'),
                            ]));
                        @endphp

                        @if ($day['inMonth'])
                            <a
                                href="{{ $dayUrl }}"
                                class="relative min-h-[4.5rem] rounded border p-2 transition-colors
                                    {{ $day['isSelected'] ? 'border-primary bg-primary-soft/30' : 'border-border bg-secondary hover:border-border-strong' }}
                                    {{ $day['isToday'] && ! $day['isSelected'] ? 'ring-1 ring-primary/40' : '' }}"
                            >
                                <span class="text-sm font-bold {{ $day['isSelected'] ? 'text-primary' : 'text-text' }}">
                                    {{ $date->day }}
                                </span>
                                @if ($day['count'] > 0)
                                    <span class="absolute bottom-2 left-2 right-2 inline-flex items-center justify-center rounded bg-primary/20 px-1 py-0.5 text-[10px] font-bold text-primary">
                                        {{ $day['count'] }} {{ $day['count'] === 1 ? 'cita' : 'citas' }}
                                    </span>
                                @endif
                            </a>
                        @else
                            <div class="min-h-[4.5rem] rounded border border-transparent bg-transparent p-2">
                                <span class="text-sm text-text-soft">{{ $date->day }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="xl:col-span-2 rounded-lg border border-border bg-surface overflow-hidden">
                <div class="px-5 py-4 border-b border-border">
                    <h2 class="text-sm font-title text-text">
                        {{ $selectedDate?->locale('es')->translatedFormat('l d/m/Y') ?? 'Selecciona un día' }}
                    </h2>
                    <p class="text-xs text-muted mt-1">
                        {{ $dayAppointments->count() }} {{ $dayAppointments->count() === 1 ? 'reserva' : 'reservas' }}
                    </p>
                </div>

                <div class="divide-y divide-border max-h-[34rem] overflow-y-auto">
                    @forelse ($dayAppointments as $appointment)
                        @php
                            $profile = $appointment->user?->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            $statusKey = $appointment->status instanceof \App\Enums\Appointments\AppointmentStatus
                                ? $appointment->status->value
                                : (string) $appointment->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                        @endphp
                        <div class="px-5 py-4 hover:bg-secondary/50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-primary font-mono text-sm font-bold">
                                        {{ $appointment->appointment_at?->format('H:i') }}
                                    </p>
                                    <p class="text-text font-semibold mt-1 truncate">
                                        {{ $fullName !== '' ? $fullName : ($appointment->user?->email ?? 'Sin cliente') }}
                                    </p>
                                    <p class="text-xs text-muted mt-0.5">
                                        {{ $appointment->serviceType?->name ?? 'Sin servicio' }}
                                        @if ($appointment->plate)
                                            · {{ $appointment->plate }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    <span class="inline-flex items-center rounded border px-2 py-0.5 text-[10px] font-bold uppercase {{ $statusMeta['class'] }}">
                                        {{ $statusMeta['label'] }}
                                    </span>
                                    <a href="{{ route('admin.appointments.edit', $appointment) }}" class="text-xs font-bold uppercase tracking-wide text-sky-700 hover:text-sky-800">
                                        Editar
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center text-muted text-sm">
                            No hay reservas para este día.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const form = document.getElementById('admin-appointments-filters');
            if (!form) return;

            let submitTimer = null;
            let isSubmitting = false;

            const setHint = (text) => {
                const hint = document.getElementById('filters-live-hint');
                if (hint) hint.textContent = text;
            };

            const submitFilters = () => {
                if (isSubmitting) return;
                isSubmitting = true;
                setHint('Actualizando resultados…');
                form.requestSubmit ? form.requestSubmit() : form.submit();
            };

            const scheduleSubmit = (delay = 250) => {
                clearTimeout(submitTimer);
                setHint('Aplicando filtros…');
                submitTimer = setTimeout(submitFilters, delay);
            };

            document.getElementById('search')?.addEventListener('input', () => scheduleSubmit(450));
            document.getElementById('search')?.addEventListener('search', () => scheduleSubmit(0));
            document.getElementById('status')?.addEventListener('change', () => scheduleSubmit(150));
            document.getElementById('service_type_id')?.addEventListener('change', () => scheduleSubmit(150));
        })();
    </script>
@endsection

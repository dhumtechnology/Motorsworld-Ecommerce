@extends('layouts.admin')

@section('title', 'Editar reserva #'.$appointment->id.' — Admin')
@section('page-title', 'Reserva #'.$appointment->id)
@section('page-subtitle', 'Actualizar datos de la cita')

@section('content')
    @php
        $profile = $appointment->user?->customerProfile;
        $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

        $statusLabels = [
            'pending' => 'Pendiente',
            'in_progress' => 'En curso',
            'attended' => 'Atendida',
            'absent' => 'Ausente',
            'cancelled' => 'Cancelada',
        ];

        $appointmentAtValue = old(
            'appointment_at',
            $appointment->appointment_at?->format('Y-m-d\TH:i')
        );
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a reservas
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 rounded-lg border border-border bg-surface p-6">
            @if ($errors->any())
                <div class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="appointment_at" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Fecha y hora *</label>
                        <input
                            id="appointment_at"
                            name="appointment_at"
                            type="datetime-local"
                            required
                            value="{{ $appointmentAtValue }}"
                            class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado *</label>
                        <select id="status" name="status" required class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $appointment->status->value) === $status->value)>
                                    {{ $statusLabels[$status->value] ?? $status->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="service_type_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Tipo de servicio *</label>
                        <select id="service_type_id" name="service_type_id" required class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="">Seleccionar</option>
                            @foreach ($serviceTypes as $serviceType)
                                <option value="{{ $serviceType->id }}" @selected((int) old('service_type_id', $appointment->service_type_id) === $serviceType->id)>
                                    {{ $serviceType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="vehicle_model_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Modelo</label>
                        <select id="vehicle_model_id" name="vehicle_model_id" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="">Sin modelo</option>
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}" @selected((int) old('vehicle_model_id', $appointment->vehicle_model_id) === $model->id)>
                                    {{ $model->brand?->name ? $model->brand->name.' — ' : '' }}{{ $model->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="plate" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Placa</label>
                        <input id="plate" name="plate" type="text" value="{{ old('plate', $appointment->plate) }}"
                               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    </div>

                    <div>
                        <label for="km" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Kilometraje</label>
                        <input id="km" name="km" type="number" step="0.01" min="0" value="{{ old('km', $appointment->km) }}"
                               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    </div>
                </div>

                <div>
                    <label for="comments" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Comentarios</label>
                    <textarea id="comments" name="comments" rows="4"
                              class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('comments', $appointment->comments) }}</textarea>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                        Guardar cambios
                    </button>
                    <a href="{{ route('admin.appointments.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-title text-text mb-4">Cliente</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nombre</dt>
                        <dd class="text-text font-semibold mt-0.5">{{ $fullName !== '' ? $fullName : 'Sin nombre' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Email</dt>
                        <dd class="text-text-soft mt-0.5 break-all">{{ $appointment->user?->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Documento</dt>
                        <dd class="text-text-soft mt-0.5 font-mono">{{ $profile?->document ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Teléfono</dt>
                        <dd class="text-text-soft mt-0.5">{{ $profile?->phone ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            @if ($appointment->services->isNotEmpty())
                <div class="rounded-lg border border-border bg-surface p-5">
                    <h2 class="text-sm font-title text-text mb-4">Servicios asociados</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($appointment->services as $service)
                            <li class="flex justify-between gap-3 border-b border-border pb-2 last:border-0 last:pb-0">
                                <span class="text-text-soft">{{ $service->serviceType?->name ?? 'Servicio' }}</span>
                                <span class="text-text font-semibold whitespace-nowrap">
                                    {{ number_format((float) $service->price, 2) }} {{ $service->currency }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection

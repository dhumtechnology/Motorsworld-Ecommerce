@php
    $profile = $appointment->user?->customerProfile;
    $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
    $statusKey = $appointment->status instanceof \App\Enums\Appointments\AppointmentStatus
        ? $appointment->status->value
        : (string) $appointment->status;
    $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'];
    $vehicleLabel = trim(
        ($appointment->vehicleModel?->brand?->name ? $appointment->vehicleModel->brand->name.' ' : '').
        ($appointment->vehicleModel?->name ?? '')
    );
@endphp
<tr class="hover:bg-[#252525]/60 transition-colors">
    <td class="px-5 py-3 text-neutral-300 whitespace-nowrap">
        <span class="block text-white font-semibold">{{ $appointment->appointment_at?->format('d/m/Y') }}</span>
        <span class="text-xs text-orange-400 font-mono">{{ $appointment->appointment_at?->format('H:i') }}</span>
    </td>
    <td class="px-5 py-3">
        <p class="font-semibold text-white">{{ $fullName !== '' ? $fullName : 'Sin nombre' }}</p>
        <p class="text-xs text-neutral-500 mt-0.5">{{ $appointment->user?->email ?? '—' }}</p>
    </td>
    <td class="px-5 py-3 text-neutral-300">{{ $appointment->serviceType?->name ?? '—' }}</td>
    <td class="px-5 py-3 text-neutral-300">
        {{ $vehicleLabel !== '' ? $vehicleLabel : '—' }}
        @if ($appointment->plate)
            <span class="block text-xs text-neutral-500 font-mono">{{ $appointment->plate }}</span>
        @endif
    </td>
    <td class="px-5 py-3">
        <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
            {{ $statusMeta['label'] }}
        </span>
    </td>
    <td class="px-5 py-3">
        <div class="flex justify-end">
            <a
                href="{{ route('admin.appointments.edit', $appointment) }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-800 bg-sky-950/50 text-sky-400 hover:bg-sky-900/60 transition-colors"
                title="Editar"
                aria-label="Editar reserva #{{ $appointment->id }}"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                </svg>
            </a>
        </div>
    </td>
</tr>

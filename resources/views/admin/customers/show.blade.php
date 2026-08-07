@extends('layouts.admin')

@section('title', ($customer->customerProfile?->first_name ?? $customer->email).' — Cliente')
@section('page-title', 'Detalle de cliente')
@section('page-subtitle', '#'.$customer->id)

@section('content')
    @php
        $profile = $customer->customerProfile;
        $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];
        $statusKey = $customer->status instanceof \App\Enums\Auth\UserStatus
            ? $customer->status->value
            : (string) $customer->status;
        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];

        $orderStatusLabels = [
            'created' => 'Creado',
            'paid' => 'Pagado',
            'processing' => 'En proceso',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];

        $appointmentStatusLabels = [
            'pending' => 'Pendiente',
            'accepted' => 'Aceptada',
            'in_progress' => 'En curso',
            'attended' => 'Atendida',
            'absent' => 'Ausente',
            'cancelled' => 'Cancelada',
        ];

        $lastOrder = $stats['last_order_at']
            ? \Illuminate\Support\Carbon::parse($stats['last_order_at'])->format('d/m/Y H:i')
            : '—';
        $lastAppointment = $stats['last_appointment_at']
            ? \Illuminate\Support\Carbon::parse($stats['last_appointment_at'])->format('d/m/Y H:i')
            : '—';
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a clientes
        </a>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 hover:bg-red-100 transition-colors"
            data-open-confirm="single-delete-modal"
            data-delete-url="{{ route('admin.customers.destroy', $customer) }}"
            data-delete-message="¿Eliminar al cliente «{{ $fullName !== '' ? $fullName : $customer->email }}»? Soft delete: se oculta del listado; pedidos y reservas se conservan."
        >
            Eliminar
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Estado</p>
            <p class="mt-2">
                <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                    {{ $statusMeta['label'] }}
                </span>
            </p>
            <p class="mt-2 text-xs text-muted">
                {{ $customer->isActive() ? 'Puede iniciar sesión' : 'Sin acceso (pendiente u otro estado)' }}
            </p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Pedidos</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['orders_count']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $stats['paid_orders_count'] }} con pago / proceso</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Total gastado</p>
            <p class="mt-2 text-2xl font-bold text-text">S/ {{ number_format($stats['total_spent'], 2) }}</p>
            <p class="mt-1 text-xs text-muted">Último pedido: {{ $lastOrder }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Reservas</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['appointments_count']) }}</p>
            <p class="mt-1 text-xs text-muted">Última: {{ $lastAppointment }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-border bg-surface p-6 mb-6">
        <h2 class="text-sm font-title text-text mb-1">Datos del cliente</h2>
        <p class="text-xs text-muted mb-5">Perfil y cuenta</p>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Nombre</dt>
                <dd class="mt-1 font-semibold text-text">{{ $fullName !== '' ? $fullName : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Email</dt>
                <dd class="mt-1 font-semibold text-text break-all">{{ $customer->email }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Documento</dt>
                <dd class="mt-1 font-mono text-text-soft">{{ $profile?->document ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Teléfono</dt>
                <dd class="mt-1 text-text-soft">{{ $profile?->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Registro</dt>
                <dd class="mt-1 text-text-soft">{{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Último acceso</dt>
                <dd class="mt-1 text-text-soft">{{ $customer->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
            </div>
        </dl>
    </div>

    <div class="grid gap-6 xl:grid-cols-2 mb-6">
        <div class="rounded-lg border border-border bg-surface overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h2 class="text-sm font-title text-text">Historial de compras</h2>
                <p class="text-xs text-muted mt-1">Últimos {{ $orders->count() }} pedidos</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                        <tr>
                            <th class="px-5 py-3 font-bold">Pedido</th>
                            <th class="px-5 py-3 font-bold">Fecha</th>
                            <th class="px-5 py-3 font-bold">Estado</th>
                            <th class="px-5 py-3 font-bold text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($orders as $order)
                            @php
                                $orderStatus = $order->status instanceof \App\Enums\Orders\OrderStatus
                                    ? $order->status->value
                                    : (string) $order->status;
                            @endphp
                            <tr class="hover:bg-secondary/50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-sky-700 hover:underline">
                                        #{{ $order->id }}
                                    </a>
                                    <p class="text-xs text-muted mt-0.5">{{ $order->items->count() }} ítem(s)</p>
                                </td>
                                <td class="px-5 py-3 text-muted whitespace-nowrap">
                                    {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-text-soft">
                                    {{ $orderStatusLabels[$orderStatus] ?? $orderStatus }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-text whitespace-nowrap">
                                    S/ {{ number_format((float) $order->total_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-muted">Sin pedidos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-border bg-surface overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h2 class="text-sm font-title text-text">Historial de reservas</h2>
                <p class="text-xs text-muted mt-1">Últimas {{ $appointments->count() }} citas</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                        <tr>
                            <th class="px-5 py-3 font-bold">Cita</th>
                            <th class="px-5 py-3 font-bold">Servicio</th>
                            <th class="px-5 py-3 font-bold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($appointments as $appointment)
                            @php
                                $apptStatus = $appointment->status instanceof \App\Enums\Appointments\AppointmentStatus
                                    ? $appointment->status->value
                                    : (string) $appointment->status;
                                $vehicle = trim(
                                    ($appointment->vehicleModel?->brand?->name ? $appointment->vehicleModel->brand->name.' ' : '').
                                    ($appointment->vehicleModel?->name ?? '')
                                );
                            @endphp
                            <tr class="hover:bg-secondary/50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.appointments.edit', $appointment) }}" class="font-semibold text-sky-700 hover:underline">
                                        {{ $appointment->appointment_at?->format('d/m/Y H:i') ?? '—' }}
                                    </a>
                                    @if ($appointment->plate)
                                        <p class="text-xs text-muted font-mono mt-0.5">{{ $appointment->plate }}</p>
                                    @endif
                                    @if ($vehicle !== '')
                                        <p class="text-xs text-muted mt-0.5">{{ $vehicle }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-text-soft">
                                    {{ $appointment->serviceType?->name ?? '—' }}
                                    @if ($appointment->servicePackage)
                                        <span class="block text-xs text-muted">{{ $appointment->servicePackage->name }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-text-soft">
                                    {{ $appointmentStatusLabels[$apptStatus] ?? $apptStatus }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-muted">Sin reservas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-confirm-modal
        id="single-delete-modal"
        title="Eliminar cliente"
        message="¿Seguro que deseas eliminar este cliente?"
        confirm-label="Eliminar"
        method="DELETE"
        :action="route('admin.customers.index')"
    />

    <script>
        (function () {
            const openModal = (modal) => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };
            const closeModal = (modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-confirm-modal]').forEach((modal) => {
                modal.querySelectorAll('[data-confirm-cancel], [data-confirm-overlay]').forEach((el) => {
                    el.addEventListener('click', () => closeModal(modal));
                });
                modal.querySelector('[data-confirm-submit]')?.addEventListener('click', () => {
                    modal.querySelector('[data-confirm-form]')?.submit();
                });
            });

            document.querySelectorAll('[data-open-confirm]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const modal = document.getElementById(trigger.getAttribute('data-open-confirm'));
                    if (!modal) return;
                    const form = modal.querySelector('[data-confirm-form]');
                    const messageEl = modal.querySelector('[data-confirm-message]');
                    const url = trigger.getAttribute('data-delete-url');
                    const message = trigger.getAttribute('data-delete-message');
                    if (form && url) form.action = url;
                    if (messageEl && message) messageEl.textContent = message;
                    openModal(modal);
                });
            });
        })();
    </script>
@endsection

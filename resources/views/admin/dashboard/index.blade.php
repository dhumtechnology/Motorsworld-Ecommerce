@extends('layouts.admin')

@section('title', 'Dashboard — Admin')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Métricas y actividad de Motosworld')

@section('content')
    @php
        $kpis = $kpis ?? [];
        $money = fn (float $amount): string => 'S/ '.number_format($amount, 2, '.', ',');
        $delta = (float) ($kpis['revenueDeltaPercent'] ?? 0);
        $deltaPositive = $delta >= 0;

        $appointmentStatusLabels = [
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'in_progress' => ['label' => 'En curso', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'attended' => ['label' => 'Atendida', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'absent' => ['label' => 'Ausente', 'class' => 'bg-secondary text-muted border-border'],
            'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];

        $orderStatusLabels = [
            'created' => ['label' => 'Creada', 'class' => 'bg-secondary text-text-soft border-border'],
            'paid' => ['label' => 'Pagada', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'processing' => ['label' => 'En proceso', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'shipped' => ['label' => 'Enviada', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'delivered' => ['label' => 'Entregada', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-50 text-red-600 border-red-200'],
            'refunded' => ['label' => 'Reembolsada', 'class' => 'bg-primary-soft text-primary border-primary/30'],
        ];

        $customerName = function ($user): string {
            $profile = $user?->customerProfile;
            $full = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

            return $full !== '' ? $full : ($user?->email ?? '—');
        };
    @endphp

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.customers.index') }}" class="admin-card admin-kpi group p-5 block hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="admin-label mb-0">Clientes</p>
                    <p class="text-3xl font-title text-text mt-2 tabular-nums">{{ number_format($kpis['customers'] ?? 0) }}</p>
                    <p class="text-xs text-muted mt-2 font-secondary">Registrados en la tienda</p>
                </div>
                <span class="admin-kpi-icon bg-sky-50 text-sky-700" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.appointments.index', ['status' => 'pending']) }}" class="admin-card admin-kpi group p-5 block hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="admin-label mb-0">Reservas pendientes</p>
                    <p class="text-3xl font-title text-primary mt-2 tabular-nums">{{ number_format($kpis['pendingAppointments'] ?? 0) }}</p>
                    <p class="text-xs text-muted mt-2 font-secondary">
                        {{ number_format($kpis['appointmentsToday'] ?? 0) }} hoy (activas)
                    </p>
                </div>
                <span class="admin-kpi-icon bg-amber-50 text-amber-700" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0121 8.25v11.25A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5V8.25a1.5 1.5 0 011.5-1.5z"/></svg>
                </span>
            </div>
        </a>

        <div class="admin-card admin-kpi p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="admin-label mb-0">Ganancias del mes</p>
                    <p class="text-3xl font-title text-text mt-2 tabular-nums">{{ $money((float) ($kpis['revenueThisMonth'] ?? 0)) }}</p>
                    <p class="text-xs mt-2 font-secondary {{ $deltaPositive ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $deltaPositive ? '+' : '' }}{{ number_format($delta, 1) }}% vs mes anterior
                    </p>
                </div>
                <span class="admin-kpi-icon bg-emerald-50 text-emerald-700" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.5 4.5L21.75 6M21.75 6H15.75M21.75 6v6"/></svg>
                </span>
            </div>
        </div>

        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="admin-card admin-kpi group p-5 block hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="admin-label mb-0">Pedidos por atender</p>
                    <p class="text-3xl font-title text-text mt-2 tabular-nums">{{ number_format($kpis['ordersToFulfill'] ?? 0) }}</p>
                    <p class="text-xs text-muted mt-2 font-secondary">
                        {{ number_format($kpis['ordersThisMonth'] ?? 0) }} creados este mes
                    </p>
                </div>
                <span class="admin-kpi-icon bg-primary-soft text-primary" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25h11.218c.51 0 .962-.343 1.087-.835l1.823-6.837a1.125 1.125 0 00-1.087-1.413H5.272M7.5 14.25L5.106 5.272M7.5 14.25l-2.122 6.378M16.5 14.25l2.122 6.378M9.75 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm8.25 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                </span>
            </div>
        </a>
    </div>

    {{-- Secondary KPIs --}}
    <div class="grid gap-4 sm:grid-cols-3 mt-4">
        <a href="{{ route('admin.products.index') }}" class="admin-card px-5 py-4 flex items-center justify-between gap-3 hover:border-primary/40 transition-colors">
            <div>
                <p class="admin-label mb-0">Productos activos</p>
                <p class="text-xl font-title text-text mt-1 tabular-nums">{{ number_format($kpis['activeProducts'] ?? 0) }}</p>
            </div>
            <span class="text-xs font-bold uppercase tracking-wider text-primary font-secondary">Ver →</span>
        </a>
        <a href="{{ route('admin.inventory.index') }}" class="admin-card px-5 py-4 flex items-center justify-between gap-3 hover:border-primary/40 transition-colors">
            <div>
                <p class="admin-label mb-0">Stock bajo</p>
                <p class="text-xl font-title {{ ($kpis['lowStockCount'] ?? 0) > 0 ? 'text-amber-600' : 'text-text' }} mt-1 tabular-nums">
                    {{ number_format($kpis['lowStockCount'] ?? 0) }}
                </p>
            </div>
            <span class="text-xs font-bold uppercase tracking-wider text-primary font-secondary">Inventario →</span>
        </a>
        <a href="{{ route('admin.appointments.index') }}" class="admin-card px-5 py-4 flex items-center justify-between gap-3 hover:border-primary/40 transition-colors">
            <div>
                <p class="admin-label mb-0">Reservas de hoy</p>
                <p class="text-xl font-title text-text mt-1 tabular-nums">{{ number_format($kpis['appointmentsToday'] ?? 0) }}</p>
            </div>
            <span class="text-xs font-bold uppercase tracking-wider text-primary font-secondary">Agenda →</span>
        </a>
    </div>

    {{-- Charts --}}
    <div class="grid gap-5 xl:grid-cols-3 mt-6">
        <div class="admin-card p-5 xl:col-span-2">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-title text-lg text-text">Ganancias</h2>
                    <p class="text-xs text-muted font-secondary mt-0.5">Ingresos por pagos confirmados · últimos 6 meses</p>
                </div>
                <a href="{{ route('admin.payments.index') }}" class="text-xs font-bold uppercase tracking-wider text-primary font-secondary hover:text-primary-hover">
                    Ver pagos →
                </a>
            </div>
            <div class="relative h-64 sm:h-72">
                <canvas id="dashboard-revenue-chart" aria-label="Gráfico de ganancias"></canvas>
            </div>
        </div>

        <div class="admin-card p-5">
            <div class="mb-4">
                <h2 class="font-title text-lg text-text">Reservas por estado</h2>
                <p class="text-xs text-muted font-secondary mt-0.5">Distribución actual</p>
            </div>
            <div class="relative h-64 sm:h-72 flex items-center justify-center">
                <canvas id="dashboard-appointments-chart" aria-label="Gráfico de reservas"></canvas>
            </div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3 mt-5">
        <div class="admin-card p-5">
            <div class="mb-4">
                <h2 class="font-title text-lg text-text">Pedidos por estado</h2>
                <p class="text-xs text-muted font-secondary mt-0.5">Embudo operativo</p>
            </div>
            <div class="relative h-56">
                <canvas id="dashboard-orders-chart" aria-label="Gráfico de pedidos"></canvas>
            </div>
        </div>

        {{-- Upcoming appointments --}}
        <div class="admin-card overflow-hidden xl:col-span-2">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-title text-lg text-text">Próximas reservas</h2>
                    <p class="text-xs text-muted font-secondary mt-0.5">Pendientes y en curso</p>
                </div>
                <a href="{{ route('admin.appointments.index') }}" class="text-xs font-bold uppercase tracking-wider text-primary font-secondary">Ver todas →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table w-full text-sm font-secondary">
                    <thead>
                        <tr>
                            <th class="text-left px-5 py-3">Fecha</th>
                            <th class="text-left px-5 py-3">Cliente</th>
                            <th class="text-left px-5 py-3">Servicio</th>
                            <th class="text-left px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($upcomingAppointments as $appointment)
                            @php
                                $statusKey = $appointment->status instanceof \App\Enums\Appointments\AppointmentStatus
                                    ? $appointment->status->value
                                    : (string) $appointment->status;
                                $statusMeta = $appointmentStatusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                            @endphp
                            <tr>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <a href="{{ route('admin.appointments.edit', $appointment) }}" class="font-semibold text-text hover:text-primary">
                                        {{ $appointment->appointment_at?->format('d/m/Y H:i') }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-text-soft truncate max-w-[10rem]">{{ $customerName($appointment->user) }}</td>
                                <td class="px-5 py-3 text-muted">{{ $appointment->serviceType?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusMeta['class'] }}">
                                        {{ $statusMeta['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-muted">No hay reservas próximas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent orders + low stock --}}
    <div class="grid gap-5 xl:grid-cols-2 mt-5">
        <div class="admin-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-title text-lg text-text">Últimos pedidos</h2>
                    <p class="text-xs text-muted font-secondary mt-0.5">Actividad reciente de la tienda</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold uppercase tracking-wider text-primary font-secondary">Ver órdenes →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table w-full text-sm font-secondary">
                    <thead>
                        <tr>
                            <th class="text-left px-5 py-3">#</th>
                            <th class="text-left px-5 py-3">Cliente</th>
                            <th class="text-left px-5 py-3">Total</th>
                            <th class="text-left px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            @php
                                $statusKey = $order->status instanceof \App\Enums\Orders\OrderStatus
                                    ? $order->status->value
                                    : (string) $order->status;
                                $statusMeta = $orderStatusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                            @endphp
                            <tr>
                                <td class="px-5 py-3 font-mono font-semibold text-primary">#{{ $order->id }}</td>
                                <td class="px-5 py-3 text-text-soft truncate max-w-[10rem]">{{ $customerName($order->user) }}</td>
                                <td class="px-5 py-3 tabular-nums text-text">{{ $money((float) $order->total_amount) }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusMeta['class'] }}">
                                        {{ $statusMeta['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-muted">Aún no hay pedidos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-title text-lg text-text">Alertas de inventario</h2>
                    <p class="text-xs text-muted font-secondary mt-0.5">Disponible ≤ 5 unidades</p>
                </div>
                <a href="{{ route('admin.inventory.index') }}" class="text-xs font-bold uppercase tracking-wider text-primary font-secondary">Inventario →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table w-full text-sm font-secondary">
                    <thead>
                        <tr>
                            <th class="text-left px-5 py-3">SKU</th>
                            <th class="text-left px-5 py-3">Producto</th>
                            <th class="text-right px-5 py-3">Disponible</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockProducts as $row)
                            <tr>
                                <td class="px-5 py-3 font-mono text-xs text-muted">{{ $row->product?->sku ?? '—' }}</td>
                                <td class="px-5 py-3 text-text truncate max-w-[14rem]">{{ $row->product?->name ?? 'Producto eliminado' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="inline-flex min-w-8 justify-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700 tabular-nums">
                                        {{ $row->available_stock }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-muted">Sin alertas de stock bajo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            if (typeof Chart === 'undefined') return;

            const revenue = @json($revenueChart);
            const appointments = @json($appointmentStatusChart);
            const orders = @json($orderStatusChart);

            const gridColor = 'rgba(32, 32, 32, 0.06)';
            const tickColor = '#737373';

            const revenueCtx = document.getElementById('dashboard-revenue-chart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: revenue.labels,
                        datasets: [{
                            label: 'Ingresos (S/)',
                            data: revenue.values,
                            backgroundColor: 'rgba(255, 102, 0, 0.85)',
                            hoverBackgroundColor: '#e65c00',
                            borderRadius: 6,
                            maxBarThickness: 44,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ' S/ ' + Number(ctx.raw ?? 0).toLocaleString('es-PE', { minimumFractionDigits: 2 }),
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: tickColor, font: { size: 11 } },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: {
                                    color: tickColor,
                                    font: { size: 11 },
                                    callback: (value) => 'S/ ' + Number(value).toLocaleString('es-PE'),
                                },
                            },
                        },
                    },
                });
            }

            const appointmentsCtx = document.getElementById('dashboard-appointments-chart');
            if (appointmentsCtx) {
                const hasData = (appointments.values || []).some((v) => Number(v) > 0);
                new Chart(appointmentsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: appointments.labels,
                        datasets: [{
                            data: hasData ? appointments.values : [1],
                            backgroundColor: hasData ? appointments.colors : ['#e6e6e6'],
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    padding: 14,
                                    color: tickColor,
                                    font: { size: 11 },
                                },
                            },
                            tooltip: { enabled: hasData },
                        },
                    },
                });
            }

            const ordersCtx = document.getElementById('dashboard-orders-chart');
            if (ordersCtx) {
                const hasData = (orders.values || []).some((v) => Number(v) > 0);
                new Chart(ordersCtx, {
                    type: 'doughnut',
                    data: {
                        labels: hasData ? orders.labels : ['Sin pedidos'],
                        datasets: [{
                            data: hasData ? orders.values : [1],
                            backgroundColor: hasData ? orders.colors : ['#e6e6e6'],
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    padding: 12,
                                    color: tickColor,
                                    font: { size: 11 },
                                },
                            },
                            tooltip: { enabled: hasData },
                        },
                    },
                });
            }
        })();
    </script>
@endpush

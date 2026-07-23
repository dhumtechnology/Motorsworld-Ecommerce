@extends('layouts.admin')

@section('title', 'Órdenes — Admin')
@section('page-title', 'Órdenes')
@section('page-subtitle', 'Consulta de pedidos de clientes')

@section('content')
    @php
        $statusLabels = [
            'created' => ['label' => 'Creada', 'class' => 'bg-neutral-800 text-neutral-300 border-neutral-700'],
            'paid' => ['label' => 'Pagada', 'class' => 'bg-sky-950 text-sky-400 border-sky-800'],
            'processing' => ['label' => 'En proceso', 'class' => 'bg-yellow-950 text-yellow-400 border-yellow-800'],
            'shipped' => ['label' => 'Enviada', 'class' => 'bg-indigo-950 text-indigo-300 border-indigo-800'],
            'delivered' => ['label' => 'Entregada', 'class' => 'bg-green-950 text-green-400 border-green-800'],
            'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-950 text-red-400 border-red-800'],
            'refunded' => ['label' => 'Reembolsada', 'class' => 'bg-orange-950 text-orange-400 border-orange-800'],
        ];

        $paymentLabels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            'partially_refunded' => 'Reembolso parcial',
        ];
    @endphp

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.orders.index') }}" id="admin-orders-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                        Buscar cliente
                    </label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Nombre, documento, email, teléfono o #orden..."
                        class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    >
                </div>

                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                        Estado
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    >
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ $statusLabels[$status->value]['label'] ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-neutral-500">Los filtros se aplican automáticamente</p>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.orders.index') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-800">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $orders->total() }}</span>
                {{ $orders->total() === 1 ? 'orden' : 'órdenes' }}
                @if ($hasActiveFilters)<span class="text-neutral-500">(filtradas)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold">Orden</th>
                        <th scope="col" class="px-5 py-3 font-bold">Cliente</th>
                        <th scope="col" class="px-5 py-3 font-bold">Total</th>
                        <th scope="col" class="px-5 py-3 font-bold">Ítems</th>
                        <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                        <th scope="col" class="px-5 py-3 font-bold">Pago</th>
                        <th scope="col" class="px-5 py-3 font-bold">Fecha</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($orders as $order)
                        @php
                            $profile = $order->user?->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            $statusKey = $order->status instanceof \App\Enums\Orders\OrderStatus
                                ? $order->status->value
                                : (string) $order->status;
                            $paymentKey = $order->payment_status instanceof \App\Enums\Orders\PaymentStatus
                                ? $order->payment_status->value
                                : (string) $order->payment_status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'];
                        @endphp
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3 font-mono font-semibold text-white">#{{ $order->id }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $fullName !== '' ? $fullName : 'Sin nombre' }}</p>
                                <p class="text-xs text-neutral-500 mt-0.5">{{ $order->user?->email ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-white font-semibold whitespace-nowrap">
                                {{ number_format((float) $order->total_amount, 2) }}
                                <span class="text-neutral-500 text-xs">{{ $order->currency }}</span>
                            </td>
                            <td class="px-5 py-3 text-neutral-300">{{ $order->items_count }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-neutral-300 text-xs uppercase tracking-wide">
                                {{ $paymentLabels[$paymentKey] ?? $paymentKey }}
                            </td>
                            <td class="px-5 py-3 text-neutral-400 whitespace-nowrap">
                                {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end">
                                    <a
                                        href="{{ route('admin.orders.show', $order) }}"
                                        class="inline-flex h-9 items-center gap-1.5 rounded border border-orange-800 bg-orange-950/40 px-3 text-xs font-bold uppercase tracking-wide text-orange-400 hover:bg-orange-900/50 transition-colors"
                                        title="Ver detalle"
                                        aria-label="Ver orden #{{ $order->id }}"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-neutral-500">No se encontraron órdenes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">
                {{ $orders->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>

    <script>
        (function () {
            const form = document.getElementById('admin-orders-filters');
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
        })();
    </script>
@endsection

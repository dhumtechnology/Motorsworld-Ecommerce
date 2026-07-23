@extends('layouts.admin')

@section('title', 'Pagos — Admin')
@section('page-title', 'Pagos')
@section('page-subtitle', 'Registros de pago de las órdenes')

@section('content')
    @php
        $statusLabels = [
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-yellow-950 text-yellow-400 border-yellow-800'],
            'paid' => ['label' => 'Pagado', 'class' => 'bg-green-950 text-green-400 border-green-800'],
            'failed' => ['label' => 'Fallido', 'class' => 'bg-red-950 text-red-400 border-red-800'],
            'expired' => ['label' => 'Expirado', 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'],
            'refunded' => ['label' => 'Reembolsado', 'class' => 'bg-orange-950 text-orange-400 border-orange-800'],
        ];
    @endphp

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.payments.index') }}" id="admin-payments-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Cliente, email, #orden, código..."
                           class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                </div>
                <div class="lg:col-span-3">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Estado</label>
                    <select id="status" name="status" class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ $statusLabels[$status->value]['label'] ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="method" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Método</label>
                    <select id="method" name="method" class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">Todos los métodos</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}" @selected(($filters['method'] ?? null) === $method->value)>
                                {{ $method->label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-neutral-500">Los filtros se aplican automáticamente</p>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.payments.index') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-800">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $payments->total() }}</span>
                {{ $payments->total() === 1 ? 'pago' : 'pagos' }}
                @if ($hasActiveFilters)<span class="text-neutral-500">(filtrados)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th class="px-5 py-3 font-bold">Pago</th>
                        <th class="px-5 py-3 font-bold">Orden</th>
                        <th class="px-5 py-3 font-bold">Cliente</th>
                        <th class="px-5 py-3 font-bold">Método</th>
                        <th class="px-5 py-3 font-bold">Monto</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold">Fecha</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($payments as $payment)
                        @php
                            $profile = $payment->order?->user?->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            $statusKey = $payment->status instanceof \App\Enums\Payments\PaymentRecordStatus
                                ? $payment->status->value
                                : (string) $payment->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'];
                            $methodKey = $payment->method instanceof \App\Enums\Payments\PaymentMethod
                                ? $payment->method->value
                                : (string) ($payment->method ?? '—');
                            $methodLabel = $payment->method instanceof \App\Enums\Payments\PaymentMethod
                                ? $payment->method->label()
                                : $methodKey;
                        @endphp
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3 font-mono font-semibold text-white">#{{ $payment->id }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.orders.show', $payment->order_id) }}" class="font-mono font-semibold text-sky-400 hover:text-sky-300">
                                    #{{ $payment->order_id }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $fullName !== '' ? $fullName : '—' }}</p>
                                <p class="text-xs text-neutral-500">{{ $payment->order?->user?->email ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-neutral-300">{{ $methodLabel }}</td>
                            <td class="px-5 py-3 font-semibold text-white whitespace-nowrap">
                                {{ number_format(((int) $payment->amount_cents) / 100, 2) }}
                                <span class="text-neutral-500 text-xs">{{ $payment->currency }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-neutral-400 whitespace-nowrap">
                                {{ $payment->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.payments.show', $payment) }}"
                                   class="inline-flex items-center rounded border border-sky-800 bg-sky-950/50 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-sky-400 hover:bg-sky-900/60 transition-colors">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-neutral-500">No se encontraron pagos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">
                {{ $payments->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-payments-filters',
        'entityLabelSingular' => 'pago',
        'entityLabelPlural' => 'pagos',
    ])
@endsection

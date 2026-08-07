@extends('layouts.admin')

@section('title', 'Clientes — Admin')
@section('page-title', 'Clientes')
@section('page-subtitle', 'Clientes de la tienda')

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];
    @endphp

    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.customers.index') }}" id="admin-customers-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Buscar
                    </label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Nombre, documento, email o teléfono..."
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>

                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Estado
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                <p id="filters-live-hint" class="text-xs text-muted">
                    Los filtros se aplican automáticamente
                </p>
                @if ($hasActiveFilters)
                    <a
                        href="{{ route('admin.customers.index') }}"
                        class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $customers->total() }}</span>
                {{ $customers->total() === 1 ? 'cliente' : 'clientes' }}
                @if ($hasActiveFilters)
                    <span class="text-muted">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold">Cliente</th>
                        <th scope="col" class="px-5 py-3 font-bold">Documento</th>
                        <th scope="col" class="px-5 py-3 font-bold">Contacto</th>
                        <th scope="col" class="px-5 py-3 font-bold">Órdenes</th>
                        <th scope="col" class="px-5 py-3 font-bold">Reservas</th>
                        <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                        <th scope="col" class="px-5 py-3 font-bold">Registro</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($customers as $customer)
                        @php
                            $profile = $customer->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            $statusKey = $customer->status instanceof \App\Enums\Auth\UserStatus
                                ? $customer->status->value
                                : (string) $customer->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                        @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-text hover:text-primary hover:underline">
                                    {{ $fullName !== '' ? $fullName : 'Sin nombre' }}
                                </a>
                                <p class="text-xs text-muted mt-0.5">{{ $customer->email }}</p>
                            </td>
                            <td class="px-5 py-3 font-mono text-text-soft">
                                {{ $profile?->document ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                {{ $profile?->phone ?: '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">
                                    {{ $customer->orders_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">
                                    {{ $customer->appointments_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted whitespace-nowrap">
                                {{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.customers.show', $customer) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        title="Ver detalle"
                                        aria-label="Ver {{ $fullName !== '' ? $fullName : $customer->email }}"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </a>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors"
                                        title="Eliminar"
                                        aria-label="Eliminar {{ $fullName !== '' ? $fullName : $customer->email }}"
                                        data-open-confirm="single-delete-modal"
                                        data-delete-url="{{ route('admin.customers.destroy', $customer) }}"
                                        data-delete-message="¿Eliminar al cliente «{{ $fullName !== '' ? $fullName : $customer->email }}»? Se ocultará del listado (soft delete). Sus pedidos y reservas se conservan."
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-muted">
                                No se encontraron clientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $customers->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    <x-confirm-modal
        id="single-delete-modal"
        title="Eliminar cliente"
        message="¿Seguro que deseas eliminar este cliente?"
        confirm-label="Eliminar"
        method="DELETE"
        :action="route('admin.customers.index')"
    />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-customers-filters',
        'entityLabelSingular' => 'cliente',
        'entityLabelPlural' => 'clientes',
    ])
@endsection

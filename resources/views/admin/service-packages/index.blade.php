@extends('layouts.admin')

@section('title', 'Paquetes de servicio — Admin')
@section('page-title', 'Paquetes de servicio')
@section('page-subtitle', 'Paquetes asociados a cada tipo de servicio')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.service-packages.index') }}" id="admin-service-packages-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Nombre o descripción..."
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div class="lg:col-span-4">
                    <label for="service_type_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Servicio</label>
                    <select id="service_type_id" name="service_type_id" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        @foreach ($serviceTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) ($filters['service_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="is_active" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                    <select id="is_active" name="is_active" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        <option value="1" @selected(($filters['is_active'] ?? null) === '1' || ($filters['is_active'] ?? null) === 1)>Activos</option>
                        <option value="0" @selected(($filters['is_active'] ?? null) === '0' || ($filters['is_active'] ?? null) === 0)>Inactivos</option>
                    </select>
                </div>
            </div>
            @if ($hasActiveFilters)
                    <a href="{{ route('admin.service-packages.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
                @endif
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <button type="button" id="bulk-delete-btn" disabled data-open-confirm="bulk-delete-modal"
                class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">
            Eliminar seleccionados
            <span id="bulk-delete-count" class="hidden">(0)</span>
        </button>
        <a href="{{ route('admin.service-packages.create') }}"
           class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Agregar paquete
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $packages->total() }}</span>
                {{ $packages->total() === 1 ? 'paquete' : 'paquetes' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtrados)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary" @disabled($packages->isEmpty())>
                        </th>
                        <th class="px-5 py-3 font-bold">Paquete</th>
                        <th class="px-5 py-3 font-bold">Servicio</th>
                        <th class="px-5 py-3 font-bold">Precio</th>
                        <th class="px-5 py-3 font-bold">Duración</th>
                        <th class="px-5 py-3 font-bold">Reservas</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($packages as $package)
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $package->id }}" data-row-checkbox class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary">
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $package->name }}</p>
                                @if ($package->description)
                                    <p class="text-xs text-muted mt-0.5 line-clamp-1">{{ $package->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft">{{ $package->serviceType?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-text-soft">
                                @if ($package->price !== null)
                                    {{ $package->currency }} {{ number_format((float) $package->price, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft">{{ $package->duration_minutes }} min</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">{{ $package->appointments_count }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($package->is_active)
                                    <span class="inline-flex items-center rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-bold uppercase text-emerald-700">Activo</span>
                                @else
                                    <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold uppercase text-muted">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.service-packages.edit', $package) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors" title="Editar">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                    </a>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors" title="Eliminar"
                                            data-open-confirm="single-delete-modal"
                                            data-delete-url="{{ route('admin.service-packages.destroy', $package) }}"
                                            data-delete-message="¿Eliminar el paquete «{{ $package->name }}»?">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-muted">No se encontraron paquetes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($packages->hasPages())
            <div class="px-5 py-4 border-t border-border">{{ $packages->links('vendor.pagination.admin') }}</div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar paquete" message="¿Seguro que deseas eliminar este paquete?" confirm-label="Eliminar" method="DELETE" :action="route('admin.service-packages.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar paquetes" message="¿Eliminar los paquetes seleccionados?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.service-packages.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-service-packages-filters',
        'entityLabelSingular' => 'paquete',
        'entityLabelPlural' => 'paquetes',
    ])
@endsection

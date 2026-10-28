@extends('layouts.admin')

@section('title', 'Cupones — Admin')
@section('page-title', 'Cupones')
@section('page-subtitle', 'Descuentos para el checkout')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.coupons.index') }}" id="admin-coupons-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Código o título..."
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div class="lg:col-span-3">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                    <select id="status" name="status" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? null) === 'active')>Vigentes</option>
                        <option value="scheduled" @selected(($filters['status'] ?? null) === 'scheduled')>Programados</option>
                        <option value="expired" @selected(($filters['status'] ?? null) === 'expired')>Expirados</option>
                        <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactivos</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="audience" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Audiencia</label>
                    <select id="audience" name="audience" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        <option value="public" @selected(($filters['audience'] ?? null) === 'public')>Públicos</option>
                        <option value="assigned" @selected(($filters['audience'] ?? null) === 'assigned')>Clientes asignados</option>
                    </select>
                </div>
            </div>
            @if ($hasActiveFilters)
                <a href="{{ route('admin.coupons.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <button type="button" id="bulk-delete-btn" disabled data-open-confirm="bulk-delete-modal"
                class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">
            Eliminar seleccionados
            <span id="bulk-delete-count" class="hidden">(0)</span>
        </button>
        <a href="{{ route('admin.coupons.create') }}"
           class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Agregar cupón
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $coupons->total() }}</span>
                {{ $coupons->total() === 1 ? 'cupón' : 'cupones' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtrados)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary" @disabled($coupons->isEmpty())>
                        </th>
                        <th class="px-5 py-3 font-bold">Código</th>
                        <th class="px-5 py-3 font-bold">Descuento</th>
                        <th class="px-5 py-3 font-bold">Audiencia</th>
                        <th class="px-5 py-3 font-bold">Vigencia</th>
                        <th class="px-5 py-3 font-bold">Usos</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($coupons as $coupon)
                        @php $meta = $coupon->lifecycleMeta(); @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $coupon->id }}" data-row-checkbox class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary">
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.coupons.show', $coupon) }}" class="font-mono font-bold text-sky-700 hover:underline">{{ $coupon->code }}</a>
                                @if ($coupon->hasTitle())
                                    <p class="text-xs text-muted mt-0.5">{{ $coupon->name }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-semibold text-text whitespace-nowrap">
                                {{ $coupon->discountLabel() }}
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                @if ($coupon->customers_count > 0)
                                    {{ $coupon->customers_count }} {{ $coupon->customers_count === 1 ? 'cliente' : 'clientes' }}
                                @else
                                    Todos
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft whitespace-nowrap text-xs">
                                <span class="block">{{ $coupon->starts_at?->format('d/m/Y H:i') }}</span>
                                <span class="block text-muted">→ {{ $coupon->ends_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3 text-text-soft">{{ $coupon->usages_count }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.coupons.show', $coupon) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-border bg-surface text-muted hover:text-text hover:border-border-strong transition-colors" title="Ver">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" /><circle cx="12" cy="12" r="3" /></svg>
                                    </a>
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors" title="Editar">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                    </a>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors" title="Eliminar"
                                            data-open-confirm="single-delete-modal"
                                            data-delete-url="{{ route('admin.coupons.destroy', $coupon) }}"
                                            data-delete-message="¿Eliminar el cupón «{{ $coupon->code }}»?">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-muted">No se encontraron cupones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($coupons->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $coupons->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar cupón" message="¿Seguro que deseas eliminar este cupón?" confirm-label="Eliminar" method="DELETE" :action="route('admin.coupons.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar cupones" message="¿Eliminar los cupones seleccionados?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.coupons.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-coupons-filters',
        'entityLabelSingular' => 'cupón',
        'entityLabelPlural' => 'cupones',
    ])
@endsection

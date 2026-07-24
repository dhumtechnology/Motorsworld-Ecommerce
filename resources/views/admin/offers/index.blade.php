@extends('layouts.admin')

@section('title', 'Ofertas — Admin')
@section('page-title', 'Ofertas')
@section('page-subtitle', 'Promociones de productos del catálogo')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.offers.index') }}" id="admin-offers-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="SKU o nombre de producto..."
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                    <select id="status" name="status" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? null) === 'active')>Vigentes</option>
                        <option value="scheduled" @selected(($filters['status'] ?? null) === 'scheduled')>Programadas</option>
                        <option value="expired" @selected(($filters['status'] ?? null) === 'expired')>Expiradas</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-muted">Los filtros se aplican automáticamente</p>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.offers.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <button type="button" id="bulk-delete-btn" disabled data-open-confirm="bulk-delete-modal"
                class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">
            Eliminar seleccionados
            <span id="bulk-delete-count" class="hidden">(0)</span>
        </button>
        <a href="{{ route('admin.offers.create') }}"
           class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Agregar oferta
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $offers->total() }}</span>
                {{ $offers->total() === 1 ? 'oferta' : 'ofertas' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtradas)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary" @disabled($offers->isEmpty())>
                        </th>
                        <th class="px-5 py-3 font-bold">Producto</th>
                        <th class="px-5 py-3 font-bold">Descuento</th>
                        <th class="px-5 py-3 font-bold">Precio oferta</th>
                        <th class="px-5 py-3 font-bold">Motivo</th>
                        <th class="px-5 py-3 font-bold">Vigencia</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($offers as $offer)
                        @php $meta = $offer->lifecycleMeta(); @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $offer->id }}" data-row-checkbox class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary">
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $offer->product?->name ?? '—' }}</p>
                                <a href="{{ route('admin.products.show', $offer->product_id) }}" class="text-xs font-mono text-sky-700 hover:text-sky-800">
                                    {{ $offer->product?->sku }}
                                </a>
                            </td>
                            <td class="px-5 py-3 font-semibold text-text whitespace-nowrap">
                                {{ number_format($offer->resolvedDiscountPercent((float) ($offer->product?->price_amount ?? 0)), 2) }}%
                            </td>
                            <td class="px-5 py-3 font-semibold text-primary whitespace-nowrap">
                                {{ number_format((float) $offer->offer_price_amount, 2) }}
                                <span class="text-xs text-muted">PEN</span>
                                @if ($offer->product)
                                    <span class="block text-xs text-muted line-through font-normal">
                                        {{ number_format((float) $offer->product->price_amount, 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft max-w-[14rem]">
                                <span class="line-clamp-2" title="{{ $offer->reason }}">{{ $offer->reason ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-text-soft whitespace-nowrap text-xs">
                                <span class="block">{{ $offer->starts_at?->format('d/m/Y H:i') }}</span>
                                <span class="block text-muted">→ {{ $offer->ends_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.offers.edit', $offer) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors" title="Editar">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                    </a>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors" title="Eliminar"
                                            data-open-confirm="single-delete-modal"
                                            data-delete-url="{{ route('admin.offers.destroy', $offer) }}"
                                            data-delete-message="¿Eliminar la oferta #{{ $offer->id }}?">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-muted">No se encontraron ofertas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($offers->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $offers->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar oferta" message="¿Seguro que deseas eliminar esta oferta?" confirm-label="Eliminar" method="DELETE" :action="route('admin.offers.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar ofertas" message="¿Eliminar las ofertas seleccionadas?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.offers.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-offers-filters',
        'entityLabelSingular' => 'oferta',
        'entityLabelPlural' => 'ofertas',
    ])
@endsection

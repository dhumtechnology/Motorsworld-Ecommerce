@extends('layouts.admin')

@section('title', 'Configuración — Admin')
@section('page-title', 'Configuración')
@section('page-subtitle', 'Banners del home')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.home-banners.index') }}" id="admin-home-banners-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Título del banner..."
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                    <select id="status" name="status" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? null) === 'active')>Vigentes</option>
                        <option value="scheduled" @selected(($filters['status'] ?? null) === 'scheduled')>Programados</option>
                        <option value="expired" @selected(($filters['status'] ?? null) === 'expired')>Expirados</option>
                        <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactivos</option>
                    </select>
                </div>
            </div>
            @if ($hasActiveFilters)
                <a href="{{ route('admin.home-banners.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <button type="button" id="bulk-delete-btn" disabled data-open-confirm="bulk-delete-modal"
                class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">
            Eliminar seleccionados
            <span id="bulk-delete-count" class="hidden">(0)</span>
        </button>
        <a href="{{ route('admin.home-banners.create') }}"
           class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Agregar banner
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $banners->total() }}</span>
                {{ $banners->total() === 1 ? 'banner' : 'banners' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtrados)</span>@endif
            </p>
            <p class="mt-1 text-xs text-muted">
                Si no hay banners vigentes, el home mostrará las imágenes por defecto.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary" @disabled($banners->isEmpty())>
                        </th>
                        <th class="px-5 py-3 font-bold">Banner</th>
                        <th class="px-5 py-3 font-bold">Vigencia</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($banners as $banner)
                        @php $meta = $banner->lifecycleMeta(); @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $banner->id }}" data-row-checkbox class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary">
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img src="{{ $banner->image }}" alt="" class="h-12 w-24 rounded object-cover border border-border shrink-0">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-text truncate">{{ $banner->title ?: 'Sin título' }}</p>
                                        <p class="text-xs text-muted mt-0.5">Orden: {{ $banner->sort_order }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-text-soft whitespace-nowrap">
                                <p>{{ $banner->starts_at->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-muted">{{ $banner->ends_at?->format('d/m/Y H:i') ?? 'Indeterminado' }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.home-banners.edit', $banner) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors" title="Editar">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                    </a>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors" title="Eliminar"
                                            data-open-confirm="single-delete-modal"
                                            data-delete-url="{{ route('admin.home-banners.destroy', $banner) }}"
                                            data-delete-message="¿Eliminar este banner del home?">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-muted">No hay banners configurados. Se usarán las imágenes por defecto del home.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($banners->hasPages())
            <div class="px-5 py-4 border-t border-border">{{ $banners->links('vendor.pagination.admin') }}</div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar banner" message="¿Seguro que deseas eliminar este banner?" confirm-label="Eliminar" method="DELETE" :action="route('admin.home-banners.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar banners" message="¿Eliminar los banners seleccionados?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.home-banners.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-home-banners-filters',
        'entityLabelSingular' => 'banner',
        'entityLabelPlural' => 'banners',
    ])
@endsection

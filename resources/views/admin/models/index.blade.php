@extends('layouts.admin')

@section('title', 'Modelos — Admin')
@section('page-title', 'Modelos')
@section('page-subtitle', 'Modelos vinculados a marcas')

@section('content')
    @php
        $selectedBrands = $filters['brands'] ?? [];
        $deleteImpactMessage = function (string $name, int $productsCount): string {
            $message = "¿Eliminar el modelo «{$name}»?";

            if ($productsCount > 0) {
                $label = $productsCount === 1 ? '1 producto asociado' : "{$productsCount} productos asociados";
                $message .= " También se eliminarán {$label}.";
            }

            return $message.' Esta acción no se puede deshacer.';
        };
    @endphp

    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.models.index') }}" id="admin-models-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Modelo o marca..."
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <x-multi-select
                        name="brands"
                        label="Marcas"
                        placeholder="Todas las marcas"
                        :options="$brands"
                        :selected="$selectedBrands"
                    />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-muted">Los filtros se aplican automáticamente</p>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.models.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
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
        <a href="{{ route('admin.models.create') }}"
           class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Agregar modelo
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $models->total() }}</span>
                {{ $models->total() === 1 ? 'modelo' : 'modelos' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtrados)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary" @disabled($models->isEmpty())>
                        </th>
                        <th scope="col" class="px-5 py-3 font-bold">Modelo</th>
                        <th scope="col" class="px-5 py-3 font-bold">Marca</th>
                        <th scope="col" class="px-5 py-3 font-bold">Productos</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($models as $model)
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input
                                    type="checkbox"
                                    value="{{ $model->id }}"
                                    data-row-checkbox
                                    data-products-count="{{ $model->products_count }}"
                                    class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                                >
                            </td>
                            <td class="px-5 py-3 font-semibold text-text">{{ $model->name }}</td>
                            <td class="px-5 py-3 text-text-soft">{{ $model->brand?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">{{ $model->products_count }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.models.edit', $model) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors" title="Editar" aria-label="Editar {{ $model->name }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                    </a>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors" title="Eliminar" aria-label="Eliminar {{ $model->name }}"
                                            data-open-confirm="single-delete-modal"
                                            data-delete-url="{{ route('admin.models.destroy', $model) }}"
                                            data-delete-message="{{ $deleteImpactMessage($model->name, (int) $model->products_count) }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-muted">No se encontraron modelos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($models->hasPages())
            <div class="px-5 py-4 border-t border-border">{{ $models->links('vendor.pagination.admin') }}</div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar modelo" message="¿Seguro que deseas eliminar este modelo?" confirm-label="Eliminar" method="DELETE" :action="route('admin.models.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar modelos" message="¿Eliminar los modelos seleccionados?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.models.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-models-filters',
        'entityLabelSingular' => 'modelo',
        'entityLabelPlural' => 'modelos',
        'relatedCountFields' => [
            ['attr' => 'productsCount', 'singular' => 'producto', 'plural' => 'productos'],
        ],
    ])
@endsection

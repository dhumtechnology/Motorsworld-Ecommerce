@extends('layouts.admin')

@section('title', 'Marcas — Admin')
@section('page-title', 'Marcas')
@section('page-subtitle', 'Marcas del catálogo')

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.brands.index') }}" id="admin-brands-filters" class="space-y-4">
            <div class="max-w-md">
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Buscar</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Nombre de marca..."
                    class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                >
            </div>
            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-neutral-500">La búsqueda se aplica automáticamente</p>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.brands.index') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <button type="button" id="bulk-delete-btn" disabled data-open-confirm="bulk-delete-modal"
                class="rounded border border-red-800 bg-red-950/40 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-400 transition-colors enabled:hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-40">
            Eliminar seleccionados
            <span id="bulk-delete-count" class="hidden">(0)</span>
        </button>
        <a href="{{ route('admin.brands.create') }}"
           class="inline-flex items-center gap-2 rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Agregar marca
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-800">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $brands->total() }}</span>
                {{ $brands->total() === 1 ? 'marca' : 'marcas' }}
                @if ($hasActiveFilters)<span class="text-neutral-500">(filtradas)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-neutral-600 bg-[#1e1e1e] text-orange-600 focus:ring-orange-500" @disabled($brands->isEmpty())>
                        </th>
                        <th scope="col" class="px-5 py-3 font-bold w-16">Img</th>
                        <th scope="col" class="px-5 py-3 font-bold">Nombre</th>
                        <th scope="col" class="px-5 py-3 font-bold">Modelos</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($brands as $brand)
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $brand->id }}" data-row-checkbox class="h-4 w-4 rounded border-neutral-600 bg-[#1e1e1e] text-orange-600 focus:ring-orange-500">
                            </td>
                            <td class="px-5 py-3">
                                @if ($brand->image)
                                    <img src="{{ $brand->image }}" alt="" class="h-10 w-10 rounded object-cover border border-neutral-700 bg-[#252525]">
                                @else
                                    <div class="h-10 w-10 rounded border border-neutral-700 bg-[#252525] flex items-center justify-center text-neutral-600 text-xs">—</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-semibold text-white">{{ $brand->name }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-neutral-700 bg-[#252525] px-2 py-0.5 text-xs font-bold text-neutral-300">{{ $brand->vehicle_models_count }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.brands.edit', $brand) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-800 bg-sky-950/50 text-sky-400 hover:bg-sky-900/60 transition-colors" title="Editar" aria-label="Editar {{ $brand->name }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                    </a>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-800 bg-red-950/50 text-red-400 hover:bg-red-900/60 transition-colors" title="Eliminar" aria-label="Eliminar {{ $brand->name }}"
                                            data-open-confirm="single-delete-modal"
                                            data-delete-url="{{ route('admin.brands.destroy', $brand) }}"
                                            data-delete-message="¿Eliminar la marca «{{ $brand->name }}»? Esta acción no se puede deshacer.">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-500">No se encontraron marcas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($brands->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">{{ $brands->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar marca" message="¿Seguro que deseas eliminar esta marca?" confirm-label="Eliminar" method="DELETE" :action="route('admin.brands.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar marcas" message="¿Eliminar las marcas seleccionadas?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.brands.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-brands-filters',
        'entityLabelSingular' => 'marca',
        'entityLabelPlural' => 'marcas',
    ])
@endsection

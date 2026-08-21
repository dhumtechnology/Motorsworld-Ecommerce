@extends('layouts.admin')

@section('title', 'Papelera de productos — Admin')
@section('page-title', 'Papelera de productos')
@section('page-subtitle', 'Productos archivados que ya no aparecen en la tienda')

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];

        $selectedCategories = $filters['categories'] ?? [];
        $selectedBrands = $filters['brands'] ?? [];
        $selectedModels = $filters['models'] ?? [];
    @endphp

    <div class="mb-4">
        <a
            href="{{ route('admin.products.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors"
        >
            ← Volver a productos
        </a>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-6">
        Los productos archivados conservan su historial de pedidos. Puedes restaurarlos para volver a publicarlos
        o eliminarlos permanentemente solo si no tienen pedidos asociados.
    </div>

    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.products.trash') }}" id="admin-products-trash-filters" class="space-y-5">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-3">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Buscar
                    </label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="SKU, nombre, marca o categoría..."
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>

                <div class="lg:col-span-3">
                    <x-multi-select
                        name="categories"
                        label="Categorías"
                        placeholder="Todas las categorías"
                        :options="$categories"
                        :selected="$selectedCategories"
                    />
                </div>

                <div class="lg:col-span-2">
                    <x-multi-select
                        name="brands"
                        label="Marcas"
                        placeholder="Todas las marcas"
                        :options="$brands"
                        :selected="$selectedBrands"
                    />
                </div>

                <div class="lg:col-span-2">
                    <x-multi-select
                        name="models"
                        label="Modelos"
                        placeholder="Selecciona una marca"
                        :options="$models"
                        :selected="$selectedModels"
                        depends-on="brands"
                    />
                </div>

                <div class="lg:col-span-2">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Estado
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Todos</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ $statusLabels[$status->value]['label'] ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($hasActiveFilters)
                <a
                    href="{{ route('admin.products.trash') }}"
                    class="inline-flex rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
                >
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <button
            type="button"
            id="bulk-restore-btn"
            disabled
            data-open-confirm="bulk-restore-modal"
            class="rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-emerald-700 transition-colors enabled:hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-40"
        >
            Restaurar seleccionados
            <span id="bulk-restore-count" class="hidden">(0)</span>
        </button>
        <button
            type="button"
            id="bulk-force-delete-btn"
            disabled
            data-open-confirm="bulk-force-delete-modal"
            class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40"
        >
            Eliminar permanentemente
            <span id="bulk-force-delete-count" class="hidden">(0)</span>
        </button>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden" data-products-trash-table>
        <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-4">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $products->total() }}</span>
                {{ $products->total() === 1 ? 'producto archivado' : 'productos archivados' }}
                @if ($hasActiveFilters)
                    <span class="text-muted">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold w-12">
                            <input
                                type="checkbox"
                                id="select-all-trashed-products"
                                class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                                title="Seleccionar todos"
                                @disabled($products->isEmpty())
                            >
                        </th>
                        <th scope="col" class="px-5 py-3 font-bold w-16">Img</th>
                        <th scope="col" class="px-5 py-3 font-bold">SKU</th>
                        <th scope="col" class="px-5 py-3 font-bold">Nombre</th>
                        <th scope="col" class="px-5 py-3 font-bold">Categoría</th>
                        <th scope="col" class="px-5 py-3 font-bold">Marca / Modelo</th>
                        <th scope="col" class="px-5 py-3 font-bold">Archivado</th>
                        <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($products as $product)
                        @php
                            $statusKey = $product->status instanceof \App\Enums\Products\ProductStatus
                                ? $product->status->value
                                : (string) $product->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                            $imageUrl = $product->catalogImageUrl();
                        @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input
                                    type="checkbox"
                                    name="product_ids[]"
                                    value="{{ $product->id }}"
                                    data-product-checkbox
                                    data-product-name="{{ $product->name }}"
                                    class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                                >
                            </td>
                            <td class="px-5 py-3">
                                @if ($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt=""
                                        class="h-10 w-10 rounded object-cover border border-border bg-secondary"
                                    >
                                @else
                                    <div class="h-10 w-10 rounded border border-border bg-secondary flex items-center justify-center text-muted text-xs">
                                        —
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-text-soft">{{ $product->sku }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $product->name }}</p>
                            </td>
                            <td class="px-5 py-3 text-text-soft">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-text-soft">
                                {{ $product->vehicleModel?->brand?->name ?? '—' }}
                                @if ($product->vehicleModel?->name)
                                    <span class="block text-xs text-muted">{{ $product->vehicleModel->name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft whitespace-nowrap">
                                {{ $product->deleted_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.products.restore', $product->id) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                            title="Restaurar"
                                            aria-label="Restaurar {{ $product->name }}"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1M3 10l4-4M3 10l4 4" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 14H11a5 5 0 01-5-5V8" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 14l-4-4M21 14l-4 4" />
                                            </svg>
                                        </button>
                                    </form>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors"
                                        title="Eliminar permanentemente"
                                        aria-label="Eliminar permanentemente {{ $product->name }}"
                                        data-open-confirm="single-force-delete-modal"
                                        data-delete-url="{{ route('admin.products.force-destroy', $product->id) }}"
                                        data-delete-message="¿Eliminar permanentemente «{{ $product->name }}»? Solo es posible si no tiene pedidos asociados."
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
                            <td colspan="9" class="px-5 py-12 text-center text-muted">
                                No hay productos archivados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $products->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    <x-confirm-modal
        id="single-force-delete-modal"
        title="Eliminar permanentemente"
        message="¿Eliminar permanentemente este producto?"
        confirm-label="Eliminar"
        method="DELETE"
        :action="route('admin.products.trash')"
    />

    <x-confirm-modal
        id="bulk-restore-modal"
        title="Restaurar productos"
        message="¿Restaurar los productos seleccionados?"
        confirm-label="Restaurar seleccionados"
        method="POST"
        :action="route('admin.products.bulk-restore')"
    />

    <x-confirm-modal
        id="bulk-force-delete-modal"
        title="Eliminar permanentemente"
        message="¿Eliminar permanentemente los productos seleccionados?"
        confirm-label="Eliminar permanentemente"
        method="DELETE"
        :action="route('admin.products.bulk-force-destroy')"
    />

    @include('admin.products._trash-scripts')
@endsection

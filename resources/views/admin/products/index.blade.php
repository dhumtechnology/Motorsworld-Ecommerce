@extends('layouts.admin')

@section('title', 'Productos — Admin')
@section('page-title', 'Productos')
@section('page-subtitle', 'Listado del catálogo con filtros')

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-green-950 text-green-400 border-green-800'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-yellow-950 text-yellow-400 border-yellow-800'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-950 text-red-400 border-red-800'],
        ];
    @endphp

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-col lg:flex-row gap-4 lg:items-end">
            <div class="flex-1">
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                    Buscar
                </label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="SKU, nombre o categoría..."
                    class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                >
            </div>

            <div class="w-full lg:w-64">
                <label for="category_id" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                    Categoría
                </label>
                <select
                    id="category_id"
                    name="category_id"
                    class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                >
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) === $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors"
                >
                    Filtrar
                </button>
                @if (($filters['search'] ?? null) || ($filters['category_id'] ?? null))
                    <a
                        href="{{ route('admin.products.index') }}"
                        class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-800 flex items-center justify-between gap-4">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $products->total() }}</span>
                {{ $products->total() === 1 ? 'producto' : 'productos' }}
                @if (($filters['search'] ?? null) || ($filters['category_id'] ?? null))
                    <span class="text-neutral-500">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold w-16">Img</th>
                        <th scope="col" class="px-5 py-3 font-bold">SKU</th>
                        <th scope="col" class="px-5 py-3 font-bold">Nombre</th>
                        <th scope="col" class="px-5 py-3 font-bold">Categoría</th>
                        <th scope="col" class="px-5 py-3 font-bold">Precio</th>
                        <th scope="col" class="px-5 py-3 font-bold">Stock</th>
                        <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($products as $product)
                        @php
                            $statusKey = $product->status instanceof \App\Enums\Products\ProductStatus
                                ? $product->status->value
                                : (string) $product->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'];
                            $imageUrl = $product->catalogImageUrl();
                        @endphp
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3">
                                @if ($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt=""
                                        class="h-10 w-10 rounded object-cover border border-neutral-700 bg-[#252525]"
                                    >
                                @else
                                    <div class="h-10 w-10 rounded border border-neutral-700 bg-[#252525] flex items-center justify-center text-neutral-600 text-xs">
                                        —
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-neutral-300">{{ $product->sku }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $product->name }}</p>
                                <a
                                    href="{{ route('shop.product.show', $product) }}"
                                    target="_blank"
                                    class="text-xs text-orange-500 hover:text-orange-400"
                                >
                                    Ver en tienda ↗
                                </a>
                            </td>
                            <td class="px-5 py-3 text-neutral-300">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-white font-semibold whitespace-nowrap">
                                {{ number_format((float) $product->price_amount, 2) }}
                                <span class="text-neutral-500 text-xs">{{ $product->currency }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @php $stock = $product->inventory?->available_stock ?? 0; @endphp
                                <span class="{{ $stock > 0 ? 'text-green-400' : 'text-red-400' }} font-semibold">
                                    {{ $stock }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-neutral-500">
                                No se encontraron productos con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">
                {{ $products->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
@endsection

@php
    $statusLabels = [
        'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
        'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
    ];
@endphp

<div class="rounded-lg border border-border bg-surface overflow-hidden">
    <div class="px-5 py-4 border-b border-border flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-title text-text">{{ $title ?? 'Productos' }}</h2>
            <p class="text-xs text-muted mt-0.5">{{ $subtitle ?? 'Listado paginado' }}</p>
        </div>
        <p class="text-sm text-muted">
            <span class="text-text font-bold">{{ $products->total() }}</span>
            {{ $products->total() === 1 ? 'producto' : 'productos' }}
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                <tr>
                    <th scope="col" class="px-5 py-3 font-bold w-16">Img</th>
                    <th scope="col" class="px-5 py-3 font-bold">SKU</th>
                    <th scope="col" class="px-5 py-3 font-bold">Nombre</th>
                    @if ($showCategory ?? false)
                        <th scope="col" class="px-5 py-3 font-bold">Categoría</th>
                    @endif
                    @if ($showModel ?? false)
                        <th scope="col" class="px-5 py-3 font-bold">Modelo</th>
                    @endif
                    @if ($showBrandModel ?? false)
                        <th scope="col" class="px-5 py-3 font-bold">Marca / Modelo</th>
                    @endif
                    <th scope="col" class="px-5 py-3 font-bold">Precio</th>
                    <th scope="col" class="px-5 py-3 font-bold">Stock</th>
                    <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                    <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($products as $product)
                    @php
                        $imageUrl = $product->catalogImageUrl();
                        $statusKey = $product->status instanceof \App\Enums\Products\ProductStatus
                            ? $product->status->value
                            : (string) $product->status;
                        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                        $stock = $product->inventory?->available_stock ?? 0;
                    @endphp
                    <tr class="hover:bg-secondary/60 transition-colors">
                        <td class="px-5 py-3">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="" class="h-10 w-10 rounded object-cover border border-border bg-secondary">
                            @else
                                <div class="h-10 w-10 rounded border border-border bg-secondary flex items-center justify-center text-muted text-xs">—</div>
                            @endif
                        </td>
                        <td class="px-5 py-3 font-mono">
                            <a href="{{ route('admin.products.show', $product) }}" class="text-sky-700 hover:text-sky-800 hover:underline">
                                {{ $product->sku }}
                            </a>
                        </td>
                        <td class="px-5 py-3 font-semibold text-text">{{ $product->name }}</td>
                        @if ($showCategory ?? false)
                            <td class="px-5 py-3 text-text-soft">{{ $product->category?->name ?? '—' }}</td>
                        @endif
                        @if ($showModel ?? false)
                            <td class="px-5 py-3 text-text-soft">
                                @if ($product->vehicleModel)
                                    <a href="{{ route('admin.models.show', $product->vehicleModel) }}" class="hover:text-primary transition-colors">
                                        {{ $product->vehicleModel->name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        @if ($showBrandModel ?? false)
                            <td class="px-5 py-3 text-text-soft">
                                {{ $product->vehicleModel?->brand?->name ?? $product->vehicleModel?->name ?? '—' }}
                                @if ($product->vehicleModel?->brand && $product->vehicleModel?->name)
                                    <span class="block text-xs text-muted">{{ $product->vehicleModel->name }}</span>
                                @endif
                            </td>
                        @endif
                        <td class="px-5 py-3 text-text font-semibold whitespace-nowrap">
                            {{ number_format((float) $product->price_amount, 2) }}
                            <span class="text-muted text-xs">{{ $product->currency }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="{{ $stock > 0 ? 'text-emerald-700' : 'text-red-600' }} font-semibold">{{ $stock }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                {{ $statusMeta['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a
                                href="{{ route('admin.products.show', $product) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                title="Ver detalle"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 7 + ($showCategory ?? false ? 1 : 0) + ($showModel ?? false ? 1 : 0) + ($showBrandModel ?? false ? 1 : 0) }}" class="px-5 py-12 text-center text-muted">
                            No hay productos asociados.
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

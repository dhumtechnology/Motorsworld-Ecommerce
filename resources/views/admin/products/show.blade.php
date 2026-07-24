@extends('layouts.admin')

@section('title', $product->name.' — Admin')
@section('page-title', 'Detalle del producto')
@section('page-subtitle', $product->sku)

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];
        $statusKey = $product->status instanceof \App\Enums\Products\ProductStatus
            ? $product->status->value
            : (string) $product->status;
        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
        $primaryImage = $product->catalogImageUrl();
        $pricing = $product->currentPricing();
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a productos
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('shop.product.show', $product) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded border border-border px-4 py-2 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
            >
                Ver en tienda ↗
            </a>
            <a
                href="{{ route('admin.products.edit', $product) }}"
                class="inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors"
            >
                Editar
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Stock disponible</p>
            <p class="mt-2 text-2xl font-bold {{ $stats['available_stock'] > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ $stats['available_stock'] }}
            </p>
            <p class="mt-1 text-xs text-muted">Reservado: {{ $stats['reserved_stock'] }} · Total: {{ $stats['total_stock'] }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Unidades vendidas</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['units_sold']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $stats['orders_count'] }} {{ $stats['orders_count'] === 1 ? 'orden' : 'órdenes' }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Ingresos</p>
            <p class="mt-2 text-2xl font-bold text-text">
                {{ number_format($stats['revenue'], 2) }}
                <span class="text-sm font-semibold text-muted">{{ $product->currency }}</span>
            </p>
            <p class="mt-1 text-xs text-muted">Sin canceladas / reembolsadas</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Movimientos</p>
            <p class="mt-2 text-2xl font-bold text-text">
                <span class="text-emerald-700">+{{ $stats['entries_qty'] }}</span>
                <span class="text-muted text-lg mx-1">/</span>
                <span class="text-red-600">-{{ $stats['exits_qty'] }}</span>
            </p>
            <p class="mt-1 text-xs text-muted">
                Neto: {{ $stats['net_movement'] >= 0 ? '+' : '' }}{{ $stats['net_movement'] }}
                · {{ $stats['entries_count'] + $stats['exits_count'] }} registros
            </p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3 mb-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-lg border border-border bg-surface p-6">
                <div class="flex flex-wrap items-start gap-5">
                    @if ($primaryImage)
                        <img
                            src="{{ $primaryImage }}"
                            alt="{{ $product->name }}"
                            class="h-28 w-28 rounded-lg object-cover border border-border bg-secondary shrink-0"
                        >
                    @else
                        <div class="h-28 w-28 rounded-lg border border-border bg-secondary flex items-center justify-center text-muted text-sm shrink-0">
                            Sin imagen
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                {{ $statusMeta['label'] }}
                            </span>
                            <span class="font-mono text-xs text-muted">{{ $product->sku }}</span>
                        </div>
                        <h2 class="text-xl font-title text-text">{{ $product->name }}</h2>
                        <p class="mt-2 text-lg font-bold text-text">
                            @if ($pricing->hasOffer())
                                <span class="text-primary">{{ number_format((float) $pricing->unitPrice, 2) }}</span>
                                <span class="text-sm text-muted font-semibold">{{ $product->currency }}</span>
                                <span class="ml-2 text-sm text-muted line-through font-semibold">
                                    {{ number_format((float) $pricing->listUnitPrice, 2) }}
                                </span>
                            @else
                                {{ number_format((float) $product->price_amount, 2) }}
                                <span class="text-sm text-muted font-semibold">{{ $product->currency }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Categoría</dt>
                        <dd class="mt-1 text-text-soft">{{ $product->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Marca / Modelo</dt>
                        <dd class="mt-1 text-text-soft">
                            {{ $product->vehicleModel?->brand?->name ?? '—' }}
                            · {{ $product->vehicleModel?->name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Creado</dt>
                        <dd class="mt-1 text-text-soft">{{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Actualizado</dt>
                        <dd class="mt-1 text-text-soft">{{ $product->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($product->description)
                    <div class="mt-6">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</h3>
                        <div class="text-sm text-text-soft whitespace-pre-line">{{ $product->description }}</div>
                    </div>
                @endif

                @if ($product->additional_information)
                    <div class="mt-6">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Información adicional</h3>
                        <div class="text-sm text-text-soft whitespace-pre-line">{{ $product->additional_information }}</div>
                    </div>
                @endif

                @if ($product->images->isNotEmpty())
                    <div class="mt-6">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">
                            Imágenes ({{ $stats['images_count'] }})
                        </h3>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($product->images as $image)
                                <div class="relative">
                                    <img
                                        src="{{ $image->path }}"
                                        alt=""
                                        class="h-16 w-16 rounded object-cover border border-border bg-secondary"
                                    >
                                    @if ($image->is_primary)
                                        <span class="absolute -top-1 -right-1 rounded bg-primary px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">
                                            Principal
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-border bg-surface p-6">
                <h2 class="text-sm font-title text-text mb-1">Estadísticas</h2>
                <p class="text-xs text-muted mb-5">Resumen operativo del producto</p>

                <dl class="space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Valoraciones</dt>
                        <dd class="font-semibold text-text">
                            @if ($stats['reviews_count'] > 0)
                                {{ number_format($stats['avg_stars'], 1) }} ★
                                <span class="text-muted font-normal">({{ $stats['reviews_count'] }})</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">En carritos activos</dt>
                        <dd class="font-semibold text-text">{{ $stats['cart_count'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Líneas de pedido</dt>
                        <dd class="font-semibold text-text">{{ $stats['line_count'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Entradas registradas</dt>
                        <dd class="font-semibold text-emerald-700">{{ $stats['entries_count'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Salidas registradas</dt>
                        <dd class="font-semibold text-red-600">{{ $stats['exits_count'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Último movimiento</dt>
                        <dd class="font-semibold text-text">
                            {{ $stats['last_movement_at'] ? \Illuminate\Support\Carbon::parse($stats['last_movement_at'])->format('d/m/Y H:i') : '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Oferta activa</dt>
                        <dd class="font-semibold text-text">
                            {{ $product->hasActiveOffer() ? 'Sí' : 'No' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-title text-text">Movimientos de stock</h2>
                <p class="text-xs text-muted mt-0.5">Historial de entradas y salidas (kardex)</p>
            </div>
            <a
                href="{{ route('admin.inventory.create', ['product_id' => $product->id]) }}"
                class="inline-flex items-center gap-2 rounded border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold uppercase tracking-wide text-sky-700 hover:bg-sky-100 transition-colors"
            >
                Nuevo movimiento
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-secondary text-left text-xs uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-5 py-3 font-bold">Fecha</th>
                        <th class="px-5 py-3 font-bold">Tipo</th>
                        <th class="px-5 py-3 font-bold">Cantidad</th>
                        <th class="px-5 py-3 font-bold">Motivo</th>
                        <th class="px-5 py-3 font-bold">Orden</th>
                        <th class="px-5 py-3 font-bold">Registrado por</th>
                        <th class="px-5 py-3 font-bold">Notas</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($movements as $movement)
                        @php
                            $isEntry = $movement->type === \App\Enums\Inventory\InventoryMovementType::Entry;
                        @endphp
                        <tr class="hover:bg-secondary/40">
                            <td class="px-5 py-3 text-muted whitespace-nowrap">
                                {{ $movement->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3">
                                @if ($isEntry)
                                    <span class="inline-flex items-center rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-bold uppercase text-emerald-700">Entrada</span>
                                @else
                                    <span class="inline-flex items-center rounded border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-bold uppercase text-red-600">Salida</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-semibold {{ $isEntry ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ $isEntry ? '+' : '-' }}{{ $movement->quantity }}
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                {{ $movement->reason?->label() ?? '—' }}
                                @if ($movement->isSaleExit())
                                    <span class="block text-xs text-muted">Venta</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($movement->order_id)
                                    <a href="{{ route('admin.orders.show', $movement->order_id) }}" class="font-mono text-sky-700 hover:text-sky-800">
                                        #{{ $movement->order_id }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                {{ $movement->creator?->email ?? ($movement->isSaleExit() ? 'Sistema (venta)' : 'Sistema') }}
                            </td>
                            <td class="px-5 py-3 text-text-soft max-w-[12rem] truncate" title="{{ $movement->notes }}">
                                {{ $movement->notes ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a
                                    href="{{ route('admin.inventory.show', $movement) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors"
                                    title="Ver movimiento"
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
                            <td colspan="8" class="px-5 py-12 text-center text-muted">
                                Aún no hay movimientos de stock para este producto.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $movements->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>
@endsection

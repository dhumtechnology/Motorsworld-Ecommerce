@extends('layouts.admin')

@section('title', 'Movimiento #'.$movement->id.' — Admin')
@section('page-title', 'Movimiento #'.$movement->id)
@section('page-subtitle', 'Detalle de inventario')

@section('content')
    @php
        $isEntry = $movement->type === \App\Enums\Inventory\InventoryMovementType::Entry;
        $product = $movement->product;
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-400 hover:text-orange-400 transition-colors">
            ← Volver a inventario
        </a>
    </div>

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-2xl">
        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Tipo</dt>
                <dd class="mt-1">
                    @if ($isEntry)
                        <span class="inline-flex items-center rounded border border-green-800 bg-green-950 px-2 py-0.5 text-xs font-bold uppercase text-green-400">Entrada</span>
                    @else
                        <span class="inline-flex items-center rounded border border-red-800 bg-red-950 px-2 py-0.5 text-xs font-bold uppercase text-red-400">Salida</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Cantidad</dt>
                <dd class="mt-1 font-bold text-lg {{ $isEntry ? 'text-green-400' : 'text-red-400' }}">
                    {{ $isEntry ? '+' : '-' }}{{ $movement->quantity }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Producto</dt>
                <dd class="mt-1 font-semibold text-white">{{ $product?->name }}</dd>
                <dd class="text-xs font-mono text-neutral-500">{{ $product?->sku }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Motivo</dt>
                <dd class="mt-1 text-neutral-300">{{ $movement->reason?->label() }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Categoría</dt>
                <dd class="mt-1 text-neutral-300">{{ $product?->category?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Marca / Modelo</dt>
                <dd class="mt-1 text-neutral-300">
                    {{ $product?->vehicleModel?->brand?->name ?? '—' }}
                    · {{ $product?->vehicleModel?->name ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Fecha</dt>
                <dd class="mt-1 text-neutral-300">{{ $movement->created_at?->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Orden</dt>
                <dd class="mt-1">
                    @if ($movement->order_id)
                        <a href="{{ route('admin.orders.show', $movement->order_id) }}" class="font-mono font-semibold text-sky-400 hover:text-sky-300">#{{ $movement->order_id }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Registrado por</dt>
                <dd class="mt-1 text-neutral-300">{{ $movement->creator?->email ?? ($movement->isSaleExit() ? 'Sistema (venta)' : 'Sistema') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-neutral-500">Stock actual</dt>
                <dd class="mt-1 text-neutral-300">{{ (int) ($product?->inventory?->available_stock ?? 0) }}</dd>
            </div>
            @if ($movement->notes)
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wider text-neutral-500">Notas</dt>
                    <dd class="mt-1 text-neutral-300">{{ $movement->notes }}</dd>
                </div>
            @endif
        </dl>

        @if ($movement->isReversible())
            <div class="mt-6">
                <button type="button" data-open-confirm="single-delete-modal"
                        data-delete-url="{{ route('admin.inventory.destroy', $movement) }}"
                        data-delete-message="¿Revertir el movimiento #{{ $movement->id }}?"
                        class="rounded border border-red-800 bg-red-950/40 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-red-400 hover:bg-red-900/50 transition-colors">
                    Revertir movimiento
                </button>
            </div>
        @else
            <p class="mt-6 text-xs text-neutral-500">Las salidas por venta no se pueden revertir desde aquí.</p>
        @endif
    </div>

    @if ($movement->isReversible())
        <x-confirm-modal id="single-delete-modal" title="Revertir movimiento" message="¿Seguro?" confirm-label="Revertir" method="DELETE" :action="route('admin.inventory.destroy', $movement)" />
        @include('admin.partials.crud-list-scripts', [
            'entityLabelSingular' => 'movimiento',
            'entityLabelPlural' => 'movimientos',
        ])
    @endif
@endsection

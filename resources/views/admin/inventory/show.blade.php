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
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a inventario
        </a>
    </div>

    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Tipo</dt>
                <dd class="mt-1">
                    @if ($isEntry)
                        <span class="inline-flex items-center rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-bold uppercase text-emerald-700">Entrada</span>
                    @else
                        <span class="inline-flex items-center rounded border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-bold uppercase text-red-600">Salida</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Cantidad</dt>
                <dd class="mt-1 font-bold text-lg {{ $isEntry ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ $isEntry ? '+' : '-' }}{{ $movement->quantity }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Producto</dt>
                <dd class="mt-1 font-semibold text-text">{{ $product?->name }}</dd>
                <dd class="text-xs font-mono text-muted">{{ $product?->sku }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Motivo</dt>
                <dd class="mt-1 text-text-soft">{{ $movement->reason?->label() }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Categoría</dt>
                <dd class="mt-1 text-text-soft">{{ $product?->category?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Marca / Modelo</dt>
                <dd class="mt-1 text-text-soft">
                    {{ $product?->vehicleModel?->brand?->name ?? '—' }}
                    · {{ $product?->vehicleModel?->name ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Fecha</dt>
                <dd class="mt-1 text-text-soft">{{ $movement->created_at?->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Orden</dt>
                <dd class="mt-1">
                    @if ($movement->order_id)
                        <a href="{{ route('admin.orders.show', $movement->order_id) }}" class="font-mono font-semibold text-sky-700 hover:text-sky-800">#{{ $movement->order_id }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Registrado por</dt>
                <dd class="mt-1 text-text-soft">{{ $movement->creator?->email ?? ($movement->isSaleExit() ? 'Sistema (venta)' : 'Sistema') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Stock actual</dt>
                <dd class="mt-1 text-text-soft">{{ (int) ($product?->inventory?->available_stock ?? 0) }}</dd>
            </div>
            @if ($movement->notes)
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wider text-muted">Notas</dt>
                    <dd class="mt-1 text-text-soft">{{ $movement->notes }}</dd>
                </div>
            @endif
        </dl>

        @if ($movement->isReversible())
            <div class="mt-6">
                <button type="button" data-open-confirm="single-delete-modal"
                        data-delete-url="{{ route('admin.inventory.destroy', $movement) }}"
                        data-delete-message="¿Revertir el movimiento #{{ $movement->id }}?"
                        class="rounded border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-red-600 hover:bg-red-100 transition-colors">
                    Revertir movimiento
                </button>
            </div>
        @else
            <p class="mt-6 text-xs text-muted">Las salidas por venta no se pueden revertir desde aquí.</p>
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

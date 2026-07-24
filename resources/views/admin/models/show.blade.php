@extends('layouts.admin')

@section('title', $vehicleModel->name.' — Admin')
@section('page-title', 'Detalle de modelo')
@section('page-subtitle', '#'.$vehicleModel->id)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.models.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a modelos
        </a>
        <div class="flex flex-wrap items-center gap-2">
            @if ($vehicleModel->brand)
                <a
                    href="{{ route('admin.brands.show', $vehicleModel->brand) }}"
                    class="inline-flex items-center gap-2 rounded border border-border px-4 py-2 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
                >
                    Ver marca
                </a>
            @endif
            <a
                href="{{ route('admin.models.edit', $vehicleModel) }}"
                class="inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors"
            >
                Editar
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Productos</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['products_count']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $stats['active_count'] }} activos · {{ $stats['out_of_stock'] }} sin stock</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Stock disponible</p>
            <p class="mt-2 text-2xl font-bold {{ $stats['available_stock'] > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ number_format($stats['available_stock']) }}
            </p>
            <p class="mt-1 text-xs text-muted">Reservado: {{ number_format($stats['reserved_stock']) }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Unidades vendidas</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['units_sold']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $stats['orders_count'] }} {{ $stats['orders_count'] === 1 ? 'orden' : 'órdenes' }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Ingresos</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['revenue'], 2) }}</p>
            <p class="mt-1 text-xs text-muted">
                Precio prom.: {{ number_format($stats['avg_price'], 2) }}
                · {{ $stats['appointments_count'] }} reservas
            </p>
        </div>
    </div>

    <div class="rounded-lg border border-border bg-surface p-6 mb-6">
        <h2 class="text-sm font-title text-text mb-1">Información</h2>
        <p class="text-xs text-muted mb-5">Datos del modelo</p>
        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">ID</dt>
                <dd class="mt-1 font-mono font-semibold text-text">#{{ $vehicleModel->id }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Nombre</dt>
                <dd class="mt-1 font-semibold text-text">{{ $vehicleModel->name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Marca</dt>
                <dd class="mt-1">
                    @if ($vehicleModel->brand)
                        <a href="{{ route('admin.brands.show', $vehicleModel->brand) }}" class="font-semibold text-sky-700 hover:text-sky-800">
                            {{ $vehicleModel->brand->name }}
                        </a>
                    @else
                        <span class="text-text-soft">—</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Creado</dt>
                <dd class="mt-1 text-text-soft">{{ $vehicleModel->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Actualizado</dt>
                <dd class="mt-1 text-text-soft">{{ $vehicleModel->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    @include('admin.partials.related-products-table', [
        'products' => $products,
        'title' => 'Productos del modelo',
        'subtitle' => 'Pertenecen a «'.$vehicleModel->name.'»',
        'showCategory' => true,
    ])
@endsection

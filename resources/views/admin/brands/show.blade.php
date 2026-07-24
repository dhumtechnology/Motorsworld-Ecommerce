@extends('layouts.admin')

@section('title', $brand->name.' — Admin')
@section('page-title', 'Detalle de marca')
@section('page-subtitle', '#'.$brand->id)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.brands.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a marcas
        </a>
        <a
            href="{{ route('admin.brands.edit', $brand) }}"
            class="inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors"
        >
            Editar
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Modelos</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['models_count']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $stats['products_count'] }} productos</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Stock disponible</p>
            <p class="mt-2 text-2xl font-bold {{ $stats['available_stock'] > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ number_format($stats['available_stock']) }}
            </p>
            <p class="mt-1 text-xs text-muted">{{ $stats['active_count'] }} activos · {{ $stats['out_of_stock'] }} sin stock</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Unidades vendidas</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['units_sold']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $stats['orders_count'] }} {{ $stats['orders_count'] === 1 ? 'orden' : 'órdenes' }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Ingresos</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['revenue'], 2) }}</p>
            <p class="mt-1 text-xs text-muted">Sin canceladas / reembolsadas</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3 mb-6">
        <div class="xl:col-span-1 rounded-lg border border-border bg-surface p-6">
            <h2 class="text-sm font-title text-text mb-1">Información</h2>
            <p class="text-xs text-muted mb-5">Datos de la marca</p>

            <div class="flex items-start gap-4 mb-5">
                @if ($brand->image)
                    <img src="{{ $brand->image }}" alt="" class="h-16 w-16 rounded object-cover border border-border bg-secondary shrink-0">
                @else
                    <div class="h-16 w-16 rounded border border-border bg-secondary flex items-center justify-center text-muted text-xs shrink-0">Sin img</div>
                @endif
                <div>
                    <p class="font-mono text-xs text-muted">#{{ $brand->id }}</p>
                    <h3 class="text-lg font-title text-text">{{ $brand->name }}</h3>
                </div>
            </div>

            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Creada</dt>
                    <dd class="mt-1 text-text-soft">{{ $brand->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Actualizada</dt>
                    <dd class="mt-1 text-text-soft">{{ $brand->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="xl:col-span-2 rounded-lg border border-border bg-surface overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h2 class="text-sm font-title text-text">Modelos de la marca</h2>
                <p class="text-xs text-muted mt-0.5">Clic en un modelo para ver sus productos</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold">ID</th>
                            <th scope="col" class="px-5 py-3 font-bold">Modelo</th>
                            <th scope="col" class="px-5 py-3 font-bold">Productos</th>
                            <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($models as $model)
                            <tr class="hover:bg-secondary/60 transition-colors">
                                <td class="px-5 py-3 font-mono text-muted">
                                    <a href="{{ route('admin.models.show', $model) }}" class="text-sky-700 hover:text-sky-800 hover:underline">
                                        #{{ $model->id }}
                                    </a>
                                </td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.models.show', $model) }}" class="font-semibold text-text hover:text-primary transition-colors">
                                        {{ $model->name }}
                                    </a>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">
                                        {{ $model->products_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a
                                        href="{{ route('admin.models.show', $model) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        title="Ver productos del modelo"
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
                                <td colspan="4" class="px-5 py-10 text-center text-muted">Esta marca aún no tiene modelos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.partials.related-products-table', [
        'products' => $products,
        'title' => 'Productos de la marca',
        'subtitle' => 'Todos los productos vinculados a modelos de «'.$brand->name.'»',
        'showCategory' => true,
        'showModel' => true,
    ])
@endsection

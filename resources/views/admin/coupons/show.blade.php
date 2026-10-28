@extends('layouts.admin')

@section('title', $coupon->code.' — Cupón')
@section('page-title', 'Detalle de cupón')
@section('page-subtitle', $coupon->code)

@section('content')
    @php
        $meta = $coupon->lifecycleMeta();
        $payments = $coupon->allowedPaymentMethods();
        $cities = $coupon->allowedCities();
        $paymentLabels = [
            'card' => 'Tarjeta',
            'yape' => 'Yape',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a cupones
        </a>
        <a
            href="{{ route('admin.coupons.edit', $coupon) }}"
            class="inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors"
        >
            Editar
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Estado</p>
            <p class="mt-2">
                <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $meta['class'] }}">
                    {{ $meta['label'] }}
                </span>
            </p>
            <p class="mt-2 font-mono text-lg font-bold text-text">{{ $coupon->code }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Descuento</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ $coupon->discountLabel() }}</p>
            <p class="mt-1 text-xs text-muted">{{ $coupon->type->label() }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Audiencia</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ $coupon->isPublic() ? 'Todos' : $stats['customers_count'] }}</p>
            <p class="mt-1 text-xs text-muted">{{ $coupon->isPublic() ? 'Cupón público' : 'Clientes asignados' }}</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Usos</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['usages_count']) }}</p>
            <p class="mt-1 text-xs text-muted">Un uso por cliente</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3 mb-6">
        <div class="xl:col-span-1 rounded-lg border border-border bg-surface p-6">
            <h2 class="text-sm font-title text-text mb-1">Información</h2>
            <p class="text-xs text-muted mb-5">{{ $coupon->hasTitle() ? $coupon->name : 'Sin título' }}</p>
            <dl class="space-y-4 text-sm">
                @if (filled($coupon->reason))
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Motivo</dt>
                        <dd class="mt-1 text-text-soft">{{ $coupon->reason }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Vigencia</dt>
                    <dd class="mt-1 text-text-soft">
                        {{ $coupon->starts_at?->format('d/m/Y H:i') }} → {{ $coupon->ends_at?->format('d/m/Y H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Mínimo de productos</dt>
                    <dd class="mt-1 text-text-soft">{{ $coupon->min_products ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Mínimo de precio</dt>
                    <dd class="mt-1 text-text-soft">
                        @if ($coupon->min_subtotal)
                            {{ \App\Support\Currency::format((float) $coupon->min_subtotal, $coupon->min_subtotal_currency) }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Métodos de pago</dt>
                    <dd class="mt-1 text-text-soft">
                        @if ($payments === [])
                            Todos
                        @else
                            {{ collect($payments)->map(fn ($method) => $paymentLabels[$method] ?? $method)->implode(', ') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-muted">Zonas de entrega</dt>
                    <dd class="mt-1 text-text-soft">{{ $cities === [] ? 'Todas' : implode(', ', $cities) }}</dd>
                </div>
            </dl>
        </div>

        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-lg border border-border bg-surface p-6">
                <h2 class="text-sm font-title text-text mb-4">Alcance de catálogo</h2>
                <div class="grid gap-4 sm:grid-cols-3 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted mb-2">Productos</p>
                        @forelse ($coupon->products as $product)
                            <p class="text-text-soft">{{ $product->sku }} — {{ $product->name }}</p>
                        @empty
                            <p class="text-muted">Todos</p>
                        @endforelse
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted mb-2">Categorías</p>
                        @forelse ($coupon->categories as $category)
                            <p class="text-text-soft">{{ $category->name }}</p>
                        @empty
                            <p class="text-muted">Todas</p>
                        @endforelse
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted mb-2">Marcas</p>
                        @forelse ($coupon->brands as $brand)
                            <p class="text-text-soft">{{ $brand->name }}</p>
                        @empty
                            <p class="text-muted">Todas</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-border bg-surface overflow-hidden">
                <div class="px-5 py-4 border-b border-border">
                    <h2 class="text-sm font-title text-text">Clientes asignados</h2>
                    <p class="text-xs text-muted mt-1">{{ $coupon->isPublic() ? 'Sin asignación: válido para todos.' : $coupon->customers->count().' cliente(s)' }}</p>
                </div>
                @if (! $coupon->isPublic())
                    <ul class="divide-y divide-border text-sm">
                        @foreach ($coupon->customers as $customer)
                            @php
                                $profile = $customer->customerProfile;
                                $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            @endphp
                            <li class="px-5 py-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-text">{{ $fullName !== '' ? $fullName : $customer->email }}</p>
                                    <p class="text-xs text-muted">{{ $customer->email }}</p>
                                </div>
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-sky-700 hover:underline text-xs font-bold uppercase">Ver</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <h2 class="text-sm font-title text-text">Usos registrados</h2>
            <p class="text-xs text-muted mt-1">Últimos {{ $usages->count() }} usos</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold">Cliente</th>
                        <th class="px-5 py-3 font-bold">Pedido</th>
                        <th class="px-5 py-3 font-bold">Descuento</th>
                        <th class="px-5 py-3 font-bold">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($usages as $usage)
                        @php
                            $profile = $usage->user?->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                        @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $fullName !== '' ? $fullName : ($usage->user?->email ?? '—') }}</p>
                                <p class="text-xs text-muted">{{ $usage->user?->email }}</p>
                            </td>
                            <td class="px-5 py-3">
                                @if ($usage->order)
                                    <a href="{{ route('admin.orders.show', $usage->order) }}" class="font-mono text-sky-700 hover:underline">#{{ $usage->order->id }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3 font-semibold text-text">
                                {{ \App\Support\Currency::format((float) $usage->discount_amount, $usage->currency) }}
                            </td>
                            <td class="px-5 py-3 text-muted whitespace-nowrap">{{ $usage->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-muted">Todavía no se usó este cupón.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

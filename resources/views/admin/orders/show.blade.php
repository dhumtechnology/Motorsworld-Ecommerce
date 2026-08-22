@extends('layouts.admin')

@section('title', 'Orden #'.$order->id.' — Admin')
@section('page-title', 'Orden #'.$order->id)
@section('page-subtitle', 'Detalle de la orden')

@section('content')
    @php
        $profile = $order->user?->customerProfile;
        $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

        $statusLabels = [
            'created' => ['label' => 'Creada', 'class' => 'bg-secondary text-text-soft border-border'],
            'paid' => ['label' => 'Pagada', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'processing' => ['label' => 'En proceso', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'shipped' => ['label' => 'Enviada', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'delivered' => ['label' => 'Entregada', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-50 text-red-600 border-red-200'],
            'refunded' => ['label' => 'Reembolsada', 'class' => 'bg-primary-soft text-primary border-primary/30'],
        ];

        $paymentLabels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            'partially_refunded' => 'Reembolso parcial',
        ];

        $statusKey = $order->status instanceof \App\Enums\Orders\OrderStatus
            ? $order->status->value
            : (string) $order->status;
        $paymentKey = $order->payment_status instanceof \App\Enums\Orders\PaymentStatus
            ? $order->payment_status->value
            : (string) $order->payment_status;
        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a órdenes
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-lg border border-border bg-surface p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-sm font-title text-text">Información general</h2>
                        <p class="text-xs text-muted mt-1">Datos principales de la orden</p>
                    </div>
                    <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nº de orden</dt>
                        <dd class="mt-1 font-mono font-semibold text-text">#{{ $order->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Fecha</dt>
                        <dd class="mt-1 text-text-soft">{{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Estado</dt>
                        <dd class="mt-1 text-text font-semibold">{{ $statusMeta['label'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Estado de pago</dt>
                        <dd class="mt-1 text-text-soft">{{ $paymentLabels[$paymentKey] ?? $paymentKey }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Total</dt>
                        <dd class="mt-1 text-text font-bold text-lg">
                            {{ number_format((float) $order->total_amount, 2) }}
                            <span class="text-sm text-muted font-semibold">{{ $order->currency }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Entrega</dt>
                        <dd class="mt-1 text-text font-semibold">{{ $order->fulfillment_method?->label() ?? 'Delivery' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Ítems</dt>
                        <dd class="mt-1 text-text-soft">{{ $order->items->count() }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-border bg-surface p-6">
                <h2 class="text-sm font-title text-text mb-1">Cambiar estado</h2>
                <p class="text-xs text-muted mb-4">Actualiza el estado de la orden y deja una nota opcional.</p>

                @if ($errors->any())
                    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('admin.orders.update-status', $order) }}"
                    class="space-y-4"
                    x-data="{ submitting: false }"
                    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado *</label>
                        <select id="status" name="status" required
                                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            @foreach ($statuses as $status)
                                @php
                                    $optionKey = $status->value;
                                    $optionLabel = $statusLabels[$optionKey]['label'] ?? $optionKey;
                                @endphp
                                <option value="{{ $optionKey }}" @selected(old('status', $statusKey) === $optionKey)>
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="note" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nota (opcional)</label>
                        <textarea id="note" name="note" rows="3" maxlength="500"
                                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                  placeholder="Ej. Pedido preparado para envío">{{ old('note') }}</textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <svg
                            x-show="submitting"
                            x-cloak
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Guardando…' : 'Guardar estado'">Guardar estado</span>
                    </button>
                </form>
            </div>

            <div class="rounded-lg border border-border bg-surface overflow-hidden">
                <div class="px-5 py-4 border-b border-border">
                    <h2 class="text-sm font-title text-text">Productos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                            <tr>
                                <th class="px-5 py-3 font-bold">Producto</th>
                                <th class="px-5 py-3 font-bold">Cant.</th>
                                <th class="px-5 py-3 font-bold">P. unit.</th>
                                <th class="px-5 py-3 font-bold text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($order->items as $item)
                                <tr>
                                    <td class="px-5 py-3 text-text font-semibold">
                                        {{ $item->product?->name ?? 'Producto #'.$item->product_id }}
                                        @if ($item->product?->sku)
                                            <span class="block text-xs text-muted font-mono">{{ $item->product->sku }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-text-soft">{{ $item->quantity }}</td>
                                    <td class="px-5 py-3 text-text-soft whitespace-nowrap">
                                        {{ number_format((float) $item->unit_price, 2) }} {{ $item->currency }}
                                    </td>
                                    <td class="px-5 py-3 text-text font-semibold text-right whitespace-nowrap">
                                        {{ number_format((float) $item->unit_price * $item->quantity, 2) }} {{ $item->currency }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-muted">Sin productos en esta orden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($order->items->isNotEmpty())
                            <tfoot class="border-t border-border bg-secondary/50">
                                <tr>
                                    <td colspan="3" class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-muted">Total</td>
                                    <td class="px-5 py-3 text-right text-text font-bold whitespace-nowrap">
                                        {{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            @if ($order->statusHistory->isNotEmpty())
                <div class="rounded-lg border border-border bg-surface p-5">
                    <h2 class="text-sm font-title text-text mb-4">Historial de estados</h2>
                    <ul class="space-y-3">
                        @foreach ($order->statusHistory as $history)
                            @php
                                $historyLabel = $statusLabels[$history->status]['label'] ?? $history->status;
                            @endphp
                            <li class="border-b border-border pb-3 last:border-0 last:pb-0">
                                <p class="text-sm text-text font-semibold uppercase tracking-wide">{{ $historyLabel }}</p>
                                @if ($history->note)
                                    <p class="text-xs text-muted mt-1">{{ $history->note }}</p>
                                @endif
                                <p class="text-[11px] text-muted mt-1">
                                    {{ $history->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-title text-text mb-4">Cliente</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nombre</dt>
                        <dd class="text-text font-semibold mt-0.5">{{ $fullName !== '' ? $fullName : 'Sin nombre' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Email</dt>
                        <dd class="text-text-soft mt-0.5 break-all">{{ $order->user?->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Documento</dt>
                        <dd class="text-text-soft mt-0.5 font-mono">{{ $profile?->document ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Teléfono</dt>
                        <dd class="text-text-soft mt-0.5">{{ $profile?->phone ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-title text-text mb-4">Entrega</h2>
                <p class="text-sm font-semibold text-text">
                    {{ $order->fulfillment_method?->label() ?? 'Delivery' }}
                </p>
                @if ($order->fulfillment_method?->value === 'pickup')
                    <p class="mt-2 text-sm text-text-soft">El cliente retirará el pedido en tienda.</p>
                @endif
            </div>

            @if ($order->fulfillment_method?->value !== 'pickup' && $order->shippingAddress)
                <div class="rounded-lg border border-border bg-surface p-5">
                    <h2 class="text-sm font-title text-text mb-4">Dirección de envío</h2>
                    <p class="text-sm text-text-soft leading-relaxed">
                        {{ $order->shippingAddress->line1 }}<br>
                        {{ $order->shippingAddress->city }}
                        @if ($order->shippingAddress->postal_code)
                            · {{ $order->shippingAddress->postal_code }}
                        @endif
                        <br>{{ $order->shippingAddress->country }}
                    </p>
                </div>
            @elseif ($order->fulfillment_method?->value !== 'pickup')
                <div class="rounded-lg border border-border bg-surface p-5">
                    <h2 class="text-sm font-title text-text mb-4">Dirección de envío</h2>
                    <p class="text-sm text-muted">Sin dirección registrada.</p>
                </div>
            @endif

            @if ($order->billingAddress && $order->billing_address_id !== $order->shipping_address_id)
                <div class="rounded-lg border border-border bg-surface p-5">
                    <h2 class="text-sm font-title text-text mb-4">Dirección de facturación</h2>
                    <p class="text-sm text-text-soft leading-relaxed">
                        {{ $order->billingAddress->line1 }}<br>
                        {{ $order->billingAddress->city }}
                        @if ($order->billingAddress->postal_code)
                            · {{ $order->billingAddress->postal_code }}
                        @endif
                        <br>{{ $order->billingAddress->country }}
                    </p>
                </div>
            @endif

            @if ($order->payments->isNotEmpty())
                <div class="rounded-lg border border-border bg-surface p-5">
                    <h2 class="text-sm font-title text-text mb-4">Pagos</h2>
                    <ul class="space-y-3 text-sm">
                        @foreach ($order->payments as $payment)
                            @php
                                $methodLabel = $payment->method instanceof \App\Enums\Payments\PaymentMethod
                                    ? $payment->method->label()
                                    : (string) ($payment->method ?? '—');
                                $paymentStatusLabel = $payment->status instanceof \App\Enums\Payments\PaymentRecordStatus
                                    ? match ($payment->status->value) {
                                        'pending' => 'Pendiente',
                                        'paid' => 'Pagado',
                                        'failed' => 'Fallido',
                                        'expired' => 'Expirado',
                                        'refunded' => 'Reembolsado',
                                        default => $payment->status->value,
                                    }
                                    : (string) ($payment->status ?? '—');
                            @endphp
                            <li class="border-b border-border pb-3 last:border-0 last:pb-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-text font-semibold">
                                            {{ number_format(((int) $payment->amount_cents) / 100, 2) }}
                                            {{ $payment->currency ?? $order->currency }}
                                        </p>
                                        <p class="text-xs text-muted mt-1 uppercase tracking-wide">
                                            {{ $paymentStatusLabel }}
                                            · {{ $methodLabel }}
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-xs font-bold uppercase tracking-wide text-sky-700 hover:text-sky-800 shrink-0">
                                        Ver
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection

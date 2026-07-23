@extends('layouts.admin')

@section('title', 'Pago #'.$payment->id.' — Admin')
@section('page-title', 'Pago #'.$payment->id)
@section('page-subtitle', 'Detalle del registro de pago')

@section('content')
    @php
        $order = $payment->order;
        $profile = $order?->user?->customerProfile;
        $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));

        $statusLabels = [
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'paid' => ['label' => 'Pagado', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'failed' => ['label' => 'Fallido', 'class' => 'bg-red-50 text-red-600 border-red-200'],
            'expired' => ['label' => 'Expirado', 'class' => 'bg-secondary text-muted border-border'],
            'refunded' => ['label' => 'Reembolsado', 'class' => 'bg-primary-soft text-primary border-primary/30'],
        ];

        $statusKey = $payment->status instanceof \App\Enums\Payments\PaymentRecordStatus
            ? $payment->status->value
            : (string) $payment->status;
        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a pagos
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-lg border border-border bg-surface p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-sm font-title text-text">Información del pago</h2>
                        <p class="text-xs text-muted mt-1">Datos principales del cobro</p>
                    </div>
                    <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nº de pago</dt>
                        <dd class="mt-1 font-mono font-semibold text-text">#{{ $payment->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nº de orden</dt>
                        <dd class="mt-1">
                            @if ($order)
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-sky-700 hover:text-sky-800">#{{ $order->id }}</a>
                            @else
                                <span class="font-mono text-muted">#{{ $payment->order_id }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Método de pago</dt>
                        <dd class="mt-1 font-semibold text-text">{{ $methodLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Proveedor</dt>
                        <dd class="mt-1 text-text-soft uppercase tracking-wide">{{ $payment->provider ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Monto</dt>
                        <dd class="mt-1 text-text font-bold text-lg">
                            {{ number_format(((int) $payment->amount_cents) / 100, 2) }}
                            <span class="text-sm text-muted font-semibold">{{ $payment->currency }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Fecha de creación</dt>
                        <dd class="mt-1 text-text-soft">{{ $payment->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Pagado el</dt>
                        <dd class="mt-1 text-text-soft">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Expira</dt>
                        <dd class="mt-1 text-text-soft">{{ $payment->expires_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-border bg-surface p-6">
                <h2 class="text-sm font-title text-text mb-5">Referencias del proveedor</h2>
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Charge ID</dt>
                        <dd class="mt-1 font-mono text-text-soft break-all">{{ $payment->culqi_charge_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Order ID (Culqi)</dt>
                        <dd class="mt-1 font-mono text-text-soft break-all">{{ $payment->culqi_order_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Código de pago</dt>
                        <dd class="mt-1 font-mono text-text-soft">{{ $payment->payment_code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Source ID</dt>
                        <dd class="mt-1 font-mono text-text-soft break-all">{{ $payment->source_id ?? '—' }}</dd>
                    </div>
                    @if ($payment->payment_url)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wider text-muted">URL de pago</dt>
                            <dd class="mt-1">
                                <a href="{{ $payment->payment_url }}" target="_blank" rel="noopener" class="text-sky-700 hover:text-sky-800 break-all">
                                    {{ $payment->payment_url }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-title text-text mb-4">Cliente</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nombre</dt>
                        <dd class="mt-1 font-semibold text-text">{{ $fullName !== '' ? $fullName : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Email</dt>
                        <dd class="mt-1 text-text-soft">{{ $order?->user?->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Documento</dt>
                        <dd class="mt-1 text-text-soft">{{ $profile?->document ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Teléfono</dt>
                        <dd class="mt-1 text-text-soft">{{ $profile?->phone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-title text-text mb-4">Orden relacionada</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Nº de orden</dt>
                        <dd class="mt-1 font-mono font-semibold text-text">#{{ $order?->id ?? $payment->order_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Total orden</dt>
                        <dd class="mt-1 text-text-soft">
                            @if ($order)
                                {{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted">Ítems</dt>
                        <dd class="mt-1 text-text-soft">{{ $order?->items?->count() ?? 0 }}</dd>
                    </div>
                </dl>
                @if ($order)
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="mt-4 inline-flex items-center rounded border border-border px-4 py-2 text-xs font-bold uppercase tracking-wide text-text-soft hover:text-text hover:border-border-strong transition-colors">
                        Ver orden
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection

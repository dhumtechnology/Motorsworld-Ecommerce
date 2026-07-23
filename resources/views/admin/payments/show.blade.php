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
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-yellow-950 text-yellow-400 border-yellow-800'],
            'paid' => ['label' => 'Pagado', 'class' => 'bg-green-950 text-green-400 border-green-800'],
            'failed' => ['label' => 'Fallido', 'class' => 'bg-red-950 text-red-400 border-red-800'],
            'expired' => ['label' => 'Expirado', 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'],
            'refunded' => ['label' => 'Reembolsado', 'class' => 'bg-orange-950 text-orange-400 border-orange-800'],
        ];

        $statusKey = $payment->status instanceof \App\Enums\Payments\PaymentRecordStatus
            ? $payment->status->value
            : (string) $payment->status;
        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'];
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-400 hover:text-orange-400 transition-colors">
            ← Volver a pagos
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider text-white">Información del pago</h2>
                        <p class="text-xs text-neutral-500 mt-1">Datos principales del cobro</p>
                    </div>
                    <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Nº de pago</dt>
                        <dd class="mt-1 font-mono font-semibold text-white">#{{ $payment->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Nº de orden</dt>
                        <dd class="mt-1">
                            @if ($order)
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-sky-400 hover:text-sky-300">#{{ $order->id }}</a>
                            @else
                                <span class="font-mono text-neutral-400">#{{ $payment->order_id }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Método de pago</dt>
                        <dd class="mt-1 font-semibold text-white">{{ $methodLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Proveedor</dt>
                        <dd class="mt-1 text-neutral-300 uppercase tracking-wide">{{ $payment->provider ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Monto</dt>
                        <dd class="mt-1 text-white font-bold text-lg">
                            {{ number_format(((int) $payment->amount_cents) / 100, 2) }}
                            <span class="text-sm text-neutral-500 font-semibold">{{ $payment->currency }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Fecha de creación</dt>
                        <dd class="mt-1 text-neutral-300">{{ $payment->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Pagado el</dt>
                        <dd class="mt-1 text-neutral-300">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Expira</dt>
                        <dd class="mt-1 text-neutral-300">{{ $payment->expires_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
                <h2 class="text-sm font-black uppercase tracking-wider text-white mb-5">Referencias del proveedor</h2>
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Charge ID</dt>
                        <dd class="mt-1 font-mono text-neutral-300 break-all">{{ $payment->culqi_charge_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Order ID (Culqi)</dt>
                        <dd class="mt-1 font-mono text-neutral-300 break-all">{{ $payment->culqi_order_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Código de pago</dt>
                        <dd class="mt-1 font-mono text-neutral-300">{{ $payment->payment_code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Source ID</dt>
                        <dd class="mt-1 font-mono text-neutral-300 break-all">{{ $payment->source_id ?? '—' }}</dd>
                    </div>
                    @if ($payment->payment_url)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wider text-neutral-500">URL de pago</dt>
                            <dd class="mt-1">
                                <a href="{{ $payment->payment_url }}" target="_blank" rel="noopener" class="text-sky-400 hover:text-sky-300 break-all">
                                    {{ $payment->payment_url }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5">
                <h2 class="text-sm font-black uppercase tracking-wider text-white mb-4">Cliente</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Nombre</dt>
                        <dd class="mt-1 font-semibold text-white">{{ $fullName !== '' ? $fullName : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Email</dt>
                        <dd class="mt-1 text-neutral-300">{{ $order?->user?->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Documento</dt>
                        <dd class="mt-1 text-neutral-300">{{ $profile?->document ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Teléfono</dt>
                        <dd class="mt-1 text-neutral-300">{{ $profile?->phone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5">
                <h2 class="text-sm font-black uppercase tracking-wider text-white mb-4">Orden relacionada</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Nº de orden</dt>
                        <dd class="mt-1 font-mono font-semibold text-white">#{{ $order?->id ?? $payment->order_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Total orden</dt>
                        <dd class="mt-1 text-neutral-300">
                            @if ($order)
                                {{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-neutral-500">Ítems</dt>
                        <dd class="mt-1 text-neutral-300">{{ $order?->items?->count() ?? 0 }}</dd>
                    </div>
                </dl>
                @if ($order)
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="mt-4 inline-flex items-center rounded border border-neutral-700 px-4 py-2 text-xs font-bold uppercase tracking-wide text-neutral-300 hover:text-white hover:border-neutral-500 transition-colors">
                        Ver orden
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection

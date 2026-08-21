@extends('layouts.shop')

@section('title', 'Confirmando pago — '.config('app.name'))

@section('content')
@php
    $isFailed = in_array($payment?->status->value, ['failed', 'expired', 'refunded'], true)
        || $order->payment_status === \App\Enums\Orders\PaymentStatus::Failed;
@endphp
<div class="mx-auto max-w-lg px-4 py-16 text-neutral-900 font-title">
    <div class="rounded-3xl border border-neutral-200 bg-white p-8 shadow-sm text-center">
        @if ($isFailed)
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">Pago no confirmado</p>
            <h1 class="mt-3 text-2xl font-black uppercase tracking-wide text-neutral-900">No pudimos confirmar el pago</h1>
            <p class="mt-3 text-sm text-neutral-600">
                El pedido #{{ $order->id }} quedó registrado, pero el pago no fue aprobado.
                Puedes volver al checkout e intentar con otro medio.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('shop.checkout.show') }}" class="inline-flex justify-center rounded-2xl bg-orange-600 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500">
                    Volver al checkout
                </a>
                <a href="{{ route('shop.catalog') }}" class="inline-flex justify-center rounded-2xl border border-neutral-200 px-5 py-3 text-sm font-bold uppercase tracking-wide text-neutral-700 hover:border-orange-500">
                    Ir al catálogo
                </a>
            </div>
        @else
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-orange-50">
                <svg class="h-7 w-7 animate-spin text-orange-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">Pedido #{{ $order->id }}</p>
            <h1 class="mt-3 text-2xl font-black uppercase tracking-wide text-neutral-900">Confirmando tu pago</h1>
            <p class="mt-3 text-sm text-neutral-600">
                Mercado Pago aún está procesando la transacción.
                El resumen del pedido se mostrará automáticamente cuando el pago sea aprobado.
            </p>
            <p id="processing-status" class="mt-4 text-xs font-bold uppercase tracking-widest text-neutral-400">
                Estado: {{ $payment?->status->value ?? 'pending' }}
            </p>

            @if ($mpFake ?? false)
                <form method="POST" action="{{ route('shop.checkout.orders.simulate', $order) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-sky-500">
                        Simular pago recibido (fake)
                    </button>
                </form>
            @endif

            <a href="{{ route('shop.catalog') }}" class="mt-8 inline-block text-sm font-bold text-neutral-500 hover:text-orange-600">
                Seguir comprando
            </a>
        @endif
    </div>
</div>

@if (! $isFailed)
<script>
(function () {
    const statusUrl = @json($statusUrl);
    const statusEl = document.getElementById('processing-status');
    let tries = 0;
    const maxTries = 40;

    async function check() {
        tries += 1;
        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (statusEl && data.status) {
                statusEl.textContent = 'Estado: ' + data.status;
            }
            if (data.paid && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }
            if (data.discarded || ['rejected', 'cancelled', 'failed', 'refunded'].includes(String(data.status || ''))) {
                window.location.href = data.redirect_url || @json(route('shop.checkout.show'));
                return;
            }
        } catch (e) {}

        if (tries < maxTries) {
            setTimeout(check, 3000);
        } else if (statusEl) {
            statusEl.textContent = 'Aún en revisión. Recarga en unos minutos.';
        }
    }

    setTimeout(check, 2000);
})();
</script>
@endif
@endsection

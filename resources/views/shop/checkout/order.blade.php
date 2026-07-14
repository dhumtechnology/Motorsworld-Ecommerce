@extends('layouts.shop')

@section('title', 'Pedido #'.$order->id.' — '.config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 text-white">
    <h1 class="text-3xl font-black uppercase tracking-wide mb-6">Pedido #{{ $order->id }}</h1>

    @if (session('status'))
        <div class="mb-6 rounded border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] divide-y divide-neutral-800 mb-6">
        @foreach ($order->items as $item)
            <div class="flex justify-between gap-4 p-4 text-sm">
                <div>
                    <p class="font-semibold">{{ $item->product?->name ?? 'Producto' }}</p>
                    <p class="text-neutral-500 text-xs">x{{ $item->quantity }}</p>
                </div>
                <p class="font-bold text-orange-500">
                    S/ {{ number_format((float) $item->unit_price * $item->quantity, 2) }}
                </p>
            </div>
        @endforeach
        <div class="flex justify-between p-4">
            <span class="text-neutral-400 uppercase text-xs font-bold tracking-widest">Total</span>
            <span class="text-xl font-black">S/ {{ number_format((float) $order->total_amount, 2) }}</span>
        </div>
    </div>

    @if ($payment && $payment->status->value === 'pending')
        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 space-y-3 mb-6">
            @if ($payment->payment_code)
                <div class="rounded border border-orange-800/50 bg-orange-500/10 p-4">
                    <p class="text-xs uppercase tracking-widest text-orange-400 font-bold mb-1">Código CIP (PagoEfectivo)</p>
                    <p class="text-2xl font-black tracking-widest">{{ $payment->payment_code }}</p>
                    @if ($payment->payment_url)
                        <a href="{{ $payment->payment_url }}" target="_blank" class="inline-block mt-2 text-sm text-orange-500 hover:text-orange-400">
                            Abrir instrucciones de pago ↗
                        </a>
                    @endif
                </div>
            @endif

            @if ($payment->qr_url)
                <div class="rounded border border-neutral-700 p-4">
                    <p class="text-xs uppercase tracking-widest text-neutral-500 font-bold mb-3">QR Plin / billeteras</p>
                    <img src="{{ $payment->qr_url }}" alt="QR de pago" class="mx-auto max-w-[220px] rounded bg-white p-2">
                </div>
            @endif

            @if ($culqiFake)
                <form method="POST" action="{{ route('shop.checkout.orders.simulate', $order) }}" class="pt-2">
                    @csrf
                    <button type="submit" class="rounded bg-sky-700 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-sky-600">
                        Simular pago recibido (fake)
                    </button>
                </form>
            @endif
        </div>
    @endif

    <div class="mt-8 flex gap-4">
        <a href="{{ route('shop.catalog') }}" class="text-sm font-bold text-orange-500 hover:text-orange-400">
            ← Volver al catálogo
        </a>
    </div>
</div>
@endsection

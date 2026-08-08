@extends('layouts.shop')

@section('title', 'Pedido #'.$order->id.' — '.config('app.name'))

@section('content')
@php
    $profile = $order->user?->customerProfile;
    $shipping = $order->shippingAddress;
    $customerName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
    $isPickup = $order->fulfillment_method === \App\Enums\Orders\FulfillmentMethod::Pickup;
    $storeAddress = config('shop.contact.address');
    $mapEmbedUrl = config('shop.map_embed_url');
    $mapsLink = 'https://maps.google.com/?q='.urlencode((string) $storeAddress);
    $orderCurrencySymbol = \App\Support\Currency::symbol($order->currency ?? 'PEN');
@endphp
<div class="mx-auto max-w-3xl px-4 py-10 text-neutral-900 font-title">
    <h1 class="text-3xl font-black uppercase tracking-wide mb-6">Pedido #{{ $order->id }}</h1>

    @if (session('status'))
        <div class="mb-6 rounded border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-neutral-200 bg-white divide-y divide-neutral-100 mb-6 shadow-sm">
        @foreach ($order->items as $item)
            @php
                $itemCurrencySymbol = \App\Support\Currency::symbol($item->currency ?? $order->currency ?? 'PEN');
            @endphp
            <div class="flex justify-between gap-4 p-4 text-sm">
                <div>
                    <p class="font-semibold text-neutral-900">{{ $item->product?->name ?? 'Producto' }}</p>
                    @if ($item->variant)
                        <p class="text-neutral-600 text-xs">{{ $item->variant->colorLabel() }} · {{ $item->variant->sku }}</p>
                    @endif
                    <p class="text-neutral-500 text-xs">x{{ $item->quantity }}</p>
                </div>
                <p class="font-bold text-orange-600">
                    {{ $itemCurrencySymbol }} {{ number_format((float) $item->unit_price * $item->quantity, 2) }}
                </p>
            </div>
        @endforeach
        <div class="flex justify-between p-4">
            <span class="text-neutral-500 uppercase text-xs font-bold tracking-widest">Total</span>
            <span class="text-xl font-black text-neutral-900">{{ $orderCurrencySymbol }} {{ number_format((float) $order->total_amount, 2) }}</span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 mb-6">
        <div class="rounded-lg border border-neutral-200 bg-white p-5 space-y-2 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-widest text-neutral-500 mb-3">Datos del cliente</h2>
            <p class="text-sm">
                <span class="text-neutral-500">Nombre:</span>
                <span class="font-semibold text-neutral-900">{{ $customerName !== '' ? $customerName : '—' }}</span>
            </p>
            <p class="text-sm">
                <span class="text-neutral-500">Email:</span>
                <span class="font-semibold text-neutral-900">{{ $order->user?->email ?? '—' }}</span>
            </p>
            <p class="text-sm">
                <span class="text-neutral-500">Teléfono:</span>
                <span class="font-semibold text-neutral-900">{{ $profile?->phone ?: '—' }}</span>
            </p>
            @if ($profile?->document)
                <p class="text-sm">
                    <span class="text-neutral-500">Documento:</span>
                    <span class="font-semibold text-neutral-900">{{ $profile->document }}</span>
                </p>
            @endif
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-5 space-y-2 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-widest text-neutral-500 mb-3">Entrega</h2>
            <p class="text-sm font-semibold text-neutral-900">
                {{ $order->fulfillment_method?->label() ?? 'Delivery' }}
            </p>
            @if ($isPickup)
                <p class="text-sm text-neutral-600">Retira tu pedido en nuestra tienda:</p>
                <p class="text-sm font-semibold text-neutral-900">{{ $storeAddress }}</p>
                <a
                    href="{{ $mapsLink }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex text-sm font-bold text-orange-600 hover:text-orange-500"
                >
                    Ver en Google Maps →
                </a>
            @elseif ($shipping)
                <p class="text-sm font-semibold text-neutral-900 mt-2">{{ $shipping->line1 }}</p>
                <p class="text-sm text-neutral-600">
                    {{ $shipping->city }}
                    @if ($shipping->postal_code)
                        · {{ $shipping->postal_code }}
                    @endif
                </p>
                <p class="text-sm text-neutral-500">{{ $shipping->country }}</p>
            @else
                <p class="text-sm text-neutral-500">No se registró dirección de envío.</p>
            @endif
        </div>
    </div>

    @if ($isPickup && $mapEmbedUrl)
        <div class="rounded-lg border border-neutral-200 bg-white overflow-hidden mb-6 shadow-sm">
            <div class="px-5 py-4 border-b border-neutral-100">
                <h2 class="text-xs font-bold uppercase tracking-widest text-neutral-500">Ubicación de tienda</h2>
                <p class="mt-1 text-sm text-neutral-600">{{ $storeAddress }}</p>
            </div>
            <div class="relative w-full aspect-[16/9] min-h-[220px]">
                <iframe
                    title="Mapa Motosworld — {{ $storeAddress }}"
                    src="{{ $mapEmbedUrl }}"
                    class="absolute inset-0 h-full w-full border-0"
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
    @endif

    @if ($payment && $payment->status->value === 'pending')
        <div class="rounded-lg border border-neutral-200 bg-white p-5 space-y-3 mb-6 shadow-sm">
            @if ($payment->payment_code)
                <div class="rounded border border-orange-200 bg-orange-50 p-4">
                    <p class="text-xs uppercase tracking-widest text-orange-600 font-bold mb-1">Código CIP (PagoEfectivo)</p>
                    <p class="text-2xl font-black tracking-widest text-neutral-900">{{ $payment->payment_code }}</p>
                    @if ($payment->payment_url)
                        <a href="{{ $payment->payment_url }}" target="_blank" class="inline-block mt-2 text-sm text-orange-600 hover:text-orange-500">
                            Abrir instrucciones de pago ↗
                        </a>
                    @endif
                </div>
            @endif

            @if ($payment->qr_url)
                <div class="rounded border border-neutral-200 p-4">
                    <p class="text-xs uppercase tracking-widest text-neutral-500 font-bold mb-3">QR Plin / billeteras</p>
                    <img src="{{ $payment->qr_url }}" alt="QR de pago" class="mx-auto max-w-[220px] rounded bg-white p-2 border border-neutral-100">
                </div>
            @endif

            @if ($culqiFake)
                <form method="POST" action="{{ route('shop.checkout.orders.simulate', $order) }}" class="pt-2">
                    @csrf
                    <button type="submit" class="rounded bg-sky-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-sky-500">
                        Simular pago recibido (fake)
                    </button>
                </form>
            @endif
        </div>
    @endif

    <div class="mt-8 flex gap-4">
        <a href="{{ route('shop.catalog') }}" class="text-sm font-bold text-orange-600 hover:text-orange-500">
            ← Volver al catálogo
        </a>
        <a href="{{ route('shop.account.show') }}" class="text-sm font-bold text-neutral-600 hover:text-neutral-900">
            Ver mi cuenta
        </a>
    </div>
</div>
@endsection

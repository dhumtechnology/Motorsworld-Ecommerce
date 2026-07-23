@extends('layouts.shop')

@section('title', 'Carrito — '.config('app.name'))

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10" data-cart-page>
    <h1 class="text-3xl font-black uppercase tracking-wide mb-2">Tu carrito</h1>
    <p class="text-neutral-400 text-sm mb-8" data-cart-summary-text>
        @if ($itemCount > 0)
            {{ $itemCount }} {{ $itemCount === 1 ? 'producto' : 'productos' }} en el carrito
        @else
            Tu carrito está vacío
        @endif
    </p>

    <p data-cart-error class="hidden mb-6 text-sm text-rose-400 font-semibold"></p>

    <div data-cart-empty class="{{ $lines->isEmpty() ? '' : 'hidden' }} rounded-lg border border-dashed border-neutral-700 bg-[#1e1e1e]/60 p-10 text-center">
        <p class="text-neutral-400 text-sm mb-6">Aún no has agregado productos.</p>
        <a href="{{ route('shop.catalog') }}"
           class="inline-block rounded bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wide hover:bg-orange-500 transition-colors">
            Ir al catálogo
        </a>
    </div>

    <div data-cart-content class="{{ $lines->isEmpty() ? 'hidden' : '' }}">
        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden divide-y divide-neutral-800 mb-6">
            @foreach ($lines as $line)
                <div
                    class="flex flex-col sm:flex-row gap-4 p-4 sm:items-center"
                    data-cart-line
                    data-product-id="{{ $line['product']->id }}"
                    data-unit-price="{{ $line['unit_price'] }}"
                    data-max-stock="{{ $line['max_quantity'] }}"
                    data-increment-url="{{ route('shop.cart.items.increment', $line['product']) }}"
                    data-decrement-url="{{ route('shop.cart.items.decrement', $line['product']) }}"
                >
                    <a href="{{ route('shop.product.show', $line['product']) }}" class="shrink-0">
                        @if ($line['image'])
                            <img src="{{ $line['image'] }}" alt="" class="h-20 w-20 rounded object-cover border border-neutral-700">
                        @else
                            <div class="h-20 w-20 rounded bg-[#252525] border border-neutral-700"></div>
                        @endif
                    </a>

                    <div class="flex-1 min-w-0">
                        <a href="{{ route('shop.product.show', $line['product']) }}" class="font-semibold hover:text-orange-500 transition-colors">
                            {{ $line['product']->name }}
                        </a>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            {{ $line['product']->sku }}
                            @if ($line['product']->category)
                                · {{ $line['product']->category->name }}
                            @endif
                        </p>
                        <p class="text-sm text-orange-500 font-bold mt-2">
                            S/ {{ number_format($line['unit_price'], 2) }}
                            @if ($line['is_on_sale'])
                                <span class="text-neutral-500 line-through font-normal ml-2">
                                    S/ {{ number_format($line['list_unit_price'], 2) }}
                                </span>
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white overflow-hidden rounded-sm shrink-0">
                        <button
                            type="button"
                            data-cart-action="decrement"
                            class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-black text-2xl focus:outline-none"
                            aria-label="Disminuir"
                        >
                            −
                        </button>
                        <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center font-black text-lg">
                            <span data-line-qty>{{ $line['quantity'] }}</span>
                        </div>
                        <button
                            type="button"
                            data-cart-action="increment"
                            @disabled($line['quantity'] >= $line['max_quantity'])
                            class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-black text-xl focus:outline-none disabled:opacity-40"
                            aria-label="Aumentar"
                        >
                            +
                        </button>
                    </div>

                    <div class="sm:text-right shrink-0 sm:min-w-[100px]">
                        <p class="font-black text-lg" data-line-total>S/ {{ number_format($line['line_total'], 2) }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-8 flex justify-between items-center">
            <span class="text-neutral-400 uppercase text-xs font-bold tracking-widest">Total</span>
            <span class="text-2xl font-black" data-cart-grand-total>S/ {{ number_format($total, 2) }}</span>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:justify-between">
            <a href="{{ route('shop.catalog') }}"
               class="inline-flex justify-center rounded border border-neutral-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-neutral-300 hover:border-neutral-400 hover transition-colors">
                Seguir comprando
            </a>
            <a href="{{ route('shop.checkout.show') }}"
               class="inline-flex justify-center rounded bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wide hover:bg-orange-500 transition-colors">
                Pagar ahora
            </a>
        </div>
    </div>
</div>
@endsection

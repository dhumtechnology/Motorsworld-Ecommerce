@extends('layouts.shop')

@section('title', 'Carrito — '.config('app.name'))

@section('content')
<div
    class="mx-auto max-w-5xl px-4 py-10"
    data-cart-page
    data-currency-symbol="{{ $totalCurrencySymbol }}"
    data-sell-rate="{{ $totals->hasRate ? number_format((float) $totals->sellRate, 4, '.', '') : '' }}"
    data-rate-date="{{ $totals->rateDate ?? '' }}">
    <h1 class="text-3xl font-black uppercase tracking-wide mb-2">Tu carrito</h1>
    <p class="text-black text-sm mb-8" data-cart-summary-text>
        @if ($itemCount > 0)
        {{ $itemCount }} {{ $itemCount === 1 ? 'producto' : 'productos' }} en el carrito
        @else
        Tu carrito está vacío
        @endif
    </p>

    <p data-cart-error class="hidden mb-6 text-sm text-black font-title"></p>

    <div data-cart-empty class="{{ $lines->isEmpty() ? '' : 'hidden' }} rounded-lg p-10 text-center">
        <p class="text-black mb-6">Aún no has agregado productos.</p>
        <a href="{{ route('shop.catalog') }}"
            class="inline-block rounded bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wide hover:bg-orange-500 transition-colors">
            Ir al catálogo
        </a>
    </div>

    <div data-cart-content class="{{ $lines->isEmpty() ? 'hidden' : '' }}">
        <div class="w-full overflow-x-auto mb-6">
            {{-- Añadimos un ancho mínimo para que en móviles se pueda hacer scroll horizontal sin que se rompa el diseño --}}
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-neutral-100 border-b border-neutral-200">
                    <tr>
                        <th scope="col" class="py-5 px-5 text-xs font-bold uppercase tracking-wider text-neutral-800">
                            Producto
                        </th>
                        <th scope="col" class="py-5 px-5 text-xs font-bold uppercase tracking-wider text-neutral-800 text-center">
                            Precio por unidad
                        </th>
                        <th scope="col" class="py-5 px-5 text-xs font-bold uppercase tracking-wider text-neutral-800 text-center">
                            Cantidad
                        </th>
                        <th scope="col" class="py-5 px-5 text-xs font-bold uppercase tracking-wider text-neutral-800 text-right">
                            Precio total
                        </th>
                        <th scope="col" class="py-5 px-5 text-xs font-bold uppercase tracking-wider text-neutral-800 text-center">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-500">
                    @foreach ($lines as $line)
                    <tr
                        class="hover:bg-neutral-50 transition-colors"
                        data-cart-line
                        data-product-id="{{ $line['product']->id }}"
                        data-variant-id="{{ $line['variant']->id }}"
                        data-unit-price="{{ $line['unit_price'] }}"
                        data-max-stock="{{ $line['max_quantity'] }}"
                        data-currency="{{ $line['currency'] }}"
                        data-currency-symbol="{{ $line['currency_symbol'] }}"
                        data-increment-url="{{ route('shop.cart.items.increment', $line['product'], false) }}"
                        data-decrement-url="{{ route('shop.cart.items.decrement', $line['product'], false) }}">
                        {{-- Columna 1: Imagen y Detalles del Producto --}}
                        <td class="py-5 px-5">
                            <div class="flex items-center gap-4">
                                <a href="{{ route('shop.product.show', $line['product']) }}" class="shrink-0">
                                    @if ($line['image'])
                                    <img src="{{ $line['image'] }}" alt="" class="h-20 w-20 rounded object-cover">
                                    @else
                                    <div class="h-20 w-20 rounded bg-neutral-200"></div>
                                    @endif
                                </a>

                                <div class="flex-1 min-w-0 font-secondary">
                                    <a href="{{ route('shop.product.show', $line['product']) }}" class="uppercase hover:text-orange-500 transition-colors">
                                        {{ $line['product']->name }}
                                    </a>
                                    <p class="text-xs text-black mt-0.5">
                                        {{ $line['variant']->sku }}
                                        · {{ $line['color_label'] }}
                                        @if ($line['product']->category)
                                        · <span class="text-primary font-title font-bold">
                                            {{ $line['product']->category->name }}
                                        </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Columna 2: Precio Unitario --}}
                        <td class="py-5 px-5 text-center">
                            <p class="text-sm text-orange-500 font-bold">
                                {{ $line['currency_symbol'] }} {{ number_format($line['unit_price'], 2) }}
                                @if ($line['is_on_sale'])
                                <span class="block text-neutral-500 line-through font-normal text-xs mt-1">
                                    {{ $line['currency_symbol'] }} {{ number_format($line['list_unit_price'], 2) }}
                                </span>
                                @endif
                            </p>
                        </td>

                        {{-- Columna 3: Controles de Cantidad --}}
                        <td class="py-5 px-5">
                            <div class="flex items-center justify-center w-24 h-10 mx-auto overflow-hidden rounded border border-neutral-300">
                                <button
                                    type="button"
                                    data-cart-action="decrement"
                                    class="w-8 h-full flex items-center justify-center text-black hover:text-orange-500 hover:bg-neutral-100 cursor-pointer text-xl focus:outline-none"
                                    aria-label="Disminuir">
                                    −
                                </button>
                                <div class="flex-1 flex items-center justify-center font-black text-sm">
                                    <span data-line-qty>{{ $line['quantity'] }}</span>
                                </div>
                                <button
                                    type="button"
                                    data-cart-action="increment"
                                    @disabled($line['quantity']>= $line['max_quantity'])
                                    class="w-8 h-full flex items-center justify-center text-black hover:text-orange-500 hover:bg-neutral-100 cursor-pointer text-xl focus:outline-none disabled:opacity-40"
                                    aria-label="Aumentar"
                                    >
                                    +
                                </button>
                            </div>
                        </td>

                        {{-- Columna 4: Precio Total de la Línea --}}
                        <td class="py-5 px-5 text-right">
                            <div class="flex items-center justify-end gap-4">
                                <p class="text-sm font-bold text-black" data-line-total>
                                    {{ $line['currency_symbol'] }} {{ number_format($line['line_total'], 2) }}
                                </p>
                            </div>
                        </td>
                        {{-- Botón de eliminación absoluta --}}
                        <td class="py-5 px-5 text-center">
                            <button
                                type="button"
                                data-cart-action="remove"
                                data-remove-url="{{ route('shop.cart.items.remove', $line['product'], false) }}"
                                class="text-neutral-400 hover:text-red-600 transition-colors focus:outline-none shrink-0"
                                aria-label="Eliminar todo el producto"
                                title="Eliminar del carrito">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg p-5 mb-8 space-y-3 font-secondary" data-cart-totals>
        <div class="space-y-1 text-sm" data-cart-subtotals>
            <div class="flex justify-end gap-4 {{ $totals->hasPen() ? '' : 'hidden' }}" data-subtotal-pen>
                <span class="text-neutral-600">Subtotal en soles</span>
                <span data-subtotal-pen-amount>S/ {{ number_format($totals->totalPen, 2) }}</span>
            </div>
            <div class="flex justify-end gap-4 {{ $totals->hasUsd() ? '' : 'hidden' }}" data-subtotal-usd>
                <span class="text-neutral-600">Subtotal en dólares</span>
                <span data-subtotal-usd-amount>$ {{ number_format($totals->totalUsd, 2) }}</span>
            </div>
        </div>

        <div class="flex flex-col items-end gap-2">
            @if ($totals->hasRate)
            <div class="inline-flex rounded border border-neutral-300 p-0.5 text-xs font-bold uppercase tracking-wide">
                <button type="button" data-cart-total-currency="PEN" class="rounded px-2.5 py-1 bg-orange-600 text-white">Soles</button>
                <button type="button" data-cart-total-currency="USD" class="rounded px-2.5 py-1 text-neutral-600">Dólares</button>
            </div>
            <div class="flex justify-end gap-4 text-xl">
                <span class="text-black uppercase tracking-widest">Total</span>
                <span
                    data-cart-grand-total
                    data-pen="{{ number_format($totals->grandPen, 2, '.', '') }}"
                    data-usd="{{ number_format($totals->grandUsd, 2, '.', '') }}">S/ {{ number_format($totals->grandPen, 2) }}</span>
            </div>
            <p class="text-xs text-neutral-500" data-cart-rate-note>
                TC venta SUNAT:
                <span class="font-mono">{{ number_format((float) $totals->sellRate, 4) }}</span>
                @if ($totals->rateDate)
                · {{ $totals->rateDate }}
                @endif
            </p>
            @else
            <div class="flex justify-end gap-4 text-xl">
                <span class="text-black uppercase tracking-widest">Total</span>
                <span data-cart-grand-total>{{ $totalCurrencySymbol }} {{ number_format($total, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 font-title sm:justify-between px-6">
        <a href="{{ route('shop.catalog') }}"
            class="inline-flex justify-center rounded px-6 py-3 text-sm font-bold uppercase tracking-wide bg-black text-white hover:border-neutral-400 hover transition-colors">
            Continuar comprando
        </a>
        <a href="{{ route('shop.checkout.show') }}"
            class="inline-flex justify-center text-white rounded bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wide hover:bg-orange-500 transition-colors">
            Ir a pagar
        </a>
    </div>
</div>
</div>
@endsection
@props([
    'lines' => [],
    'itemCount' => 0,
    'totals' => null,
])

@php
    $lines = collect($lines);
    $itemCount = (int) $itemCount;
    $hasItems = $lines->isNotEmpty();
    $chargeAmount = $totals?->chargeAmount();
    $chargeSymbol = \App\Support\Currency::symbol($totals?->chargeCurrency() ?: 'PEN');
@endphp

<div
    class="relative h-full"
    data-cart-drawer
    data-catalog-url="{{ route('shop.catalog') }}"
    x-data="{
        open: false,
        openDrawer() {
            this.open = true;
            document.documentElement.classList.add('overflow-hidden');
        },
        closeDrawer() {
            this.open = false;
            document.documentElement.classList.remove('overflow-hidden');
        },
        toggle() {
            this.open ? this.closeDrawer() : this.openDrawer();
        },
    }"
    @keydown.escape.window="if (open) closeDrawer()"
    @open-cart-drawer.window="openDrawer()"
    @close-cart-drawer.window="closeDrawer()"
>
    <button
        type="button"
        data-cart-icon
        @click="toggle()"
        class="inline-flex h-full min-w-11 items-center justify-center px-3 text-white transition-colors hover:bg-orange-600"
        :class="open ? 'bg-orange-600' : ''"
        title="Ver carrito"
        aria-label="Ver carrito"
        :aria-expanded="open.toString()"
        aria-controls="shop-cart-drawer"
    >
        <span class="relative inline-flex h-5 w-5 items-center justify-center" data-cart-icon-mark>
            <svg class="h-5 w-5" viewBox="0 0 25 23" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M9.33366 22C9.90896 22 10.3753 21.5523 10.3753 21C10.3753 20.4477 9.90896 20 9.33366 20C8.75836 20 8.29199 20.4477 8.29199 21C8.29199 21.5523 8.75836 22 9.33366 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20.7917 22C21.367 22 21.8333 21.5523 21.8333 21C21.8333 20.4477 21.367 20 20.7917 20C20.2164 20 19.75 20.4477 19.75 21C19.75 21.5523 20.2164 22 20.7917 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M1 1H5.16667L7.95833 14.39C8.05359 14.8504 8.31449 15.264 8.69536 15.5583C9.07623 15.8526 9.55281 16.009 10.0417 16H20.1667C20.6555 16.009 21.1321 15.8526 21.513 15.5583C21.8938 15.264 22.1547 14.8504 22.25 14.39L23.9167 6H6.20833" fill="none"/>
                <path d="M1 1H5.16667L7.95833 14.39C8.05359 14.8504 8.31449 15.264 8.69536 15.5583C9.07623 15.8526 9.55281 16.009 10.0417 16H20.1667C20.6555 16.009 21.1321 15.8526 21.513 15.5583C21.8938 15.264 22.1547 14.8504 22.25 14.39L23.9167 6H6.20833" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @if ($itemCount > 0)
                <span data-cart-badge class="absolute -right-2 -top-2 min-w-[16px] h-4 px-1 rounded-full bg-orange-600 text-white text-[9px] font-black leading-4 text-center">
                    {{ $itemCount > 99 ? '99+' : $itemCount }}
                </span>
            @endif
        </span>
    </button>

    <template x-teleport="body">
        <div
            id="shop-cart-drawer"
            data-cart-drawer-panel
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[70]"
            role="dialog"
            aria-modal="true"
            aria-label="Carrito de compras"
        >
            <div
                class="absolute inset-0 bg-black/45"
                @click="closeDrawer()"
                data-cart-drawer-close
                aria-hidden="true"
            ></div>

            <aside
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="absolute inset-y-0 right-0 flex w-full max-w-[100vw] flex-col bg-white shadow-2xl sm:max-w-md"
                @click.stop
            >
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-4 sm:px-5">
                    <div>
                        <h2 class="text-base font-black uppercase tracking-[0.12em] text-neutral-900">
                            Tu carrito
                        </h2>
                        <p data-cart-drawer-summary class="mt-0.5 text-xs text-neutral-500">
                            @if ($hasItems)
                                {{ $itemCount }} {{ $itemCount === 1 ? 'producto' : 'productos' }}
                            @else
                                Vacío
                            @endif
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeDrawer()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-neutral-300 text-neutral-700 hover:border-neutral-400 hover:text-neutral-900 transition-colors"
                        aria-label="Cerrar carrito"
                        title="Cerrar"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div data-cart-drawer-body class="flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                    @if (! $hasItems)
                        <div class="flex h-full min-h-[12rem] flex-col items-center justify-center px-4 text-center">
                            <p class="text-sm text-neutral-600">Aún no has agregado productos.</p>
                            <a
                                href="{{ route('shop.catalog') }}"
                                data-cart-drawer-close
                                class="mt-5 inline-flex rounded bg-orange-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors"
                            >
                                Ir al catálogo
                            </a>
                        </div>
                    @else
                        <ul class="flex flex-col divide-y divide-neutral-200">
                            @foreach ($lines as $line)
                                <li class="flex gap-3 py-4 first:pt-0 last:pb-0">
                                    <a
                                        href="{{ route('shop.product.show', $line['product']) }}"
                                        data-cart-drawer-close
                                        class="h-20 w-20 shrink-0 overflow-hidden rounded bg-neutral-100"
                                    >
                                        @if (! empty($line['image']))
                                            <img
                                                src="{{ $line['image'] }}"
                                                alt=""
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @endif
                                    </a>

                                    <div class="min-w-0 flex-1">
                                        <a
                                            href="{{ route('shop.product.show', $line['product']) }}"
                                            data-cart-drawer-close
                                            class="line-clamp-2 text-sm font-bold uppercase leading-snug text-neutral-900 hover:text-orange-600 transition-colors"
                                        >
                                            {{ $line['product']->name }}
                                        </a>
                                        <p class="mt-1 text-[11px] text-neutral-500">
                                            {{ $line['color_label'] }}
                                            · Cant. {{ $line['quantity'] }}
                                        </p>
                                        <p class="mt-2 text-sm font-black text-neutral-900">
                                            {{ $line['currency_symbol'] }} {{ number_format($line['line_total'], 2) }}
                                        </p>
                                        @if ($line['is_on_sale'])
                                            <p class="text-[11px] text-neutral-400 line-through">
                                                {{ $line['currency_symbol'] }} {{ number_format($line['list_unit_price'] * $line['quantity'], 2) }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="border-t border-neutral-200 bg-neutral-50 px-4 py-4 sm:px-5">
                    <div
                        data-cart-drawer-total
                        class="mb-4 flex items-baseline justify-between gap-3 {{ ($hasItems && $chargeAmount !== null) ? '' : 'hidden' }}"
                    >
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total</span>
                        <span data-cart-drawer-total-amount class="text-lg font-black text-neutral-900">
                            @if ($hasItems && $chargeAmount !== null)
                                {{ $chargeSymbol }} {{ number_format((float) $chargeAmount, 2) }}
                            @endif
                        </span>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <a
                            href="{{ route('shop.cart.index') }}"
                            data-cart-drawer-close
                            class="inline-flex w-full items-center justify-center rounded border border-neutral-900 bg-white px-4 py-3 text-xs font-bold uppercase tracking-wide text-neutral-900 hover:bg-neutral-100 transition-colors"
                        >
                            Ver carrito
                        </a>
                        <a
                            href="{{ route('shop.checkout.show') }}"
                            data-cart-drawer-pay
                            @class([
                                'inline-flex w-full items-center justify-center rounded px-4 py-3 text-xs font-bold uppercase tracking-wide transition-colors',
                                'bg-orange-600 text-white hover:bg-orange-500' => $hasItems,
                                'pointer-events-none bg-neutral-300 text-neutral-500' => ! $hasItems,
                            ])
                            @if ($hasItems) data-cart-drawer-close @endif
                            @unless ($hasItems) aria-disabled="true" tabindex="-1" @endunless
                        >
                            Pagar
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </template>
</div>

@props([
    'categories' => [],
    'products' => null,
    'value' => '',
    'action' => null,
])

@php
    $actionUrl = $action ?? route('shop.catalog');
    $current = is_string($value) ? $value : (string) ($value ?? '');
    $recommended = $products ?? collect();
@endphp

<div
    class="relative h-full"
    x-data="{
        open: false,
        query: @js($current),
        openPanel() {
            this.open = true;
            document.documentElement.classList.add('overflow-hidden');
            this.$nextTick(() => this.$refs.input?.focus());
        },
        closePanel() {
            this.open = false;
            document.documentElement.classList.remove('overflow-hidden');
        },
        toggle() {
            this.open ? this.closePanel() : this.openPanel();
        },
    }"
    @keydown.escape.window="if (open) closePanel()"
    {{ $attributes }}
>
    <button
        type="button"
        @click="toggle()"
        class="inline-flex h-full min-w-11 items-center justify-center px-3 text-white transition-colors hover:bg-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-orange-500/70"
        :class="open ? 'bg-orange-600' : ''"
        :aria-expanded="open.toString()"
        aria-controls="shop-search-panel"
        aria-label="Buscar productos"
        title="Buscar"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16 10.5a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            id="shop-search-panel"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[60]"
            role="dialog"
            aria-modal="true"
            aria-label="Buscar en la tienda"
        >
            <div
                class="absolute inset-0 bg-black/45"
                @click="closePanel()"
                aria-hidden="true"
            ></div>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="absolute inset-x-0 top-0 max-h-[min(92vh,52rem)] overflow-hidden border-b border-neutral-200 bg-white shadow-xl"
                @click.stop
            >
                <div class="border-b border-neutral-200 bg-neutral-50 px-4 py-3 sm:px-6 lg:px-10">
                    <form
                        action="{{ $actionUrl }}"
                        method="GET"
                        class="flex items-center gap-2 sm:gap-3"
                        @submit="if (! query.trim()) { $event.preventDefault(); }"
                    >
                        <div class="relative min-w-0 flex-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16 10.5a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
                            </svg>
                            <input
                                x-ref="input"
                                type="search"
                                name="search"
                                x-model="query"
                                value="{{ $current }}"
                                placeholder="Buscar producto, marca o categoría…"
                                autocomplete="off"
                                class="w-full rounded-lg border border-neutral-300 bg-white py-2.5 pl-10 pr-3 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none ring-0 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 [&::-webkit-search-cancel-button]:appearance-none"
                            >
                        </div>
                        <button
                            type="submit"
                            class="shrink-0 rounded-lg bg-orange-600 px-3 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors sm:px-4"
                        >
                            Buscar
                        </button>
                        <button
                            type="button"
                            @click="closePanel()"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-neutral-300 bg-white text-neutral-700 hover:border-neutral-400 hover:text-neutral-900 transition-colors"
                            aria-label="Cerrar búsqueda"
                            title="Cerrar"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="grid max-h-[calc(min(92vh,52rem)-4.5rem)] grid-cols-1 overflow-y-auto lg:grid-cols-12">
                    <aside class="border-b border-neutral-200 bg-white lg:col-span-3 lg:border-b-0 lg:border-r lg:border-neutral-200">
                        <div class="px-4 py-4 sm:px-5 lg:px-6">
                            <p class="mb-3 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                Categorías
                            </p>

                            {{-- Móvil: tarjetas con imagen --}}
                            <ul class="search-panel-cats-mobile gap-3 overflow-x-auto pb-1">
                                @forelse ($categories as $category)
                                    <li class="w-28 shrink-0 sm:w-32">
                                        <a
                                            href="{{ $category['href'] }}"
                                            class="group relative block aspect-[4/5] overflow-hidden rounded-md bg-neutral-200 opacity-80 transition-opacity duration-300 hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:opacity-100"
                                            @click="closePanel()"
                                        >
                                            @if (! empty($category['image']))
                                                <img
                                                    src="{{ $category['image'] }}"
                                                    alt="{{ $category['name'] }}"
                                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-neutral-300 text-xs font-bold uppercase tracking-wide text-neutral-600">
                                                    {{ $category['name'] }}
                                                </div>
                                            @endif
                                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                                            <div class="pointer-events-none absolute inset-x-0 bottom-0 p-2.5">
                                                <p class="text-center text-[11px] font-black uppercase tracking-[0.1em] text-white drop-shadow sm:text-xs">
                                                    {{ $category['name'] }}
                                                </p>
                                            </div>
                                        </a>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-sm text-neutral-500">Sin categorías</li>
                                @endforelse
                            </ul>

                            {{-- Desktop: solo nombres --}}
                            <ul class="search-panel-cats-desktop">
                                @forelse ($categories as $category)
                                    <li>
                                        <a
                                            href="{{ $category['href'] }}"
                                            class="block rounded-md px-3 py-2.5 text-sm font-semibold text-neutral-800 transition-colors hover:bg-orange-50 hover:text-orange-600"
                                            @click="closePanel()"
                                        >
                                            {{ $category['name'] }}
                                        </a>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-sm text-neutral-500">Sin categorías</li>
                                @endforelse
                            </ul>
                        </div>
                    </aside>

                    <div class="bg-white px-4 py-4 sm:px-6 lg:col-span-9 lg:px-8 lg:py-5">
                        <div class="mb-4 flex items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                    Recomendados
                                </p>
                                <p class="mt-1 text-sm text-neutral-600">
                                    Los 6 productos más vendidos
                                </p>
                            </div>
                            <a
                                href="{{ route('shop.catalog') }}"
                                class="hidden text-xs font-bold uppercase tracking-wide text-orange-600 hover:text-orange-500 sm:inline"
                                @click="closePanel()"
                            >
                                Ver catálogo →
                            </a>
                        </div>

                        @if ($recommended->isEmpty())
                            <p class="py-8 text-center text-sm text-neutral-500">
                                Pronto verás recomendaciones aquí.
                            </p>
                        @else
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-2.5">
                                @foreach ($recommended as $product)
                                    @php
                                        $currencySymbol = \App\Support\Currency::symbol($product->currency ?? 'PEN');
                                        $image = $product->image ?: asset('images/home/product-placeholder.png');
                                    @endphp
                                    <a
                                        href="{{ route('shop.product.show', $product) }}"
                                        class="group flex flex-col overflow-hidden rounded-md border border-neutral-200 bg-white transition-shadow hover:shadow-md"
                                        @click="closePanel()"
                                    >
                                        <div class="aspect-square overflow-hidden bg-neutral-100">
                                            <img
                                                src="{{ $image }}"
                                                alt="{{ $product->name }}"
                                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                loading="lazy"
                                            >
                                        </div>
                                        <div class="flex flex-1 flex-col gap-1 p-2.5 sm:p-3 lg:gap-0.5 lg:p-2">
                                            <p class="line-clamp-2 text-sm font-bold leading-snug text-neutral-900 group-hover:text-orange-600 lg:text-xs">
                                                {{ $product->name }}
                                            </p>
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500 lg:text-[9px]">
                                                {{ $product->category?->name ?? 'Producto' }}
                                            </p>
                                            <p class="mt-auto pt-1 text-sm font-black text-neutral-900 lg:pt-0.5 lg:text-xs">
                                                {{ $currencySymbol }} {{ number_format((float) ($product->effective_price ?? $product->price_amount), 2) }}
                                            </p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

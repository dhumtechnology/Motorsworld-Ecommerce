@props([
    'popularProducts' => collect(),
    'cartQuantities' => [],
])

@php
    $products = $popularProducts->unique('id')->take(10)->values();
    $carouselId = 'popular-'.uniqid();
@endphp

<section class="w-full bg-white border-t border-neutral-200 select-none font-title" id="{{ $carouselId }}">
    <div class="h-1 w-full bg-primary" aria-hidden="true"></div>
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-14">
        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.2em] text-primary">
                    Destacados
                </p>
                <h2 class="text-xl md:text-2xl font-black uppercase tracking-widest text-neutral-900">
                    Productos populares
                </h2>
            </div>

            @if ($products->isNotEmpty())
                <div class="flex gap-2 shrink-0" data-popular-controls>
                    <button
                        type="button"
                        data-popular-prev
                        class="w-9 h-9 flex items-center justify-center rounded-sm border-2 border-neutral-900 bg-transparent text-neutral-900 hover:bg-neutral-900 hover:text-white transition cursor-pointer"
                        aria-label="Anterior"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        data-popular-next
                        class="w-9 h-9 flex items-center justify-center rounded-sm border-2 border-neutral-900 bg-transparent text-neutral-900 hover:bg-neutral-900 hover:text-white transition cursor-pointer"
                        aria-label="Siguiente"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        @if ($products->isEmpty())
            <p class="text-center text-sm text-neutral-500">Pronto verás aquí los productos más vendidos.</p>
        @else
            <div class="overflow-hidden">
                <div data-popular-track class="flex">
                    @foreach ($products as $popularProduct)
                        @php
                            $brand = $popularProduct->vehicleModel?->brand?->name
                                ?? $popularProduct->category?->name
                                ?? 'Motoworld';
                            $description = \Illuminate\Support\Str::limit(
                                trim((string) ($popularProduct->description ?: $popularProduct->name)),
                                90,
                            );
                            $price = (float) ($popularProduct->effective_price ?? $popularProduct->price_amount);
                            $oldPrice = $popularProduct->is_on_sale
                                ? (float) $popularProduct->list_price
                                : null;
                            $currencySymbol = \App\Support\Currency::symbol($popularProduct->currency ?? 'PEN');
                            $image = $popularProduct->image ?: asset('images/home/product-placeholder.png');
                            $cartQty = (int) ($cartQuantities[$popularProduct->id] ?? 0);
                        @endphp

                        <div class="popular-slide w-1/2 sm:w-1/3 md:w-1/4 lg:w-1/5 shrink-0 px-1.5 sm:px-2 md:px-3" data-real="1">
                            <a
                                href="{{ route('shop.product.show', $popularProduct) }}"
                                class="group flex h-full w-full flex-col overflow-hidden border border-neutral-200 bg-white transition-shadow hover:shadow-md"
                            >
                                <div class="relative aspect-square overflow-hidden bg-neutral-100">
                                    @if ($popularProduct->is_on_sale && $popularProduct->discount_percent)
                                        <span class="absolute top-2 left-2 z-10 bg-primary px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-white rounded-xs">
                                            -{{ rtrim(rtrim(number_format((float) $popularProduct->discount_percent, 2, '.', ''), '0'), '.') }}%
                                        </span>
                                    @elseif ($popularProduct->is_on_sale)
                                        <span class="absolute top-2 left-2 z-10 bg-primary px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-white rounded-xs">
                                            Oferta
                                        </span>
                                    @endif

                                    @if ($cartQty > 0)
                                        <span
                                            class="absolute top-2 right-2 z-10 flex h-7 min-w-[28px] items-center justify-center rounded-full bg-neutral-900 px-2 text-xs font-black text-white shadow-sm"
                                            title="En tu carrito"
                                        >
                                            {{ $cartQty }}
                                        </span>
                                    @endif

                                    <img
                                        src="{{ $image }}"
                                        alt="{{ $popularProduct->name }}"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    >
                                </div>

                                <div class="mt-auto flex min-h-[4.5rem] border-t border-neutral-200">
                                    <div class="flex min-w-0 flex-1 flex-col justify-center p-2 md:p-2.5">
                                        <p class="truncate text-[10px] font-bold uppercase tracking-wider text-neutral-500">
                                            {{ $brand }}
                                        </p>
                                        <p class="mt-0.5 line-clamp-2 text-[11px] font-semibold leading-tight text-neutral-900 md:text-xs">
                                            {{ $description }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-center justify-center bg-primary px-2 text-center sm:px-3">
                                        <span class="whitespace-nowrap text-xs font-black tracking-tight text-white sm:text-sm md:text-base">
                                            {{ $currencySymbol }} {{ number_format($price, 2) }}
                                        </span>
                                        @if ($oldPrice)
                                            <span class="whitespace-nowrap text-[10px] font-semibold text-white/75 line-through sm:text-xs">
                                                {{ $currencySymbol }} {{ number_format($oldPrice, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

@if ($products->isNotEmpty())
    @push('scripts')
        <script>
            (function () {
                const scope = document.getElementById(@json($carouselId));
                if (!scope) return;
                const track = scope.querySelector('[data-popular-track]');
                const controls = scope.querySelector('[data-popular-controls]');
                const prevBtn = scope.querySelector('[data-popular-prev]');
                const nextBtn = scope.querySelector('[data-popular-next]');
                if (!track || !prevBtn || !nextBtn) return;

                const mqLg = window.matchMedia('(min-width: 1024px)');
                const mqMd = window.matchMedia('(min-width: 768px)');
                const mqSm = window.matchMedia('(min-width: 640px)');

                function itemsPerPage() {
                    if (mqLg.matches) return 5;
                    if (mqMd.matches) return 4;
                    if (mqSm.matches) return 3;
                    return 2;
                }

                let perPage = itemsPerPage();
                let buffer = 0;
                let currentIndex = 0;
                let transitioning = false;
                let loopEnabled = false;

                function clearClones() {
                    track.querySelectorAll('.popular-slide[data-clone="1"]').forEach((el) => el.remove());
                }

                function rebuild() {
                    clearClones();
                    const originals = Array.from(track.querySelectorAll('.popular-slide[data-real="1"]'));
                    perPage = itemsPerPage();
                    loopEnabled = originals.length > perPage;
                    buffer = loopEnabled ? perPage : 0;

                    if (controls) {
                        controls.classList.toggle('hidden', originals.length <= perPage);
                    }

                    if (!loopEnabled) {
                        currentIndex = 0;
                        setTransform(false);
                        return;
                    }

                    originals.slice(-buffer).reverse().forEach((el) => {
                        const clone = el.cloneNode(true);
                        clone.removeAttribute('data-real');
                        clone.setAttribute('data-clone', '1');
                        track.insertBefore(clone, track.firstChild);
                    });
                    originals.slice(0, buffer).forEach((el) => {
                        const clone = el.cloneNode(true);
                        clone.removeAttribute('data-real');
                        clone.setAttribute('data-clone', '1');
                        track.appendChild(clone);
                    });

                    currentIndex = buffer;
                    setTransform(false);
                }

                function step() {
                    return 100 / perPage;
                }

                function setTransform(animate) {
                    track.style.transition = animate ? 'transform 350ms ease-out' : 'none';
                    track.style.transform = 'translateX(-' + (currentIndex * step()) + '%)';
                }

                track.addEventListener('transitionend', () => {
                    if (!loopEnabled) {
                        transitioning = false;
                        return;
                    }

                    const totalReal = track.querySelectorAll('.popular-slide[data-real="1"]').length;
                    if (currentIndex >= buffer + totalReal) {
                        currentIndex = buffer;
                        setTransform(false);
                    } else if (currentIndex < buffer) {
                        currentIndex = buffer + totalReal - perPage;
                        setTransform(false);
                    }
                    transitioning = false;
                });

                nextBtn.addEventListener('click', () => {
                    if (transitioning || !loopEnabled) return;
                    transitioning = true;
                    currentIndex++;
                    setTransform(true);
                });
                prevBtn.addEventListener('click', () => {
                    if (transitioning || !loopEnabled) return;
                    transitioning = true;
                    currentIndex--;
                    setTransform(true);
                });

                rebuild();
                window.addEventListener('resize', () => {
                    if (itemsPerPage() !== perPage) rebuild();
                });
            })();
        </script>
    @endpush
@endif

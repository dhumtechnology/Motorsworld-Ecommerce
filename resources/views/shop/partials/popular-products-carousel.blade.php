{{--
    Carrusel productos populares (motos y accesorios).
    Requiere: $popularProducts, $cartQuantities (opcional, por product_id)
--}}
@if (($popularProducts ?? collect())->isNotEmpty())
    @php $carouselId = 'popular-'.uniqid(); @endphp
    <section class="w-full bg-white border-t border-neutral-200 select-none font-title" id="{{ $carouselId }}">
        <div class="h-1 w-full bg-primary" aria-hidden="true"></div>
        <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-14">
            <div class="mb-8 flex items-center justify-between gap-4">
                <div>
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.2em] text-primary">
                        Destacados
                    </p>
                    <h3 class="text-xl md:text-2xl font-black uppercase tracking-widest text-neutral-900">
                        Productos populares
                    </h3>
                </div>

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
            </div>

            <div class="overflow-hidden">
                <div data-popular-track class="flex">
                    @foreach ($popularProducts->unique('id')->take(10) as $popularProduct)
                        <div class="popular-slide w-full sm:w-1/2 lg:w-1/4 shrink-0 px-2 md:px-4 flex justify-center" data-real="1">
                            <x-card
                                class="max-w-[240px] w-full"
                                :title="$popularProduct->name ?? $popularProduct->sku"
                                :category="$popularProduct->category?->name ?? 'Producto'"
                                :price="$popularProduct->effective_price"
                                :oldPrice="$popularProduct->is_on_sale ? $popularProduct->list_price : null"
                                :image="$popularProduct->image ?? 'https://via.placeholder.com/300?text=MotoWorld'"
                                :isSale="$popularProduct->is_on_sale"
                                :discountPercent="$popularProduct->discount_percent"
                                :currency="$popularProduct->currency ?? 'PEN'"
                                :href="route('shop.product.show', $popularProduct)"
                                :cartQty="($cartQuantities[$popularProduct->id] ?? 0)"
                            />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const scope = document.getElementById(@json($carouselId));
            if (!scope) return;
            const track = scope.querySelector('[data-popular-track]');
            const controls = scope.querySelector('[data-popular-controls]');
            const prevBtn = scope.querySelector('[data-popular-prev]');
            const nextBtn = scope.querySelector('[data-popular-next]');
            if (!track || !prevBtn || !nextBtn) return;

            const mq = window.matchMedia('(min-width: 1024px)');
            const mqSm = window.matchMedia('(min-width: 640px)');

            function itemsPerPage() {
                if (mq.matches) return 4;
                if (mqSm.matches) return 2;
                return 1;
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
@endif

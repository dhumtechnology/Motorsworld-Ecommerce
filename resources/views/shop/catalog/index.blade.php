{{--
    Catalogo shop.catalog
    Variables: $products, $section, $filters, $filterOptions, $popularProducts, $cartQuantities
    Filtros: categories[], brands[], models[], price_min, price_max, search, section
--}}
@extends('layouts.shop')

@section('content')
    <div class="min-h-screen py-12 px-4 md:px-8 w-full font-title text-black">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-breadcrumb :items="[
                ['label' => 'NUESTRA TIENDA', 'url' => null],
            ]" />

            <form
                action="{{ url()->current() }}"
                method="GET"
                class="flex w-full sm:w-auto sm:min-w-[280px] items-center gap-1 rounded-full border border-neutral-300 bg-white pl-4 pr-1 py-1 shadow-sm"
            >
                @if (request('section'))
                    <input type="hidden" name="section" value="{{ request('section') }}">
                @endif

                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Buscar producto, marca o categoría…"
                    class="min-w-0 flex-1 border-0 bg-transparent py-1.5 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none ring-0 focus:outline-none focus:ring-0 [&::-webkit-search-cancel-button]:appearance-none"
                >

                <button
                    type="submit"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-neutral-800 transition-colors hover:text-orange-600"
                    aria-label="Buscar"
                    title="Buscar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16 10.5a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
                    </svg>
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
            <div class="lg:col-span-2">
                <form action="{{ url()->current() }}" method="GET" class="flex flex-col gap-6 sticky top-4 h-fit">
                    @if (request('section'))
                        <input type="hidden" name="section" value="{{ request('section') }}">
                    @endif

                    @if (! empty($filters['search']))
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif

                    <x-filters
                        title="Categorías"
                        name="categories"
                        :options="$filterOptions['categories']"
                        :selected="$filters['categories'] ?? []"
                    />

                    <x-price-range
                        :min="$filterOptions['price']['min']"
                        :max="$filterOptions['price']['max']"
                        :value-min="$filters['price_min'] ?? null"
                        :value-max="$filters['price_max'] ?? null"
                    />

                    <x-filters
                        title="Marcas"
                        name="brands"
                        :options="$filterOptions['brands']"
                        :selected="$filters['brands'] ?? []"
                    />

                    @if (! empty($filters['brands']))
                        <x-filters
                            title="Modelos"
                            name="models"
                            :options="$filterOptions['models']"
                            :selected="$filters['models'] ?? []"
                        />
                    @endif

                    <a
                        href="{{ route('shop.catalog', array_filter(['section' => request('section')])) }}"
                        class="inline-flex items-center justify-center rounded-md border border-neutral-300 bg-white px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-neutral-700 transition-colors hover:border-orange-500 hover:text-orange-600"
                    >
                        Limpiar filtros
                    </a>
                </form>
            </div>

            <div class="lg:col-span-8">
                <div
                    id="catalog-product-grid"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-max"
                >
                    @include('shop.catalog._product-cards', [
                        'products' => $products,
                        'cartQuantities' => $cartQuantities,
                    ])
                </div>

                <div
                    id="catalog-infinite-sentinel"
                    class="mt-8 flex min-h-16 w-full flex-col items-center justify-center gap-2"
                    data-has-more="{{ $products->hasMorePages() ? '1' : '0' }}"
                    data-next-page="{{ $products->hasMorePages() ? $products->currentPage() + 1 : '' }}"
                >
                    <div id="catalog-infinite-status" class="hidden py-2 text-center text-sm text-neutral-500" role="status">
                        Cargando más productos…
                    </div>
                    <div id="catalog-infinite-end" class="{{ $products->hasMorePages() ? 'hidden' : '' }} py-2 text-center text-xs uppercase tracking-wider text-neutral-400">
                        No hay más productos
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('shop.partials.popular-products-carousel')

    <script>
        (function () {
            const grid = document.getElementById('catalog-product-grid');
            const sentinel = document.getElementById('catalog-infinite-sentinel');
            const status = document.getElementById('catalog-infinite-status');
            const endMsg = document.getElementById('catalog-infinite-end');
            if (!grid || !sentinel) return;

            let loading = false;
            let hasMore = sentinel.getAttribute('data-has-more') === '1';
            let nextPage = parseInt(sentinel.getAttribute('data-next-page') || '', 10) || null;
            let observer = null;

            function buildUrl(page) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', String(page));
                url.searchParams.set('infinite', '1');
                return url.toString();
            }

            function setFinished() {
                hasMore = false;
                nextPage = null;
                sentinel.setAttribute('data-has-more', '0');
                sentinel.setAttribute('data-next-page', '');
                if (status) status.classList.add('hidden');
                if (endMsg) endMsg.classList.remove('hidden');
                if (observer) observer.disconnect();
                window.removeEventListener('scroll', onScroll);
            }

            async function loadMore() {
                if (loading || !hasMore || !nextPage) return;
                loading = true;
                if (status) status.classList.remove('hidden');

                try {
                    const response = await fetch(buildUrl(nextPage), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok || !contentType.includes('application/json')) {
                        throw new Error('Respuesta inválida al cargar productos');
                    }

                    const data = await response.json();
                    if (data.html && String(data.html).trim() !== '') {
                        grid.insertAdjacentHTML('beforeend', data.html);
                    }

                    if (data.has_more && data.next_page) {
                        hasMore = true;
                        nextPage = Number(data.next_page);
                        sentinel.setAttribute('data-has-more', '1');
                        sentinel.setAttribute('data-next-page', String(nextPage));
                    } else {
                        setFinished();
                    }
                } catch (e) {
                    console.error('[catalog infinite]', e);
                    if (status) {
                        status.textContent = 'No se pudieron cargar más productos.';
                        status.classList.remove('hidden');
                    }
                    setFinished();
                } finally {
                    loading = false;
                    if (hasMore && status) status.classList.add('hidden');
                }
            }

            function nearBottom() {
                const rect = sentinel.getBoundingClientRect();
                return rect.top <= (window.innerHeight + 400);
            }

            function onScroll() {
                if (nearBottom()) loadMore();
            }

            if (!hasMore) {
                if (endMsg) endMsg.classList.remove('hidden');
                return;
            }

            if ('IntersectionObserver' in window) {
                observer = new IntersectionObserver((entries) => {
                    if (entries.some((entry) => entry.isIntersecting)) {
                        loadMore();
                    }
                }, {
                    root: null,
                    rootMargin: '400px 0px',
                    threshold: 0,
                });
                observer.observe(sentinel);
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            // Por si el sentinel ya está visible al cargar
            onScroll();
        })();
    </script>
@endsection

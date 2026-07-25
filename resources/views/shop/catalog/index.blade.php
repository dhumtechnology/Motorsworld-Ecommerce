<!-- {{--
    Catálogo de productos — plantilla base para el equipo frontend.

    Variables disponibles:
    - $products       : LengthAwarePaginator de Product (paginado)
    - $section        : 'accesorios' (default) | 'motos'
    - $filters        : ['categories' => list<int>, 'brands' => list<int>, 'models' => list<int>, 'search' => ?string]
    - $filterOptions  : ['categories' => Collection, 'brands' => Collection, 'models' => Collection]
    - $featuredProducts : Collection de Product (top 3 por unidades vendidas en la sección actual)

    Query string soportado (filtros múltiples):
    - section=motos|accesorios
    - categories[]=1&categories[]=2   (también acepta category=1 legacy)
    - brands[]=3&brands[]=5           (también acepta brand=3 legacy)
    - models[]=10&models[]=12         (también acepta model=10 legacy)
    - search={texto}
    - page={n}

    Rutas de sección (ejemplos):
    - Accesorios (default, excluye categoría MOTOS): {{ route('shop.catalog') }}
    - Motos: {{ route('shop.catalog', ['section' => 'motos']) }}

    Relaciones cargadas en cada producto:
    - category, vehicleModel.brand, inventory, activeOffer

    Atributos directos en cada producto:
    - name, sku, description, price_amount, currency, image

    Precios calculados por el controlador (en cada $product del paginador):
    - effective_price : precio a mostrar/cobrar (oferta activa o precio de lista)
    - list_price      : precio de catálogo (price_amount)
    - sale_price      : precio en oferta; null si no hay oferta activa
    - is_on_sale      : bool — true si tiene oferta vigente
    - offer_ends_at   : Carbon|null — fin de la oferta activa
    - offer_starts_at : Carbon|null — inicio de la oferta activa
    - discount_percent: float|null — % de descuento de la oferta activa
    - offer_reason    : string|null — motivo de la oferta activa
    - offer           : array|null — payload completo de la oferta activa (ver abajo)

    Payload $product->offer (null si no hay oferta vigente):
    - id, product_id
    - offer_price_amount   : string (precio final de oferta)
    - discount_percent     : float
    - reason               : string|null
    - currency             : string (PEN)
    - starts_at / ends_at  : ISO-8601 string|null
    - starts_at_formatted / ends_at_formatted : d/m/Y H:i|null
    - is_active            : bool
    - lifecycle_status     : 'active'|'scheduled'|'expired'

    Relación activeOffer (ProductOffer|null) — modelo Eloquent:
    - $product->activeOffer->offer_price_amount
    - $product->activeOffer->discount_percent
    - $product->activeOffer->reason
    - $product->activeOffer->starts_at / ends_at
    - $product->activeOffer->currency
    - $product->activeOffer->resolvedDiscountPercent()

    Helpers útiles en Product:
    - $product->hasAvailableStock() : bool
    - $product->hasActiveOffer()    : bool
    - $product->currentPricing()    : ProductPricing (unitPrice, listUnitPrice, hasOffer())

    Ejemplo x-card con datos reales:
    - title     => $product->name
    - category  => $product->category->name
    - price     => $product->effective_price
    - oldPrice  => $product->is_on_sale ? $product->list_price : null
    - isSale    => $product->is_on_sale
    - href      => route('shop.product.show', $product)
    - image     => $product->image ?? 'url-placeholder'

    Ejemplo oferta / descuento en Blade:
    if ($product->is_on_sale && $product->offer):
        -{{ $product->discount_percent }}%
        Motivo: {{ $product->offer['reason'] }}
        Oferta: {{ number_format($product->sale_price, 2) }} PEN
        Antes: {{ number_format($product->list_price, 2) }} PEN
        Hasta: {{ $product->offer_ends_at?->format('d/m/Y') }}
        También: $product->offer['discount_percent'], $product->offer['ends_at_formatted']
    else:
        {{ number_format($product->effective_price, 2) }} {{ $product->currency }}
    endif

    =============================================================================
    CARRITO — se agrega solo desde el detalle de producto (no desde el catálogo)
    =============================================================================

    Icono del header → vista shop.cart.index (GET /carrito)
    Badge del icono: cantidad total de unidades en el carrito

    Rutas de acción (POST/PATCH, @csrf). Redirect back + flash:
    session('cart_status'), session('cart_summary')

    | Acción              | Ruta                           | Body                    |
    |---------------------|--------------------------------|-------------------------|
    | Agregar 1ª vez      | POST shop.cart.items.store     | quantity? (default 1)   |
    | Botón +             | POST shop.cart.items.increment | —                       |
    | Botón −             | POST shop.cart.items.decrement | — (0 quita la línea)    |
    | Ver carrito         | GET  shop.cart.index           | —                       |

    Validación: producto active, stock en inventory.available_stock.
--}} -->

@extends('layouts.shop')

@section('content')
    {{--
        Ofertas / precios por producto (CatalogController + ProductOfferPresenter):
        - $product->effective_price   → precio a mostrar
        - $product->list_price        → precio de catálogo
        - $product->sale_price        → precio en oferta (null si no aplica)
        - $product->is_on_sale        → bool
        - $product->discount_percent  → % descuento (null si no hay oferta)
        - $product->offer_reason      → motivo (null si no hay oferta)
        - $product->offer_starts_at / offer_ends_at → Carbon|null
        - $product->offer             → array completo|null
            id, product_id, offer_price_amount, discount_percent, reason, currency,
            starts_at, ends_at, starts_at_formatted, ends_at_formatted,
            is_active, lifecycle_status
        - $product->activeOffer       → ProductOffer|null (Eloquent)

        Ejemplo descuento:
        if ($product->is_on_sale):
            -{{ number_format($product->discount_percent, 0) }}%
            {{ $product->offer['reason'] ?? '' }}
        endif

        x-card sugerido:
        :title="$product->name"
        :category="$product->category->name"
        :price="$product->effective_price"
        :oldPrice="$product->is_on_sale ? $product->list_price : null"
        :isSale="$product->is_on_sale"
        :discountPercent="$product->discount_percent"
        :href="route('shop.product.show', $product)"
        :image="$product->image"
        --}}
    <div class="min-h-screen py-12 px-4 md:px-8 w-full font-title text-black">
        <div>
            <x-breadcrumb :items="[
                'Catálogo' => null
            ]" />
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
            <div class="lg:col-span-2">
                <form action="{{ url()->current() }}" method="GET" class="flex flex-col gap-6 sticky top-4 h-fit">
                    
                    @if(request('section'))
                        <input type="hidden" name="section" value="{{ request('section') }}">
                    @endif
        
                    <div class="p-6 rounded-md border-neutral-800 text-black">
                        <h3 class="font-secondary font-black tracking-wider uppercase text-xl mb-4 antialiased">
                            BÚSQUEDA
                        </h3>
                        <input type="search" 
                            name="search" 
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Buscar..." 
                            class="w-full px-4 py-2.5 text-black rounded border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                            onkeypress="if(event.key === 'Enter') this.form.submit();"
                            onsearch="if(this.value === '') this.form.submit();"> {{-- Al borrar el texto y darle Enter o vaciarlo, recarga con todo --}}
                    </div>
        
                    <x-filters
                        title="CATEGORÍAS"
                        name="categories"
                        :options="$filterOptions['categories']"
                        :selected="$filters['categories'] ?? []"
                    />
        
                    <x-filters
                        title="MARCAS"
                        name="brands"
                        :options="$filterOptions['brands']"
                        :selected="$filters['brands'] ?? []"
                    />
        
                </form>
            </div>
            <div class="lg:col-span-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-max">
                    @forelse ($products as $product)
                        <x-card
                            :title="$product->name ?? $product->sku"
                            :category="$product->category?->name ?? 'MOTO'"
                            :price="$product->effective_price"
                            :oldPrice="$product->is_on_sale ? $product->list_price : null"
                            :image="$product->image ?? 'https://via.placeholder.com/300?text=MotoWorld'"
                            :isSale="$product->is_on_sale"
                            :discountPercent="$product->discount_percent"
                            :href="route('shop.product.show', $product)"
                            :cartQty="$cartQuantities[$product->id] ?? 0"
                        /> 
                    @empty
                        <div class="col-span-1 md:col-span-2 lg:col-span-4 text-center py-2 text-gray-400">
                            <span class="text-3xl">🏍️</span>
                            <p class="mt-2 text-sm">No se encontraron productos disponibles en este momento.</p>
                        </div>
                    @endforelse  
                </div>
                <div>
                    @if($products->hasPages())
                        <div>
                            {{ $products->links('vendor.pagination.tailwind') }}
                        </div>
                    @endif
                </div>
            </div>    
        </div>
        <div>
            @if($featuredProducts->isNotEmpty())
                <section class="mt-16 mb-10 select-none font-title w-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-black uppercase tracking-widest text-black">
                            Productos Destacados
                        </h3>

                        <div class="flex gap-2">
                            <button id="featured-prev" type="button" class="w-9 h-9 flex items-center justify-center bg-primary text-white rounded-sm hover:opacity-90 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button id="featured-next" type="button" class="w-9 h-9 flex items-center justify-center bg-primary text-white rounded-sm hover:opacity-90 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="featured-carousel" class="overflow-hidden">
                        <div id="featured-track" class="flex">
                            @foreach ($featuredProducts->take(10) as $featuredProduct)
                                <div class="featured-slide w-1/4 shrink-0 px-8 flex justify-center">
                                    <x-card
                                        class="max-w-[200px] w-full"
                                        :title="$featuredProduct->name ?? $featuredProduct->sku"
                                        :category="$featuredProduct->category?->name ?? 'MOTO'"
                                        :price="$featuredProduct->effective_price"
                                        :oldPrice="$featuredProduct->is_on_sale ? $featuredProduct->list_price : null"
                                        :image="$featuredProduct->image ?? 'https://via.placeholder.com/300?text=MotoWorld'"
                                        :isSale="$featuredProduct->is_on_sale"
                                        :discountPercent="$featuredProduct->discount_percent"
                                        :href="route('shop.product.show', $featuredProduct)"
                                        :cartQty="$cartQuantities[$featuredProduct->id] ?? 0"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <script>
                    (function () {
                        const track = document.getElementById('featured-track');
                        const prevBtn = document.getElementById('featured-prev');
                        const nextBtn = document.getElementById('featured-next');

                        const itemsPerPage = 4;
                        const originalSlides = Array.from(track.children);
                        const totalReal = originalSlides.length;

                        // Buffer de clones para poder loopear en ambas direcciones
                        const buffer = itemsPerPage;

                        const startClones = originalSlides.slice(-buffer).map(el => el.cloneNode(true));
                        const endClones = originalSlides.slice(0, buffer).map(el => el.cloneNode(true));

                        startClones.forEach(clone => track.insertBefore(clone, track.firstChild));
                        endClones.forEach(clone => track.appendChild(clone));

                        const step = 100 / itemsPerPage;
                        let currentIndex = buffer; // arranca mostrando el primer producto real

                        function setTransform(animate) {
                            track.style.transition = animate ? 'transform 300ms ease-out' : 'none';
                            track.style.transform = `translateX(-${currentIndex * step}%)`;
                        }

                        function goNext() {
                            currentIndex++;
                            setTransform(true);
                        }

                        function goPrev() {
                            currentIndex--;
                            setTransform(true);
                        }

                        track.addEventListener('transitionend', () => {
                            if (currentIndex >= buffer + totalReal) {
                                currentIndex = buffer;
                                setTransform(false);
                            } else if (currentIndex < buffer) {
                                currentIndex = buffer + totalReal - 1;
                                setTransform(false);
                            }
                        });

                        nextBtn.addEventListener('click', goNext);
                        prevBtn.addEventListener('click', goPrev);

                        // Set inicial sin animación
                        setTransform(false);
                    })();
                </script>
            @endif
        </div>
        

    </div>
@endsection
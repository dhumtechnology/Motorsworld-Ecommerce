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

    Relación activeOffer (ProductOffer|null):
    - $product->activeOffer->offer_price_amount
    - $product->activeOffer->starts_at / ends_at
    - $product->activeOffer->currency

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

    Ejemplo precio manual en Blade:
    @if ($product->is_on_sale)
        Oferta: {{ number_format($product->sale_price, 2) }} {{ $product->currency }}
        Antes: {{ number_format($product->list_price, 2) }}
        Hasta: {{ $product->offer_ends_at?->format('d/m/Y') }}
    @else
        {{ number_format($product->effective_price, 2) }} {{ $product->currency }}
    @endif

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
        Ofertas / precios por producto (CatalogController):
        - $product->effective_price  → precio a mostrar
        - $product->list_price       → precio de catálogo
        - $product->sale_price       → precio en oferta (null si no aplica)
        - $product->is_on_sale       → bool
        - $product->offer_ends_at    → fin de oferta activa
        - $product->activeOffer      → relación ProductOffer|null

        x-card sugerido:
        :title="$product->name"
        :category="$product->category->name"
        :price="$product->effective_price"
        :oldPrice="$product->is_on_sale ? $product->list_price : null"
        :isSale="$product->is_on_sale"
        :href="route('shop.product.show', $product)"
        :image="$product->image"
    --}}
    <h1>Catálogo de productos</h1>
    <div class="bg-[#252525] min-h-screen py-12 px-4 md:px-12 w-full grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 auto-rows-max">
           @forelse ($products as $product)
                <x-card
                    :title="$product->name ?? $product->sku"
                    :category="$product->category?->name ?? 'MOTO'"
                    :price="$product->effective_price"
                    :oldPrice="$product->is_on_sale ? $product->list_price : null"
                    :image="$product->image ?? 'https://via.placeholder.com/300?text=MotoWorld'"
                    :isSale="$product->is_on_sale"
                    :href="route('shop.product.show', $product)"
                    :cartQty="$cartQuantities[$product->id] ?? 0"
                /> 
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12 text-gray-400">
                    <span class="text-3xl">🏍️</span>
                    <p class="mt-2 text-sm">No se encontraron productos disponibles en este momento.</p>
                </div>
            @endforelse

            @if($products->hasPages())
                <div>
                    {{ $products->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

        <div class="lg:col-span-3">
            <form action="{{ url()->current() }}" method="GET" class="flex flex-col gap-6 sticky top-4 h-fit">
                
                @if(request('section'))
                    <input type="hidden" name="section" value="{{ request('section') }}">
                @endif

                <div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white">
                    <h3 class="font-sans font-black tracking-wider uppercase text-xl mb-4 antialiased">
                        BÚSQUEDA
                    </h3>
                    <input type="search" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Buscar..." 
                        class="w-full px-4 py-2.5 bg-[#151515] text-gray-300 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
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

                <div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white">
                    <h3 class="font-sans font-black tracking-wider uppercase text-xl antialiased mb-4">
                        FILTRAR POR PRECIO
                    </h3>
                    
                    {{-- Botón inteligente de Limpieza Total --}}
                    @if(
                        ($filters['categories'] ?? []) !== []
                        || ($filters['brands'] ?? []) !== []
                        || ($filters['models'] ?? []) !== []
                        || filled($filters['search'] ?? null)
                    )
                        <a href="{{ url()->current() }}{{ request('section') ? '?section='.request('section') : '' }}" 
                        class="inline-block mt-2 text-xs font-bold text-orange-500 hover:text-orange-600 uppercase tracking-widest transition-colors">
                            ✕ Limpiar Filtros
                        </a>
                    @endif
                </div>

                @if($featuredProducts->isNotEmpty())
                    <div class="mt-10 select-none font-sans">
                        <h3 class="text-xl font-black uppercase tracking-widest text-white mb-6">
                            Productos Destacados
                        </h3>

                        <div class="space-y-5">
                            @foreach ($featuredProducts as $featuredProduct)
                                <div class="flex items-center gap-4 group cursor-pointer">
                                    <div class="relative w-20 h-20 bg-[#1e1e1e] border border-neutral-800 rounded-sm overflow-hidden shrink-0 flex items-center justify-center p-1">
                                        @if($featuredProduct->is_on_sale)
                                            <span class="absolute top-1 left-1 bg-[#f15a24] text-white text-[9px] font-black uppercase tracking-wider px-1 py-0.5 rounded-xs z-10">
                                                Sale
                                            </span>
                                        @endif
                                        <img src="{{ $featuredProduct->image ?? 'https://via.placeholder.com/150?text=MotoWorld' }}"
                                            class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-200"
                                            alt="{{ $featuredProduct->name }}">
                                    </div>

                                    <div class="flex flex-col justify-center">
                                        <h4 class="text-sm font-black text-white uppercase tracking-wide group-hover:text-[#f15a24] transition-colors duration-150 leading-tight">
                                            {{ $featuredProduct->name ?? $featuredProduct->sku }}
                                        </h4>
                                        <span class="text-xs font-bold text-neutral-500 mt-0.5 uppercase tracking-wider">
                                            {{ $featuredProduct->category?->name ?? 'MOTO' }}
                                        </span>

                                        <div class="flex items-baseline gap-2 mt-1">
                                            <span class="text-sm font-black text-white">
                                                ${{ $featuredProduct->effective_price }}
                                            </span>

                                            @if($featuredProduct->is_on_sale)
                                                <span class="text-xs font-bold text-neutral-500 line-through">
                                                    ${{ $featuredProduct->list_price }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </form>
        </div>
    </div>
@endsection
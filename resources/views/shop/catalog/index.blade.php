<!-- {{--
    Catálogo de productos — plantilla base para el equipo frontend.

    Variables disponibles:
    - $products       : LengthAwarePaginator de Product (paginado)
    - $section        : 'accesorios' (default) | 'motos'
    - $filters        : ['categories' => list<int>, 'brands' => list<int>, 'models' => list<int>, 'search' => ?string]
    - $filterOptions  : ['categories' => Collection, 'brands' => Collection, 'models' => Collection]

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
    <div class="bg-[#151515] min-h-screen py-12 px-4 md:px-12 w-full grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 auto-rows-max">
           @forelse ($products as $product)
                <x-card
                    :title="$product->name ?? $product->sku" {{-- Usa el nombre real que el controlador busca en el Like --}}
                    :category="$product->category?->name ?? 'MOTO'" {{-- Mapea la relación cargada con ->with() --}}
                    :price="$product->effective_price" {{-- Tu controlador calcula este como el precio final --}}
                    :oldPrice="$product->is_on_sale ? $product->list_price : null" {{-- Si está en oferta, muestra el precio de lista original --}}
                    :image="$product->image ?? 'https://via.placeholder.com/300?text=MotoWorld'"
                    :isSale="$product->is_on_sale" {{-- Se activa directo con el boolean que genera tu método withActiveOfferPricing --}}
                    :href="route('shop.product.show', $product)"
                /> 
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12 text-gray-400">
                    <span class="text-3xl">🏍️</span>
                    <p class="mt-2 text-sm">No se encontraron productos disponibles en este momento.</p>
                </div>
            @endforelse
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
                    name="category"
                    :options="$filterOptions['categories']"
                    :selected="$filters['category']" {{-- Tu controlador ya maneja arrays o strings aquí --}}
                />

                <x-filters
                    title="MARCAS"
                    name="brand"
                    :options="$filterOptions['brands']"
                    :selected="$filters['brand']"
                />

                <div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white">
                    <h3 class="font-sans font-black tracking-wider uppercase text-xl antialiased mb-4">
                        FILTRAR POR PRECIO
                    </h3>
                    
                    {{-- Botón inteligente de Limpieza Total --}}
                    @if(request('category') || request('brand') || request('search') || request('model'))
                        <a href="{{ url()->current() }}{{ request('section') ? '?section='.request('section') : '' }}" 
                        class="inline-block mt-2 text-xs font-bold text-orange-500 hover:text-orange-600 uppercase tracking-widest transition-colors">
                            ✕ Limpiar Filtros
                        </a>
                    @endif
                </div>

            </form>
        </div>
    </div>
@endsection
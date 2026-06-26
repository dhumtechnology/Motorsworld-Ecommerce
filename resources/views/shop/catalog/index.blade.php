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
                {{--
                    Datos del producto para esta iteración:
                    - $product->name, $product->sku, $product->description
                    - $product->price_amount, $product->currency, $product->image
                    - $product->category, $product->vehicleModel, $product->inventory
                    - $product->hasAvailableStock()

                    Ofertas (si is_on_sale es true):
                    - $product->sale_price vs $product->list_price
                    - $product->activeOffer->offer_price_amount
                    - $product->offer_ends_at
                --}}
                <!-- <article>
                    <h2>
                        <a href="{{ route('shop.product.show', $product) }}">{{ $product->sku }}</a>
                    </h2>
                    @if ($product->description)
                        <p>{{ $product->description }}</p>
                    @endif
                    {{-- price_amount, currency, image, category, vehicleModel, inventory --}}
                    {{-- $product->hasAvailableStock() --}}
                </article> -->
                <x-card
                    title="Producto de ejemplo"
                    category="Categoría de ejemplo"
                    price="100"
                    oldPrice="150"
                    image="https://images.ctfassets.net/8zlbnewncp6f/2fH3mKeHaSrfHQlEsm2xxt/c9c0202dc9333bd05422552a3a14e34b/Galeria2_Galgo_Chile.jpg?w=600&fm=webp&q=90"
                    isSale="true"
                    href="#"
                /> 
            @empty
                {{-- Sin productos --}}
            @endforelse
        </div>

        <div class="lg:col-span-3 flex flex-col gap-6 sticky top-4 h-fit">
            
            <div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white">
                <h3 class="font-sans font-black tracking-wider uppercase text-xl mb-4 antialiased">
                    BÚSQUEDA
                </h3>
                <input type="text" placeholder="Buscar..." 
                    class="w-full px-4 py-2.5 bg-[#151515] text-gray-300 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm">
            </div>

            <x-filters
                title="CATEGORÍAS"
                :options="[
                    1 => 'Tires & Wheels',
                    2 => 'Tires & Wheels',
                    3 => 'Tires & Wheels',
                    4 => 'Tires & Wheels',
                    5 => 'Tires & Wheels',
                    6 => 'Tires & Wheels',
                ]"
            />

            <x-filters
                title="MARCAS"
                :options="[
                    1 => 'Tires & Wheels',
                    2 => 'Tires & Wheels',
                    3 => 'Tires & Wheels',
                ]"
            />

            <div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white">
                <h3 class="font-sans font-black tracking-wider uppercase text-xl antialiased">
                    FILTRAR POR PRECIO
                </h3>
            </div>
        </div>
    </div>
@endsection
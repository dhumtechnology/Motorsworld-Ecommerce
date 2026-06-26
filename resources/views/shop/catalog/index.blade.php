<!-- {{--
    Catálogo de productos — plantilla base para el equipo frontend.

    Variables disponibles:
    - $products       : LengthAwarePaginator de Product (paginado)
    - $section        : 'accesorios' (default) | 'motos'
    - $filters        : ['category' => ?int, 'brand' => ?int, 'model' => ?int, 'search' => ?string]
    - $filterOptions  : ['categories' => Collection, 'brands' => Collection, 'models' => Collection]

    Query string soportado:
    - section=motos|accesorios
    - category={id}
    - brand={id}
    - model={id}
    - search={texto}
    - page={n}

    Rutas de sección (ejemplos):
    - Accesorios (default, excluye categoría MOTOS): {{ route('shop.catalog') }}
    - Motos: {{ route('shop.catalog', ['section' => 'motos']) }}

    Relaciones cargadas en cada producto:
    - category, vehicleModel.brand, inventory

    Atributos directos en cada producto:
    - sku, description, price_amount, currency, image

    Helpers útiles en Product:
    - $product->hasAvailableStock() : bool
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo — {{ config('app.name') }}</title>
</head>
<body>
    {{-- Navegación de sección: implementar UI --}}
    {{-- Accesorios: route('shop.catalog', array_merge(request()->query(), ['section' => 'accesorios', 'page' => null])) --}}
    {{-- Motos: route('shop.catalog', array_merge(request()->query(), ['section' => 'motos', 'page' => null])) --}}

    {{-- Filtros: $filterOptions['categories'], $filterOptions['brands'], $filterOptions['models'] --}}
    {{-- Valores activos: $filters --}}

    
    {{-- Paginación: $products->links() --}}
</body>
</html> -->

@extends('layouts.shop')

@section('content')
    <h1>Catálogo de productos</h1>
    <div class="bg-[#151515] min-h-screen py-12 px-4 md:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($products as $product)
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
        <div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white sticky top-4">
            <x-filters
                title="CATEGORÍAS"
                :options="[
                    1 => 'Categoría 1',
                    2 => 'Categoría 2',
                    3 => 'Categoría 3',
                ]"
            />
        </div>
    </div>
@endsection
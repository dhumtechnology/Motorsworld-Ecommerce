{{--
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

    @forelse ($products as $product)
        <article>
            <h2>
                <a href="{{ route('shop.product.show', $product) }}">{{ $product->sku }}</a>
            </h2>
            @if ($product->description)
                <p>{{ $product->description }}</p>
            @endif
            {{-- price_amount, currency, image, category, vehicleModel, inventory --}}
            {{-- $product->hasAvailableStock() --}}
        </article>
    @empty
        {{-- Sin productos --}}
    @endforelse

    {{-- Paginación: $products->links() --}}
    <h1>Catalogo de productos</h1>
</body>
</html>

{{--
    Detalle de producto — plantilla base para el equipo frontend.

    Variable disponible:
    - $product : Product (solo status active; 404 en otros casos)

    Relaciones cargadas:
    - $product->category       : Category (id, name, description)
    - $product->vehicleModel   : VehicleModel|null (id, name, brand_id)
    - $product->vehicleModel->brand : Brand|null (id, name, image)
    - $product->inventory      : Inventory|null (total_stock, available_stock, reserved_stock)

    Atributos directos del producto:
    - $product->sku
    - $product->description
    - $product->price_amount
    - $product->currency
    - $product->status          : ProductStatus enum
    - $product->image
    - $product->created_at
    - $product->updated_at

    Helpers:
    - $product->hasAvailableStock() : bool

    Rutas relacionadas:
    - Volver al catálogo: {{ route('shop.catalog') }}
    - Este producto: {{ route('shop.product.show', $product) }}
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->sku }} — {{ config('app.name') }}</title>
</head>
<body>
    <nav>
        <a href="{{ route('shop.catalog') }}">← Catálogo</a>
    </nav>

    <article>
        @if ($product->image)
            {{-- Imagen: $product->image --}}
        @endif

        <h1>{{ $product->sku }}</h1>

        @if ($product->description)
            <p>{{ $product->description }}</p>
        @endif

        <p>{{ number_format($product->price_amount, 2) }} {{ $product->currency }}</p>

        <dl>
            <dt>SKU</dt>
            <dd>{{ $product->sku }}</dd>

            <dt>Estado</dt>
            <dd>{{ $product->status->value }}</dd>

            <dt>Categoría</dt>
            <dd>{{ $product->category->name }}</dd>

            @if ($product->category->description)
                <dt>Descripción categoría</dt>
                <dd>{{ $product->category->description }}</dd>
            @endif

            @if ($product->vehicleModel)
                <dt>Modelo</dt>
                <dd>{{ $product->vehicleModel->name }}</dd>
            @endif

            @if ($product->vehicleModel?->brand)
                <dt>Marca</dt>
                <dd>{{ $product->vehicleModel->brand->name }}</dd>
            @endif

            @if ($product->inventory)
                <dt>Stock disponible</dt>
                <dd>{{ $product->inventory->available_stock }}</dd>

                <dt>Stock total</dt>
                <dd>{{ $product->inventory->total_stock }}</dd>

                <dt>Stock reservado</dt>
                <dd>{{ $product->inventory->reserved_stock }}</dd>
            @else
                <dt>Stock</dt>
                <dd>Sin registro de inventario</dd>
            @endif

            <dt>Disponible para compra</dt>
            <dd>{{ $product->hasAvailableStock() ? 'Sí' : 'No' }}</dd>
        </dl>
    </article>
</body>
</html>

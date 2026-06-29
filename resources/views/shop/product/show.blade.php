{{--
    Detalle de producto — documentación para el equipo frontend.
    El HTML inferior es maqueta estática; conectar con las variables descritas aquí.

    =============================================================================
    VARIABLES DEL CONTROLADOR (ProductController@show)
    =============================================================================

    - $product         : Product (solo status active; 404 en otros casos)
    - $reviews         : Collection<int, Comment> — reseñas del producto, más recientes primero
    - $reviewSummary   : ['count' => int, 'average_stars' => float|null]

    =============================================================================
    RELACIONES CARGADAS EN $product
    =============================================================================

    - $product->category              : Category (id, name, description)
    - $product->vehicleModel          : VehicleModel|null (id, name, brand_id)
    - $product->vehicleModel->brand   : Brand|null (id, name, image)
    - $product->inventory             : Inventory|null (total_stock, available_stock, reserved_stock)
    - $product->images                : Collection<int, ProductImage> ordenadas por sort_order
    - $product->activeOffer           : ProductOffer|null (oferta vigente más barata)
    - $product->reviews               : alias de $reviews (misma colección)

    Cada ProductImage:
    - id, product_id, path, sort_order, is_primary (bool)

    =============================================================================
    ATRIBUTOS DIRECTOS DEL PRODUCTO (columnas en inglés, coherente con la BD)
    =============================================================================

    - $product->sku
    - $product->name
    - $product->description              → pestaña "Descripción"
    - $product->additional_information   → pestaña "Información Adicional" (texto plano / multilínea)
    - $product->price_amount
    - $product->currency
    - $product->status                   : ProductStatus enum
    - $product->image                    → URL principal (compatibilidad catálogo; misma que imagen is_primary)
    - $product->created_at
    - $product->updated_at

    =============================================================================
    PRECIOS CALCULADOS (misma convención que el catálogo)
    =============================================================================

    - $product->effective_price  → precio a mostrar / cobrar
    - $product->list_price       → precio de catálogo (price_amount)
    - $product->sale_price       → precio en oferta; null si no aplica
    - $product->is_on_sale       → bool
    - $product->offer_ends_at    → Carbon|null

    Oferta activa (relación):
    - $product->activeOffer->offer_price_amount
    - $product->activeOffer->starts_at / ends_at

    =============================================================================
    GALERÍA DE IMÁGENES
    =============================================================================

    Usar $product->images (todas las fotos). Miniaturas + imagen principal:

    @foreach ($product->images as $image)
        {{ $image->path }}
        {{ $image->is_primary ? 'principal' : 'secundaria' }}
    @endforeach

    Fallback si no hay filas en product_images:
    {{ $product->image ?? 'url-placeholder' }}

    =============================================================================
    RESEÑAS ($reviews / pestaña "Reviews")
    =============================================================================

    Tabla: comments (product_id enlaza reseña ↔ producto).

    Cada $review (Comment):
    - $review->id
    - $review->comment          → texto de la reseña
    - $review->stars            → int 1–5
    - $review->created_at       → Carbon
    - $review->user             : User
    - $review->user->customerProfile : CustomerProfile|null
        - first_name, last_name (mostrar nombre del autor)
        - avatar (opcional)

    Resumen agregado:
    - $reviewSummary['count']
    - $reviewSummary['average_stars']   → null si no hay reseñas

    Ejemplo estrellas:
    @forelse ($reviews as $review)
        {{ $review->user->customerProfile?->first_name }} {{ $review->user->customerProfile?->last_name }}
        {{ $review->stars }}/5
        {{ $review->comment }}
        {{ $review->created_at->format('d/m/Y') }}
    @empty
        Sin reseñas aún.
    @endforelse

    =============================================================================
    HELPERS
    =============================================================================

    - $product->hasAvailableStock() : bool
    - $product->hasActiveOffer()    : bool
    - $product->currentPricing()    : ProductPricing

    =============================================================================
    RUTAS
    =============================================================================

    - Catálogo: {{ route('shop.catalog') }}
    - Este producto: {{ route('shop.product.show', $product) }}

    =============================================================================
    EJEMPLOS RÁPIDOS PARA LA MAQUETA
    =============================================================================

    Título:     {{ $product->name }}
    Categoría:  {{ $product->category->name }}
    SKU:        {{ $product->sku }}
    Precio:     ${{ number_format($product->effective_price, 0, '.', '') }}
    Tachado:    @if($product->is_on_sale) ${{ number_format($product->list_price, 0, '.', '') }} @endif
    Descripción: {!! nl2br(e($product->description)) !!}
    Info extra:  {!! nl2br(e($product->additional_information)) !!}
--}}

@extends('layouts.shop')

@section('content')
    <div>
        <div>
            -- bradcrumb
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 text-white max-w-[95%] mx-auto p-4 select-none font-sans">
            <div class="lg:col-span-7 flex flex-col sm:flex-row gap-4 h-fit">        
                <div class="flex flex-row sm:flex-col gap-3 shrink-0 w-full sm:w-36">   
                    <div class="w-20 h-20 sm:w-36 sm:h-30 border-2 border-[#f15a24] bg-[#000000] rounded-sm overflow-hidden cursor-pointer transition-colors">
                        <img src="https://diggerdetailing.co.nz/wp-content/uploads/2022/08/product-10.png" class="w-full h-full object-contain p-1" alt="Miniatura 1">
                    </div>

                    <div class="w-20 h-20 sm:w-36 sm:h-30 border border-neutral-800 bg-[#000000] rounded-sm overflow-hidden cursor-pointer hover:border-neutral-600 transition-colors">
                        <img src="https://diggerdetailing.co.nz/wp-content/uploads/2022/08/product-10.png" class="w-full h-full object-contain p-1" alt="Miniatura 2">
                    </div>

                    <div class="w-20 h-20 sm:w-36 sm:h-30 border border-neutral-800 bg-[#000000] rounded-sm overflow-hidden cursor-pointer hover:border-neutral-600 transition-colors">
                        <img src="https://diggerdetailing.co.nz/wp-content/uploads/2022/08/product-10.png" class="w-full h-full object-contain p-1" alt="Miniatura 3">
                    </div>

                    <div class="w-20 h-20 sm:w-36 sm:h-30 border border-neutral-800 bg-[#000000] rounded-sm overflow-hidden cursor-pointer hover:border-neutral-600 transition-colors">
                        <img src="https://diggerdetailing.co.nz/wp-content/uploads/2022/08/product-10.png" class="w-full h-full object-contain p-1" alt="Miniatura 4">
                    </div>
                </div>

                <div class="flex-1 bg-[#000000] rounded-sm p-6 flex items-center justify-center border border-neutral-800 min-h-[350px] lg:min-h-[480px]">
                    <img src="https://diggerdetailing.co.nz/wp-content/uploads/2022/08/product-10.png" class="max-w-full max-h-[440px] object-contain" alt="Imagen principal">
                </div>
            </div>
      
            <div class="lg:col-span-5 flex flex-col justify-start font-sans">
                <h3 class="text-3xl font-black tracking-wide uppercase leading-tight antialiased">
                    Tendicatena Tension Roller
                </h3>
                <h5 class="text-sm font-bold tracking-widest text-neutral-400 mt-1 uppercase">
                    ROLLER
                </h5>
                <div class="mt-6 space-y-1 text-sm text-neutral-400 font-medium">
                    <p>Categories: Automatic Mechanic</p>
                    <p>SKU: 123456</p>
                    <p>Tags: Energy Speed System</p>
                </div>
                <div class="my-6 flex items-baseline gap-4">
                    <span class="text-2xl font-black tracking-tight text-white">$69000</span>
                    <span class="text-sm font-bold text-neutral-500 line-through">$76000</span>
                </div>
                <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white select-none overflow-hidden rounded-sm">
                    <button type="button" class="w-12 h-full flex items-center justify-center bg-white text-orange-600 font-sans font-black text-2xl focus:outline-none">
                        -
                    </button>
                    <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center">
                        <input type="number" value="1" class="w-full bg-transparent text-center text-white font-sans font-black text-lg focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    </div>
                    <button type="button" class="w-12 h-full flex items-center justify-center bg-white text-orange-600 font-sans font-black text-xl focus:outline-none">
                        +
                    </button>
                </div>
            </div>
        </div>

        <div class="w-full px-10 py-5 text-white font-sans mt-12 select-none">
    
            <div class="flex flex-wrap items-center gap-x-8 border-b border-neutral-800">
                
                <button type="button" class="pb-3 text-2xl font-black uppercase tracking-wide text-white border-b-2 border-[#f15a24] focus:outline-none transition-colors duration-150">
                    Descripción
                </button>

                <button type="button" class="pb-3 text-2xl font-black uppercase tracking-wide text-neutral-400 hover:text-white border-b-2 border-transparent hover:border-[#f15a24]/50 focus:outline-none transition-colors duration-150">
                    Información Adicional
                </button>

                <button type="button" class="pb-3 text-2xl font-black uppercase tracking-wide text-neutral-400 hover:text-white border-b-2 border-transparent hover:border-[#f15a24]/50 focus:outline-none transition-colors duration-150">
                    Reviews
                </button>

            </div>

            <div class="mt-8 text-neutral-300 text-sm leading-relaxed max-w-5xl">
                
                <div id="tab-content-description" class="block">
                    <p class="mb-4">
                        It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout, it is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-neutral-400">
                        <li>The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters.</li>
                        <li>Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text.</li>
                        <li>Various versions have evolved over the years, sometimes by accident, sometimes on purpose.</li>
                        <li>Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text.</li>
                        <li>Various versions have evolved over the years, sometimes by accident, sometimes on purpose.</li>
                    </ul>
                </div>

            </div>

        </div>
    </div>

@endsection

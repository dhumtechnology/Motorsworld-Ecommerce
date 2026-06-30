{{--
    Detalle de producto — documentación para el equipo frontend.
    El HTML inferior es maqueta estática; conectar con las variables descritas aquí.

    =============================================================================
    VARIABLES DEL CONTROLADOR (ProductController@show)
    =============================================================================

    - $product         : Product (solo status active; 404 en otros casos)
    - $reviews         : Collection<int, Comment> — reseñas del producto, más recientes primero
    - $reviewSummary   : ['count' => int, 'average_stars' => float|null]
    - $relatedProducts : Collection<int, Product> — hasta 8 productos relacionados (ver algoritmo abajo)
    - $cartLineQuantity : int — unidades de este producto ya en el carrito (0 si no está)

    =============================================================================
    CARRITO — botones + / − y cantidad (backend listo)
    =============================================================================

    | Control UI              | Método | Ruta                           | Body                    |
    |-------------------------|--------|--------------------------------|-------------------------|
    | Input cantidad (submit) | PATCH  | shop.cart.items.update         | quantity (entero ≥ 0)   |
    | Botón +                 | POST   | shop.cart.items.increment      | @csrf solamente         |
    | Botón −                 | POST   | shop.cart.items.decrement      | @csrf solamente         |
    | Agregar 1ª vez          | POST   | shop.cart.items.store          | quantity? (default 1)   |

    Valor inicial del input: {{ $cartLineQuantity > 0 ? $cartLineQuantity : 1 }}
    Máximo sugerido: $product->inventory?->available_stock

    Ejemplo botón +:
    <form method="POST" action="{{ route('shop.cart.items.increment', $product) }}">@csrf
        <button type="submit">+</button>
    </form>

    Ejemplo botón −:
    <form method="POST" action="{{ route('shop.cart.items.decrement', $product) }}">@csrf
        <button type="submit">−</button>
    </form>

    Ejemplo PATCH cantidad absoluta:
    <form method="POST" action="{{ route('shop.cart.items.update', $product) }}">
        @csrf @method('PATCH')
        <input type="number" name="quantity" value="{{ $cartLineQuantity }}" min="0" max="{{ $product->inventory?->available_stock }}">
        <button type="submit">Actualizar</button>
    </form>

    Tras cada acción: redirect back con session('cart_status') y session('cart_summary').
    JSON: header Accept: application/json → { message, item_count, line_count, items[] }

    Invitado vs logueado: mismo flujo; al login el carrito de sesión se fusiona (MergeGuestCartAction).

    =============================================================================
    PRODUCTOS RELACIONADOS ($relatedProducts)
    =============================================================================

    Misma forma que el catálogo: effective_price, list_price, is_on_sale, image, category, etc.
    Cada item admite x-card igual que en shop/catalog/index.blade.php.

    Algoritmo (RelatedProductsResolver):
    1. Co-compra — productos que aparecen en los mismos pedidos (no cancelados/reembolsados),
       ordenados por frecuencia de pedidos compartidos y unidades vendidas juntas.
    2. Si faltan hasta 8 — misma categoría + misma marca (vehicleModel.brand).
    3. Si aún faltan — misma categoría, priorizando más vendidos y con stock.

    Ejemplo:
    @foreach ($relatedProducts as $related)
        <x-card
            :title="$related->name"
            :category="$related->category?->name"
            :price="$related->effective_price"
            :oldPrice="$related->is_on_sale ? $related->list_price : null"
            :image="$related->image ?? 'url-placeholder'"
            :isSale="$related->is_on_sale"
            :href="route('shop.product.show', $related)"
        />
    @endforeach

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
           <!-- bradcrumb -->
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 FLEC gap-8 text-white max-w-[95%] mx-auto p-4 select-none font-sans">
            <!-- IMAGENES -->
            @php
                $galleryImages = $product->images->filter(fn ($image) => filled($image->path))->values();

                if ($galleryImages->isEmpty() && filled($product->image)) {
                    $galleryImages = collect([(object) ['path' => $product->image]]);
                }

                $initialImage = $galleryImages->first()?->path ?? 'https://via.placeholder.com/600?text=MotoWorld';
            @endphp

            <div class="lg:col-span-7 flex flex-col sm:flex-row gap-4 h-fit">
                @if ($galleryImages->isNotEmpty())
                    {{-- Contenedor de miniaturas laterales --}}
                    <div class="flex flex-row sm:flex-col gap-3 shrink-0 w-full sm:w-36 overflow-x-auto sm:overflow-y-auto sm:max-h-[480px] py-1">
                        @foreach ($galleryImages as $index => $image)
                            <button
                                type="button"
                                data-gallery-thumb
                                data-image="{{ $image->path }}"
                                {{-- QUITAMOS p-2 y CORREGIMOS aspect-square --}}
                                class="gallery-thumb w-20 h-20 sm:w-36 sm:h-32 aspect-square bg-[#1e1e1e] rounded-sm overflow-hidden cursor-pointer transition-all duration-150 shrink-0 {{ $index === 0 ? 'border-2 border-[#f15a24]' : 'border border-neutral-700 hover:border-neutral-500' }}"
                                aria-label="Ver imagen {{ $index + 1 }}"
                            >
                                {{-- w-full h-full y object-cover para rellenar TODO el botón --}}
                                <img
                                    src="{{ $image->path }}"
                                    class="w-full h-full object-cover"
                                    alt="{{ $product->name }} — imagen {{ $index + 1 }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Contenedor de la Imagen Principal Grande --}}
                {{-- CORREGIMOS aspect-square y aseguramos un alto robusto en pantallas grandes --}}
                <div class="relative flex-1 w-full aspect-square min-h-[350px] lg:min-h-[480px] bg-[#000000] border border-neutral-800 rounded-sm overflow-hidden flex items-center justify-center">
                    @if ($product->is_on_sale)
                        <span class="absolute top-4 left-4 bg-[#f15a24] text-white font-black text-[11px] tracking-wider uppercase px-2.5 py-1 rounded-sm shadow-sm z-10">
                            SALE
                        </span>
                    @endif
                    
                    {{-- CAMBIAMOS A w-full h-full object-cover para que tome todo el tamaño disponible --}}
                    <img
                        id="product-main-image"
                        src="{{ $initialImage }}"
                        class="w-full h-full object-cover transition-all duration-200"
                        alt="{{ $product->name }}"
                    >
                </div>
            </div>

            <!-- DATOS DEL PRODUCTO -->
            <div class="lg:col-span-5 flex flex-col justify-start font-sans">
                <h3 class="text-3xl font-black tracking-wide uppercase leading-tight antialiased text-white">
                    {{ $product->name }}
                </h3>
                
                @if($product->vehicleModel?->brand)
                    <h5 class="text-sm font-bold tracking-widest text-[#f15a24] mt-1 uppercase">
                        {{ $product->vehicleModel->brand->name }}
                    </h5>
                @endif

                <div class="mt-6 space-y-1 text-sm text-neutral-400 font-medium">
                    <p><span class="text-neutral-500 font-bold">Categoría:</span> {{ $product->category->name }}</p>
                    <p><span class="text-neutral-500 font-bold">SKU:</span> {{ $product->sku }}</p>
                    @if($product->vehicleModel)
                        <p><span class="text-neutral-500 font-bold">Modelo:</span> {{ $product->vehicleModel->name }}</p>
                    @endif
                    <p>
                        <span class="text-neutral-500 font-bold">Disponibilidad:</span> 
                        @if($product->hasAvailableStock())
                            <span class="text-emerald-500 font-bold">En Stock ({{ $product->inventory->available_stock }} u.)</span>
                        @else
                            <span class="text-rose-500 font-bold">Agotado</span>
                        @endif
                    </p>
                </div>

                {{-- Bloque de precios inteligente --}}
                <div class="my-6 flex items-baseline gap-4">
                    <span class="text-3xl font-black tracking-tight text-white">
                        ${{ number_format($product->effective_price, 0, '.', '') }}
                    </span>
                    @if($product->is_on_sale)
                        <span class="text-base font-bold text-neutral-500 line-through">
                            ${{ number_format($product->list_price, 0, '.', '') }}
                        </span>
                    @endif
                </div>

                {{-- Selector de cantidad dinámico --}}
                <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white select-none overflow-hidden rounded-sm" x-data="{ qty: 1 }">
                    <button type="button" @click="if(qty > 1) qty--" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-2xl focus:outline-none transition-colors">
                        -
                    </button>
                    <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center">
                        <input type="number" name="quantity" x-model.number="qty" readonly class="w-full bg-transparent text-center text-white font-sans font-black text-lg focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    </div>
                    <button type="button" @click="qty++" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-xl focus:outline-none transition-colors">
                        +
                    </button>
                </div>
            </div>

        </div>

        <!-- Informacion adicional -->
        
        <div class="w-full px-10 py-5 text-white font-sans mt-12 select-none" x-data="{ currentTab: 'description' }">
    
            {{-- Cabecera de pestañas interactiva --}}
            <div class="flex flex-wrap items-center gap-x-8 border-b border-neutral-800">
                <button type="button" 
                        @click="currentTab = 'description'"
                        :class="currentTab === 'description' ? 'text-white border-[#f15a24]' : 'text-neutral-400 border-transparent hover:text-white'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Descripción
                </button>

                <button type="button" 
                        @click="currentTab = 'info'"
                        :class="currentTab === 'info' ? 'text-white border-[#f15a24]' : 'text-neutral-400 border-transparent hover:text-white'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Información Adicional
                </button>

                <button type="button" 
                        @click="currentTab = 'reviews'"
                        :class="currentTab === 'reviews' ? 'text-white border-[#f15a24]' : 'text-neutral-400 border-transparent hover:text-white'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Reviews ({{ $reviewSummary['count'] }})
                </button>
            </div>

            {{-- Contenidos dinámicos de pestañas --}}
            <div class="mt-8 text-neutral-300 text-sm leading-relaxed max-w-5xl">
                
                {{-- Tab: Descripción --}}
                <div x-show="currentTab === 'description'" class="space-y-4">
                    @if($product->description)
                        <p>{!! nl2br(e($product->description)) !!}</p>
                    @else
                        <p class="text-neutral-500 italic">No hay descripción disponible para este artículo.</p>
                    @endif
                </div>

                {{-- Tab: Información Adicional --}}
                <div x-show="currentTab === 'info'" class="space-y-4" style="display: none;">
                    @if($product->additional_information)
                        <p>{!! nl2br(e($product->additional_information)) !!}</p>
                    @else
                        <p class="text-neutral-500 italic">No hay especificaciones adicionales registradas.</p>
                    @endif
                </div>

                {{-- Tab: Reseñas / Comentarios --}}
                <div x-show="currentTab === 'reviews'" class="space-y-6" style="display: none;">
                    @forelse ($reviews as $review)
                        <div class="border-b border-neutral-900 pb-4">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-black text-white text-base">
                                    {{ $review->user->customerProfile?->first_name ?? 'Usuario' }} 
                                    {{ $review->user->customerProfile?->last_name ?? 'MotoWorld' }}
                                </span>
                                <span class="text-xs font-bold text-neutral-500">
                                    {{ $review->created_at->format('d/m/Y') }}
                                </span>
                            </div>
                            {{-- Estrellas numéricas --}}
                            <div class="text-[#f15a24] font-black text-xs mb-2 tracking-widest">
                                @for ($i = 0; $i < $review->stars; $i++) ★ @endfor
                                @for ($i = $review->stars; $i < 5; $i++) ☆ @endfor
                            </div>
                            <p class="text-neutral-400">{{ $review->comment }}</p>
                        </div>
                    @empty
                        <p class="text-neutral-500 italic">Este producto aún no cuenta con reseñas de clientes.</p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const mainImage = document.getElementById('product-main-image');
                if (!mainImage || !thumb.dataset.image) {
                    return;
                }

                mainImage.src = thumb.dataset.image;

                document.querySelectorAll('[data-gallery-thumb]').forEach((button) => {
                    button.classList.remove('border-2', 'border-[#f15a24]');
                    button.classList.add('border', 'border-neutral-700');
                });

                thumb.classList.remove('border', 'border-neutral-700');
                thumb.classList.add('border-2', 'border-[#f15a24]');
            });
        });
    </script>
@endsection

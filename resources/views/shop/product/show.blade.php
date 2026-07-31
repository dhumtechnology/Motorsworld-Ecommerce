{{--
    Detalle de producto — documentación para el equipo frontend.
    El HTML inferior es maqueta estática; conectar con las variables descritas aquí.

    =============================================================================
    VARIABLES DEL CONTROLADOR (ProductController@show)
    =============================================================================

    - $product         : Product (solo status active; 404 en otros casos)
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

    Misma forma que el catálogo: effective_price, list_price, is_on_sale, discount_percent,
    offer / offer_reason / offer_ends_at, image, category, etc.
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
            :discountPercent="$related->discount_percent"
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
      Campos: offer_price_amount, discount_percent, reason, currency, starts_at, ends_at

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
    PRECIOS Y OFERTA ACTIVA (ProductOfferPresenter — misma convención que el catálogo)
    =============================================================================

    - $product->effective_price   → precio a mostrar / cobrar
    - $product->list_price        → precio de catálogo (price_amount)
    - $product->sale_price        → precio en oferta; null si no aplica
    - $product->is_on_sale        → bool
    - $product->discount_percent  → float|null (% de descuento)
    - $product->offer_reason      → string|null (motivo)
    - $product->offer_starts_at   → Carbon|null
    - $product->offer_ends_at     → Carbon|null
    - $product->offer             → array|null con datos completos:
        id, product_id, offer_price_amount, discount_percent, reason, currency,
        starts_at, ends_at, starts_at_formatted, ends_at_formatted,
        is_active, lifecycle_status

    Relación Eloquent:
    - $product->activeOffer->offer_price_amount
    - $product->activeOffer->discount_percent
    - $product->activeOffer->reason
    - $product->activeOffer->starts_at / ends_at
    - $product->activeOffer->resolvedDiscountPercent()

    Ejemplo UI oferta:
    @if ($product->is_on_sale && $product->offer)
        <span>-{{ number_format($product->discount_percent, 0) }}%</span>
        <p>{{ $product->offer_reason }}</p>
        <p>{{ number_format($product->sale_price, 2) }} PEN</p>
        <p class="line-through">{{ number_format($product->list_price, 2) }} PEN</p>
        <p>Válida hasta {{ $product->offer['ends_at_formatted'] }}</p>
    @endif

    Lo mismo aplica a cada item de $relatedProducts.

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
        <div class="px-4 py-2">
            <x-breadcrumb :items="[
                ['label' => 'NUESTRA TIENDA', 'url' => route('shop.catalog')],
                ['label' => 'PRODUCTO', 'url' => null],
            ]" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 FLEC gap-8 text-white max-w-[95%] mx-auto p-8 select-none font-title">
            <!-- IMAGENES -->
            @php
                $galleryImages = $product->images->filter(fn ($image) => filled($image->path))->values();

                if ($galleryImages->isEmpty() && filled($product->image)) {
                    $galleryImages = collect([(object) ['path' => $product->image]]);
                }

                $initialImage = $galleryImages->first()?->path ?? 'https://via.placeholder.com/600?text=MotoWorld';
            @endphp

            <div class="lg:col-span-8 flex flex-col sm:flex-row gap-4 h-fit">
                @if ($galleryImages->isNotEmpty())
                    {{-- Contenedor de miniaturas laterales --}}
                    <div class="flex flex-row px-4 sm:flex-col gap-3 py-1">
                        @foreach ($galleryImages as $index => $image)
                            <button
                                type="button"
                                data-gallery-thumb
                                data-image="{{ $image->path }}"
                                {{-- QUITAMOS p-2 y CORREGIMOS aspect-square --}}
                                class="gallery-thumb w-20 h-20 sm:w-36 sm:h-32 aspect-square rounded-sm overflow-hidden cursor-pointer transition-all duration-150 shrink-0 {{ $index === 0 ? 'border-2 border-[#f15a24]' : 'border border-neutral-700 hover:border-neutral-500' }}"
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
                <div class="flex justify-center w-80 h-80 sm:h-96 lg:h-[480px] rounded-sm overflow-hidden w-full">
                    
                    {{-- CAMBIAMOS A w-full h-full object-cover para que tome todo el tamaño disponible --}}
                    <img
                        id="product-main-image"
                        src="{{ $initialImage }}"
                        class=" h-full object-cover transition-all duration-200"
                        alt="{{ $product->name }}"
                    >
                </div>
            </div>

            <!-- DATOS DEL PRODUCTO -->
            <div class="lg:col-span-4 flex flex-col justify-between py-6 text-black font-title">
                <h3 class="text-3xl tracking-wide font-bold font-title uppercase leading-tight antialiased">
                    {{ $product->name }}
                </h3>
                
                @if($product->vehicleModel?->brand)
                    <h5 class="text-sm font-bold tracking-widest mt-1 uppercase text-black">
                        {{ $product->vehicleModel->brand->name }}
                    </h5>
                @endif

                <div class="flex flex-col justify-between space-y-2 text-black font-title">
                    <p><span class="font-bold">Categoría:</span> {{ $product->category->name }}</p>
                    <p><span class="font-bold">SKU:</span> {{ $product->sku }}</p>
                    @if($product->vehicleModel)
                        <p><span class="font-bold">Modelo:</span> {{ $product->vehicleModel->name }}</p>
                    @endif
                    <p>
                        <span class="font-bold">Disponibilidad:</span> 
                        @if($product->hasAvailableStock())
                            <span class="font-bold">En Stock ({{ $product->inventory->available_stock }} u.)</span>
                        @else
                            <span class="font-bold">Agotado</span>
                        @endif
                    </p>
                </div>

                {{-- Bloque de precios inteligente --}}
                <div class="my-6 flex items-baseline gap-4">
                    <span class="text-3xl font-black tracking-tight">
                        ${{ number_format($product->effective_price, 0, '.', '') }}
                    </span>
                    @if($product->is_on_sale)
                        <span class="font-bold line-through">
                            ${{ number_format($product->list_price, 0, '.', '') }}
                        </span>
                    @endif
                </div>

                {{-- Carrito: Agregar / cantidad (AJAX, sin recargar) --}}
                <div
                    class="mt-2 space-y-3"
                    data-product-cart
                    data-product-id="{{ $product->id }}"
                    data-max-stock="{{ (int) ($product->inventory?->available_stock ?? 0) }}"
                    data-store-url="{{ route('shop.cart.items.store', $product) }}"
                    data-increment-url="{{ route('shop.cart.items.increment', $product) }}"
                    data-decrement-url="{{ route('shop.cart.items.decrement', $product) }}"
                >
                    <p data-cart-error class="hidden text-sm text-rose-400 font-semibold"></p>

                    @if ($product->hasAvailableStock() || $cartLineQuantity > 0)
                        <div data-cart-add class="{{ $cartLineQuantity > 0 ? 'hidden' : '' }}">
                            @if ($product->hasAvailableStock())
                                <button
                                    type="button"
                                    data-cart-action="store"
                                    class="w-full sm:w-auto px-8 py-3 text-white font-title bold tracking-widest bg-primary rounded hover:bg-black cursor-pointer transition-colors uppercase"
                                >
                                    Agregar al carrito
                                </button>
                            @else
                                <button type="button" disabled
                                        class="w-full sm:w-auto px-8 py-3 bg-neutral-700 text-neutral-400 font-extrabold text-xs tracking-widest rounded uppercase cursor-not-allowed">
                                    Agotado
                                </button>
                            @endif
                        </div>

                        <div data-cart-qty class="{{ $cartLineQuantity > 0 ? '' : 'hidden' }} space-y-2">
                            <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white select-none overflow-hidden rounded-sm">
                                <button
                                    type="button"
                                    data-cart-action="decrement"
                                    class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-2xl focus:outline-none transition-colors"
                                    aria-label="Disminuir"
                                >
                                    −
                                </button>
                                <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center text-white font-sans font-black text-lg">
                                    <span data-cart-qty-value>{{ max($cartLineQuantity, 1) }}</span>
                                </div>
                                <button
                                    type="button"
                                    data-cart-action="increment"
                                    @disabled($cartLineQuantity >= (int) ($product->inventory?->available_stock ?? 0))
                                    class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-xl focus:outline-none transition-colors disabled:opacity-40"
                                    aria-label="Aumentar"
                                >
                                    +
                                </button>
                            </div>
                            <p class="text-xs">
                                En tu carrito.
                                <a href="{{ route('shop.cart.index') }}" class="hover:text-orange-400 font-bold">Ver carrito →</a>
                            </p>
                        </div>
                    @else
                        <button type="button" disabled
                                class="w-full sm:w-auto px-8 py-3 bg-neutral-700 font-extrabold text-white text-xs tracking-widest rounded uppercase cursor-not-allowed">
                            Agotado
                        </button>
                    @endif
                </div>
            </div>

        </div>

        <!-- Informacion adicional -->
        
        <div class="w-full px-10 py-5 text-black font-title mt-12 select-none" x-data="{ currentTab: 'description' }">
    
            {{-- Cabecera de pestañas interactiva --}}
            <div class="flex flex-wrap items-center gap-x-8">
                <button type="button" 
                        @click="currentTab = 'description'"
                        :class="currentTab === 'description' ? 'text-primary border-[#f15a24]' : 'text-neutral-400 border-transparent hover:text-secondary'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Descripción
                </button>

                <button type="button" 
                        @click="currentTab = 'info'"
                        :class="currentTab === 'info' ? 'text-primary border-[#f15a24]' : 'text-neutral-400 border-transparent hover:text-secondary'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Información Adicional
                </button>
            </div>

            {{-- Contenidos dinámicos de pestañas --}}
            <div class="mt-8 text-black text-sm leading-relaxed max-w-5xl">
                
                {{-- Tab: Descripción --}}
                <div x-show="currentTab === 'description'" class="space-y-4">
                    @if($product->description)
                        <p>{!! nl2br(e($product->description)) !!}</p>
                    @else
                        <p class="text-black italic">No hay descripción disponible para este artículo.</p>
                    @endif
                </div>

                {{-- Tab: Información Adicional --}}
                <div x-show="currentTab === 'info'" class="space-y-4" style="display: none;">
                    @if($product->additional_information)
                        <p>{!! nl2br(e($product->additional_information)) !!}</p>
                    @else
                        <p class="text-black italic">No hay especificaciones adicionales registradas.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>

    @include('shop.partials.popular-products-carousel')

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

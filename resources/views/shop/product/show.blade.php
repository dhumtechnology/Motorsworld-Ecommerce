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
    Precio:     {{ $product->currencySymbol() }} {{ number_format($product->effective_price, 2) }}
    Tachado:    @if($product->is_on_sale) {{ $product->currencySymbol() }} {{ number_format($product->list_price, 2) }} @endif
    Descripción: {!! nl2br(e($product->description)) !!}
    Info extra:  {!! nl2br(e($product->additional_information)) !!}
--}}

@extends('layouts.shop')

@section('content')
    @php
        $discountLabel = ($product->is_on_sale && $product->discount_percent)
            ? rtrim(rtrim(number_format((float) $product->discount_percent, 2, '.', ''), '0'), '.')
            : null;
    @endphp
    <div>
        <div class="px-4 py-2">
            <x-breadcrumb :items="[
                ['label' => 'NUESTRA TIENDA', 'url' => route('shop.catalog')],
                ['label' => 'PRODUCTO', 'url' => null],
            ]" />
        </div>

        <div
            class="grid grid-cols-1 lg:grid-cols-12 FLEC gap-8 text-white max-w-[95%] mx-auto p-8 select-none font-title"
            x-data="productColorPicker(@js($variantsPayload ?? []), {{ (int) ($defaultVariantId ?? 0) }}, @js($product->image ?: 'https://via.placeholder.com/600?text=MotoWorld'), {{ ($hasColorChoices ?? false) ? 'true' : 'false' }})"
        >
            <div class="lg:col-span-8 flex flex-col sm:flex-row gap-4 sm:h-96 lg:h-[480px]">
                <div
                    class="relative shrink-0 sm:h-full sm:w-36"
                    x-show="galleryImages.length > 0"
                    x-cloak
                >
                    <div
                        x-ref="thumbsScroll"
                        @scroll.passive="syncThumbScroll()"
                        class="gallery-thumbs-scroll flex flex-row sm:flex-col gap-3 px-1 py-1 overflow-x-auto sm:overflow-x-hidden sm:overflow-y-auto sm:h-full sm:max-h-full overscroll-contain scroll-smooth"
                    >
                        <template x-for="(image, index) in galleryImages" :key="image.path + '-' + index">
                            <button
                                type="button"
                                @click="mainImage = image.path; activeThumb = index"
                                :class="activeThumb === index ? 'border-2 border-[#f15a24]' : 'border border-neutral-700 hover:border-neutral-500'"
                                class="gallery-thumb w-20 h-20 sm:w-full sm:h-28 aspect-square rounded-sm overflow-hidden cursor-pointer transition-all duration-150 shrink-0"
                            >
                                <img :src="image.path" class="w-full h-full object-cover" alt="{{ $product->name }}" loading="lazy">
                            </button>
                        </template>
                    </div>

                    <div
                        class="pointer-events-none absolute inset-x-0 top-0 hidden sm:flex h-10 items-start justify-center bg-gradient-to-b from-white via-white/85 to-transparent transition-opacity duration-200"
                        :class="thumbCanScrollUp ? 'opacity-100' : 'opacity-0'"
                        aria-hidden="true"
                    >
                        <svg class="mt-1 h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                        </svg>
                    </div>
                    <div
                        class="pointer-events-none absolute inset-x-0 bottom-0 hidden sm:flex h-10 items-end justify-center bg-gradient-to-t from-white via-white/85 to-transparent transition-opacity duration-200"
                        :class="thumbCanScrollDown ? 'opacity-100' : 'opacity-0'"
                        aria-hidden="true"
                    >
                        <svg class="mb-1 h-4 w-4 text-neutral-500 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="flex justify-center h-80 sm:h-full min-h-0 flex-1 rounded-sm overflow-hidden w-full relative">
                    @if($product->is_on_sale && $discountLabel)
                        <span class="absolute top-3 left-3 z-10 inline-flex items-center rounded-md bg-primary px-3 py-1.5 text-lg font-black uppercase tracking-tight text-white shadow-md">
                            -{{ $discountLabel }}%
                        </span>
                    @endif
                    <img id="product-main-image" :src="mainImage" class="h-full w-full object-cover transition-all duration-200" alt="{{ $product->name }}">
                </div>
            </div>

            <div class="lg:col-span-4 flex flex-col justify-between py-6 text-black font-title">
                <h3 class="text-3xl tracking-wide font-bold font-title uppercase leading-tight antialiased">{{ $product->name }}</h3>

                @if($product->vehicleModel?->brand)
                    <h5 class="text-sm font-bold tracking-widest mt-1 uppercase text-black">{{ $product->vehicleModel->brand->name }}</h5>
                @endif

                <div class="flex flex-col justify-between space-y-2 text-black font-title">
                    <p><span class="font-bold">Categoría:</span> {{ $product->category->name }}</p>
                    <p><span class="font-bold">SKU:</span> <span x-text="selected?.sku || '{{ $product->sku }}'"></span></p>
                    @if($product->vehicleModel)
                        <p><span class="font-bold">Modelo:</span> {{ $product->vehicleModel->name }}</p>
                    @endif
                    <p><span class="font-bold">Disponibilidad:</span> <span class="font-bold" x-text="stockLabel"></span></p>
                </div>

                <div class="my-4 space-y-3" x-show="hasColorChoices && variants.length > 0">
                    <div class="flex items-end justify-between gap-3">
                        <p class="text-sm font-bold uppercase tracking-wide">Elige un color</p>
                        <p class="text-xs text-neutral-600" x-show="selected">
                            Seleccionado: <span class="font-bold text-black" x-text="selected?.label"></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <template x-for="variant in variants" :key="variant.id">
                            <button
                                type="button"
                                @click="selectVariant(variant.id)"
                                :class="Number(selectedId) === Number(variant.id)
                                    ? 'border-black bg-orange-50 ring-1 ring-[#f15a24]/40'
                                    : 'border-neutral-300 hover:border-neutral-500 bg-white'"
                                class="w-fit rounded-lg border px-3 py-2 text-left transition-colors shadow-sm"
                            >
                                <!-- Fila superior: Círculos de color y Unidades -->
                                <div class="flex items-center justify-between gap-3">
                                    <!-- Círculos de color con borde negro fino -->
                                    <div class="flex -space-x-1 shrink-0">
                                        <template x-for="(color, cIndex) in (variant.colors.length ? variant.colors : [{ name: variant.label, hex: '#9CA3AF' }])" :key="cIndex">
                                            <span
                                                class="h-5 w-5 rounded-full border border-black shadow-sm"
                                                :style="`background:${color.hex || '#d1d5db'}`"
                                                :title="color.name"
                                            ></span>
                                        </template>
                                    </div>

                                    <!-- Cantidad de unidades -->
                                    <span
                                        class="shrink-0 text-xs font-bold tracking-wide"
                                        :class="Number(variant.available_stock) > 0 ? 'text-emerald-700' : 'text-rose-600'"
                                        x-text="Number(variant.available_stock) > 0 ? (variant.available_stock + ' u.') : 'Agotado'"
                                    ></span>
                                </div>

                                <!-- Fila inferior: Nombre del color -->
                                <p class="mt-1 text-xs font-medium text-neutral-900 truncate" x-text="variant.label"></p>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="my-4 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-show="variants.length === 0">
                    Este producto no está disponible para compra por ahora.
                </div>

                @if($product->is_on_sale)
                    <div class="my-4 rounded-lg border border-primary/25 bg-orange-50 px-4 py-3">
                        <div class="flex flex-wrap items-start gap-3">
                            @if($discountLabel)
                                <span class="inline-flex shrink-0 items-center rounded-md bg-primary px-4 py-2 text-3xl font-black uppercase tracking-tight text-white shadow-sm">
                                    -{{ $discountLabel }}%
                                </span>
                            @endif
                            <div class="min-w-0 flex-1 space-y-1">
                                @if($product->offer_reason)
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Oferta</p>
                                    <p class="text-lg font-black uppercase leading-snug text-neutral-900">{{ $product->offer_reason }}</p>
                                @elseif($discountLabel)
                                    <p class="text-lg font-black uppercase tracking-wide text-primary">¡Oferta especial!</p>
                                @endif
                                @if(! empty($product->offer['ends_at_formatted']))
                                    <p class="text-xs font-semibold text-neutral-600">
                                        Válida hasta {{ $product->offer['ends_at_formatted'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="my-6 flex items-baseline gap-4 flex-wrap">
                    <span class="text-3xl font-black tracking-tight">{{ $product->currencySymbol() }} {{ number_format((float) $product->effective_price, 2) }}</span>
                    @if($product->is_on_sale)
                        <span class="font-bold line-through text-neutral-500">{{ $product->currencySymbol() }} {{ number_format((float) $product->list_price, 2) }}</span>
                        @if($discountLabel)
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-black uppercase tracking-wide text-emerald-800">
                                Ahorras {{ $discountLabel }}%
                            </span>
                        @endif
                    @endif
                </div>

                <div
                    class="mt-2 space-y-3"
                    data-product-cart
                    data-product-id="{{ $product->id }}"
                    data-variant-id="{{ (int) ($defaultVariantId ?? 0) }}"
                    data-max-stock="{{ (int) data_get(collect($variantsPayload ?? [])->firstWhere('id', (int) ($defaultVariantId ?? 0)), 'available_stock', 0) }}"
                    data-store-url="{{ route('shop.cart.items.store', $product, false) }}"
                    data-increment-url="{{ route('shop.cart.items.increment', $product, false) }}"
                    data-decrement-url="{{ route('shop.cart.items.decrement', $product, false) }}"
                    x-effect="
                        $el.dataset.variantId = String(selectedId || '');
                        $el.dataset.maxStock = String(selectedStock || 0);
                    "
                >
                    <p data-cart-error class="hidden text-sm text-rose-600 font-semibold" x-text="cartError" x-show="cartError" x-cloak></p>

                    <div data-cart-add x-show="cartQty <= 0" x-cloak>
                        <button
                            type="button"
                            data-cart-action="store"
                            :disabled="cartBusy || !selectedId || selectedStock <= 0"
                            x-show="selectedId && selectedStock > 0"
                            class="w-full sm:w-auto px-8 py-3 text-white font-title bold tracking-widest bg-primary rounded hover:bg-black cursor-pointer transition-colors uppercase disabled:opacity-60"
                        >
                            <span>Agregar al carrito</span>
                            <span class="normal-case tracking-normal font-semibold opacity-90" x-show="hasColorChoices" x-text="selected ? ' — ' + selected.label : ''"></span>
                        </button>
                        <button
                            type="button"
                            disabled
                            x-show="!selectedId || selectedStock <= 0"
                            class="w-full sm:w-auto px-8 py-3 bg-neutral-700 text-neutral-400 font-extrabold text-xs tracking-widest rounded uppercase cursor-not-allowed"
                        >
                            <span x-text="!selectedId ? (hasColorChoices ? 'Selecciona un color' : 'No disponible') : 'Agotado'"></span>
                        </button>
                    </div>

                    <div data-cart-qty x-show="cartQty > 0" x-cloak class="space-y-2">
                        <p class="text-xs text-neutral-600">
                            En carrito<span x-show="hasColorChoices"> (<span class="font-bold text-black" x-text="selected?.label"></span>)</span>:
                        </p>
                        <div class="flex items-center gap-8 ">
                            <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white select-none overflow-hidden rounded-sm">
                                <button type="button" data-cart-action="decrement" :disabled="cartBusy" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-2xl focus:outline-none transition-colors disabled:opacity-40" aria-label="Disminuir">−</button>
                                <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center text-white font-sans font-black text-lg">
                                    <span data-cart-qty-value x-text="cartQty"></span>
                                </div>
                                <button type="button" data-cart-action="increment" :disabled="cartBusy || cartQty >= selectedStock" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-xl focus:outline-none transition-colors disabled:opacity-40" aria-label="Aumentar">+</button>
                            </div>
                            <div>
                                <a 
                                    href="{{ route('shop.cart.index') }}"
                                    class="w-full sm:w-auto px-8 py-2 text-white font-title bold tracking-widest bg-primary rounded hover:bg-black cursor-pointer transition-colors uppercase disabled:opacity-60"
                                >
                                    <span>Ver carrito</span>
                                </a>
                            </div>
                        </div>
                        <p class="text-xs text-neutral-600" x-show="hasColorChoices">
                            Puedes elegir otro color y agregarlo también.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .gallery-thumbs-scroll {
                scrollbar-width: thin;
                scrollbar-color: #ff6600 #e5e5e5;
            }
            .gallery-thumbs-scroll::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            .gallery-thumbs-scroll::-webkit-scrollbar-track {
                background: #e5e5e5;
                border-radius: 9999px;
            }
            .gallery-thumbs-scroll::-webkit-scrollbar-thumb {
                background: #ff6600;
                border-radius: 9999px;
            }
            .gallery-thumbs-scroll::-webkit-scrollbar-thumb:hover {
                background: #e65c00;
            }
        </style>
        <script>
            window.productColorPicker = function (variants, defaultId, fallbackImage, hasColorChoices) {
                const list = Array.isArray(variants) ? variants : [];
                const initial = list.find((v) => Number(v.id) === Number(defaultId)) || list[0] || null;
                return {
                    variants: list,
                    hasColorChoices: Boolean(hasColorChoices),
                    selectedId: initial ? Number(initial.id) : null,
                    cartQty: Number(initial?.cart_quantity || 0),
                    cartBusy: false,
                    cartError: '',
                    mainImage: initial?.images?.[0]?.path || fallbackImage || 'https://via.placeholder.com/600?text=MotoWorld',
                    activeThumb: 0,
                    thumbCanScrollUp: false,
                    thumbCanScrollDown: false,
                    get selected() {
                        return this.variants.find((v) => Number(v.id) === Number(this.selectedId)) || null;
                    },
                    get selectedStock() { return Number(this.selected?.available_stock || 0); },
                    get galleryImages() { return this.selected?.images?.length ? this.selected.images : [{ path: this.mainImage }]; },
                    get stockLabel() {
                        if (!this.selected) return 'No disponible';
                        return this.selectedStock > 0 ? `En Stock (${this.selectedStock} u.)` : 'Agotado';
                    },
                    syncThumbScroll() {
                        const el = this.$refs.thumbsScroll;
                        if (!el) {
                            this.thumbCanScrollUp = false;
                            this.thumbCanScrollDown = false;
                            return;
                        }
                        const max = el.scrollHeight - el.clientHeight;
                        this.thumbCanScrollUp = el.scrollTop > 4;
                        this.thumbCanScrollDown = max > 8 && el.scrollTop < max - 4;
                    },
                    syncCartRoot() {
                        const root = this.$root?.querySelector?.('[data-product-cart]')
                            || document.querySelector('[data-product-cart]');
                        if (!root) return;
                        root.dataset.variantId = String(this.selectedId || '');
                        root.dataset.maxStock = String(this.selectedStock || 0);
                    },
                    refreshCartQty() {
                        this.cartQty = Number(this.selected?.cart_quantity || 0);
                    },
                    setCartQuantity(variantId, quantity) {
                        const qty = Number(quantity) || 0;
                        const variant = this.variants.find((item) => Number(item.id) === Number(variantId));
                        if (variant) {
                            variant.cart_quantity = qty;
                        }
                        if (Number(this.selectedId) === Number(variantId)) {
                            this.cartQty = qty;
                        }
                        this.cartError = '';
                        this.syncCartRoot();
                    },
                    setCartBusy(busy) {
                        this.cartBusy = Boolean(busy);
                    },
                    setCartError(message) {
                        this.cartError = message || '';
                    },
                    selectVariant(id) {
                        this.selectedId = id == null ? null : Number(id);
                        this.activeThumb = 0;
                        this.mainImage = this.selected?.images?.[0]?.path || this.mainImage;
                        this.cartError = '';
                        this.refreshCartQty();
                        this.$nextTick(() => {
                            this.syncCartRoot();
                            const el = this.$refs.thumbsScroll;
                            if (el) el.scrollTop = 0;
                            this.syncThumbScroll();
                        });
                    },
                    init() {
                        this.$nextTick(() => {
                            this.selectVariant(this.selectedId);
                            const el = this.$refs.thumbsScroll;
                            if (el && typeof ResizeObserver !== 'undefined') {
                                new ResizeObserver(() => this.syncThumbScroll()).observe(el);
                            }
                        });
                    },
                };
            };
        </script>

        <!-- Informacion adicional -->
        
        <div class="w-full px-10 py-5 text-black font-title mt-12 select-none" x-data="{ currentTab: 'description' }">
    
            {{-- Cabecera de pestañas interactiva --}}
            <div class="flex flex-wrap items-center gap-x-8">
                <button type="button" 
                        @click="currentTab = 'description'"
                        :class="currentTab === 'description' ? 'text-primary border-[#f15a24]' : 'border-transparent hover:text-gray-700'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Descripción
                </button>

                <button type="button" 
                        @click="currentTab = 'info'"
                        :class="currentTab === 'info' ? 'text-primary border-[#f15a24]' : 'border-transparent hover:text-gray-700'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Información Adicional
                </button>

                <button type="button"
                        @click="currentTab = 'technical'"
                        :class="currentTab === 'technical' ? 'text-primary border-[#f15a24]' : 'border-transparent hover:text-gray-700'"
                        class="pb-3 text-2xl font-black uppercase tracking-wide border-b-2 focus:outline-none transition-all duration-150">
                    Ficha técnica
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

                {{-- Tab: Ficha técnica --}}
                <div x-show="currentTab === 'technical'" class="space-y-4" style="display: none;">
                    @if($product->technical_sheet)
                        <p class="text-neutral-700">
                            Descarga la ficha técnica de este producto.
                        </p>

                        <!-- Contenedor con nombre del archivo y botón -->
                        <div class="flex items-center justify-between p-4 max-w-md rounded-lg border border-neutral-200 bg-neutral-50 shadow-sm">
                            <div class="flex items-center gap-3 truncate pr-4">
                                <!-- Icono de documento PDF -->
                                <svg class="w-6 h-6 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                
                                <!-- Nombre del archivo subido -->
                                <span class="text-sm font-medium text-neutral-800 truncate" title="{{ basename($product->technical_sheet) }}">
                                    {{ basename($product->technical_sheet) }}
                                </span>
                            </div>

                            <!-- Botón Descargar -->
                            <a 
                                href="{{ $product->technical_sheet }}" 
                                download
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 rounded bg-primary px-3 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-black transition-colors shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Descargar
                            </a>
                        </div>

                    @else
                        <p class="text-black italic">No hay ficha técnica disponible para este artículo.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-popular-products :popular-products="$popularProducts" :cart-quantities="$cartQuantities ?? []" />

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

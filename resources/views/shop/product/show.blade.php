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
    <div>
        <div class="px-4 py-2">
            <x-breadcrumb :items="[
                ['label' => 'NUESTRA TIENDA', 'url' => route('shop.catalog')],
                ['label' => 'PRODUCTO', 'url' => null],
            ]" />
        </div>

        <div
            class="grid grid-cols-1 lg:grid-cols-12 FLEC gap-8 text-white max-w-[95%] mx-auto p-8 select-none font-title"
            x-data="productColorPicker(@js($variantsPayload ?? []), {{ (int) ($defaultVariantId ?? 0) }}, @js($product->image ?: 'https://via.placeholder.com/600?text=MotoWorld'))"
        >
            <div class="lg:col-span-8 flex flex-col sm:flex-row gap-4 h-fit">
                <div class="flex flex-row px-4 sm:flex-col gap-3 py-1" x-show="galleryImages.length > 0">
                    <template x-for="(image, index) in galleryImages" :key="image.path + '-' + index">
                        <button
                            type="button"
                            @click="mainImage = image.path; activeThumb = index"
                            :class="activeThumb === index ? 'border-2 border-[#f15a24]' : 'border border-neutral-700 hover:border-neutral-500'"
                            class="gallery-thumb w-20 h-20 sm:w-36 sm:h-32 aspect-square rounded-sm overflow-hidden cursor-pointer transition-all duration-150 shrink-0"
                        >
                            <img :src="image.path" class="w-full h-full object-cover" alt="{{ $product->name }}" loading="lazy">
                        </button>
                    </template>
                </div>

                <div class="flex justify-center w-80 h-80 sm:h-96 lg:h-[480px] rounded-sm overflow-hidden w-full">
                    <img id="product-main-image" :src="mainImage" class="h-full object-cover transition-all duration-200" alt="{{ $product->name }}">
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

                <div class="my-4 space-y-3" x-show="variants.length > 0">
                    <div class="flex items-end justify-between gap-3">
                        <p class="text-sm font-bold uppercase tracking-wide">Elige un color</p>
                        <p class="text-xs text-neutral-600" x-show="selected">
                            Seleccionado: <span class="font-bold text-black" x-text="selected?.label"></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="variant in variants" :key="variant.id">
                            <button
                                type="button"
                                @click="selectVariant(variant.id)"
                                :class="Number(selectedId) === Number(variant.id)
                                    ? 'border-[#f15a24] bg-orange-50 ring-1 ring-[#f15a24]/40'
                                    : 'border-neutral-300 hover:border-neutral-500 bg-white'"
                                class="w-full rounded border px-3 py-2.5 text-left transition-colors"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="flex -space-x-1 shrink-0">
                                            <template x-for="(color, cIndex) in (variant.colors.length ? variant.colors : [{ name: variant.label, hex: '#9CA3AF' }])" :key="cIndex">
                                                <span
                                                    class="h-5 w-5 rounded-full border-2 border-white shadow-sm"
                                                    :style="`background:${color.hex || '#d1d5db'}`"
                                                    :title="color.name"
                                                ></span>
                                            </template>
                                        </div>
                                        <span class="text-sm font-semibold truncate" x-text="variant.label"></span>
                                    </div>
                                    <span
                                        class="shrink-0 text-[11px] font-bold uppercase tracking-wide"
                                        :class="Number(variant.available_stock) > 0 ? 'text-emerald-700' : 'text-rose-600'"
                                        x-text="Number(variant.available_stock) > 0 ? (variant.available_stock + ' u.') : 'Agotado'"
                                    ></span>
                                </div>
                                <p class="mt-1 text-[11px] text-neutral-500 font-mono truncate" x-text="variant.sku"></p>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="my-4 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-show="variants.length === 0">
                    Este producto aún no tiene colores configurados.
                </div>

                <div class="my-6 flex items-baseline gap-4">
                    <span class="text-3xl font-black tracking-tight">{{ $product->currencySymbol() }} {{ number_format((float) $product->effective_price, 2) }}</span>
                    @if($product->is_on_sale)
                        <span class="font-bold line-through">{{ $product->currencySymbol() }} {{ number_format((float) $product->list_price, 2) }}</span>
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
                            <span class="normal-case tracking-normal font-semibold opacity-90" x-text="selected ? ' — ' + selected.label : ''"></span>
                        </button>
                        <button
                            type="button"
                            disabled
                            x-show="!selectedId || selectedStock <= 0"
                            class="w-full sm:w-auto px-8 py-3 bg-neutral-700 text-neutral-400 font-extrabold text-xs tracking-widest rounded uppercase cursor-not-allowed"
                        >
                            <span x-text="!selectedId ? 'Selecciona un color' : 'Agotado'"></span>
                        </button>
                    </div>

                    <div data-cart-qty x-show="cartQty > 0" x-cloak class="space-y-2">
                        <p class="text-xs text-neutral-600">
                            En carrito (<span class="font-bold text-black" x-text="selected?.label"></span>):
                        </p>
                        <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white select-none overflow-hidden rounded-sm">
                            <button type="button" data-cart-action="decrement" :disabled="cartBusy" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-2xl focus:outline-none transition-colors disabled:opacity-40" aria-label="Disminuir">−</button>
                            <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center text-white font-sans font-black text-lg">
                                <span data-cart-qty-value x-text="cartQty"></span>
                            </div>
                            <button type="button" data-cart-action="increment" :disabled="cartBusy || cartQty >= selectedStock" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-xl focus:outline-none transition-colors disabled:opacity-40" aria-label="Aumentar">+</button>
                        </div>
                        <p class="text-xs">
                            Puedes elegir otro color y agregarlo también.
                            <a href="{{ route('shop.cart.index') }}" class="hover:text-orange-400 font-bold">Ver carrito →</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            window.productColorPicker = function (variants, defaultId, fallbackImage) {
                const list = Array.isArray(variants) ? variants : [];
                const initial = list.find((v) => Number(v.id) === Number(defaultId)) || list[0] || null;
                return {
                    variants: list,
                    selectedId: initial ? Number(initial.id) : null,
                    cartQty: Number(initial?.cart_quantity || 0),
                    cartBusy: false,
                    cartError: '',
                    mainImage: initial?.images?.[0]?.path || fallbackImage || 'https://via.placeholder.com/600?text=MotoWorld',
                    activeThumb: 0,
                    get selected() {
                        return this.variants.find((v) => Number(v.id) === Number(this.selectedId)) || null;
                    },
                    get selectedStock() { return Number(this.selected?.available_stock || 0); },
                    get galleryImages() { return this.selected?.images?.length ? this.selected.images : [{ path: this.mainImage }]; },
                    get stockLabel() {
                        if (!this.selected) return 'Sin colores';
                        return this.selectedStock > 0 ? `En Stock (${this.selectedStock} u.)` : 'Agotado';
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
                        this.$nextTick(() => this.syncCartRoot());
                    },
                    init() {
                        this.$nextTick(() => this.selectVariant(this.selectedId));
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
                <div x-show="currentTab === 'technical'" class="space-y-4" style="display: none;" x-data="{ openModal: false }">
                    @if($product->technical_sheet)
                        <p class="text-neutral-700">
                            Previsualiza la ficha técnica de este producto o descárgala directamente.
                        </p>

                        <!-- Tarjeta de vista previa pequeña -->
                        <div class="relative max-w-sm rounded-lg border border-neutral-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow group bg-neutral-50">
                            
                            <!-- Marco / Preview miniatura -->
                            <div class="h-64 w-full overflow-hidden relative">
                                <!-- Mostramos la primera página del PDF en la vista previa -->
                                <iframe 
                                    src="{{ $product->technical_sheet }}#toolbar=0&navpanes=0&scrollbar=0" 
                                    class="w-full h-full pointer-events-none select-none opacity-90 group-hover:opacity-100 transition-opacity"
                                    title="Vista previa ficha técnica">
                                </iframe>

                                <!-- Capa interactiva para abrir el modal -->
                                <button 
                                    type="button"
                                    @click="openModal = true"
                                    class="absolute inset-0 w-full h-full bg-black/20 group-hover:bg-black/40 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                                >
                                    <span class="bg-black/70 px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-2 backdrop-blur-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Ampliar vista previa
                                    </span>
                                </button>
                            </div>

                            <!-- Botones de acción inferiores -->
                            <div class="p-3 bg-white border-t border-neutral-200 flex items-center justify-between gap-2">
                                <button 
                                    type="button"
                                    @click="openModal = true"
                                    class="text-xs font-bold text-neutral-700 hover:text-black flex items-center gap-1"
                                >
                                    Ampliar
                                </button>

                                <a 
                                    href="{{ $product->technical_sheet }}" 
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 rounded bg-primary px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-black transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Descargar PDF
                                </a>
                            </div>
                        </div>

                        <!-- Modal para vista ampliada -->
                        <template x-teleport="body">
                            <div 
                                x-show="openModal" 
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-2 sm:p-4"
                                @keydown.escape.window="openModal = false"
                            >
                                <!-- Forzamos una altura exacta (92vh) y flexbox estricto -->
                                <div class="relative w-full h-full max-h-[92vh] bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
                                    
                                    <!-- Cabecera del modal (shrink-0 asegura que conserve su tamaño y no encoja el PDF) -->
                                    <div class="flex items-center justify-between px-6 py-3 border-b border-neutral-200 bg-neutral-50 shrink-0 h-14">
                                        <h3 class="font-bold text-neutral-800 text-base sm:text-lg">Ficha Técnica</h3>
                                        <div class="flex items-center gap-3">
                                            <button 
                                                type="button"
                                                @click="openModal = false"
                                                class="text-neutral-500 hover:text-black text-2xl font-bold leading-none p-1"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Cuerpo del modal (h-[calc(100%-3.5rem)] calcula exactamente el alto disponible sin desbordar) -->
                                    <div class="w-full h-[calc(100%-3.5rem)] bg-neutral-100 overflow-hidden relative" style="transform: translateZ(0);">
                                        <object 
                                            data="{{ $product->technical_sheet }}#toolbar=1&navpanes=0&scrollbar=1" 
                                            type="application/pdf"
                                            class="w-full h-full block border-0"
                                            style="will-change: transform;"
                                        >
                                            <iframe 
                                                src="{{ $product->technical_sheet }}" 
                                                class="w-full h-full border-0"
                                            >
                                                <p>Tu navegador no soporta vista previa. <a href="{{ $product->technical_sheet }}" target="_blank">Haz clic aquí para descargar el PDF.</a></p>
                                            </iframe>
                                        </object>
                                    </div>

                                </div>
                            </div>
                        </template>

                    @else
                        <p class="text-black italic">No hay ficha técnica disponible para este artículo.</p>
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

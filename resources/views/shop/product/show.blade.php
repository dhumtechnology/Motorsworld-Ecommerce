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
            x-data="productColorPicker(@js($variantsPayload ?? []), {{ (int) ($defaultVariantId ?? 0) }})"
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
                                :class="selectedId === variant.id
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
                    data-store-url="{{ route('shop.cart.items.store', $product) }}"
                    data-increment-url="{{ route('shop.cart.items.increment', $product) }}"
                    data-decrement-url="{{ route('shop.cart.items.decrement', $product) }}"
                    x-init="
                        $el.dataset.variantId = String(selectedId || '');
                        $el.dataset.maxStock = String(selectedStock || 0);
                    "
                    x-effect="
                        $el.dataset.variantId = String(selectedId || '');
                        $el.dataset.maxStock = String(selectedStock || 0);
                    "
                >
                    <p data-cart-error class="hidden text-sm text-rose-400 font-semibold"></p>

                    <div data-cart-add :class="cartQty > 0 ? 'hidden' : ''">
                        <button
                            type="button"
                            data-cart-action="store"
                            x-show="selectedId && selectedStock > 0"
                            class="w-full sm:w-auto px-8 py-3 text-white font-title bold tracking-widest bg-primary rounded hover:bg-black cursor-pointer transition-colors uppercase"
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

                    <div data-cart-qty :class="cartQty > 0 ? '' : 'hidden'" class="space-y-2">
                        <p class="text-xs text-neutral-600">
                            En carrito (<span class="font-bold text-black" x-text="selected?.label"></span>):
                        </p>
                        <div class="flex items-center w-36 h-10 border border-neutral-700 bg-white select-none overflow-hidden rounded-sm">
                            <button type="button" data-cart-action="decrement" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-2xl focus:outline-none transition-colors" aria-label="Disminuir">−</button>
                            <div class="w-12 h-full bg-[#f15a24] flex items-center justify-center text-white font-sans font-black text-lg">
                                <span data-cart-qty-value x-text="Math.max(cartQty, 1)"></span>
                            </div>
                            <button type="button" data-cart-action="increment" :disabled="cartQty >= selectedStock" class="w-12 h-full flex items-center justify-center bg-white text-[#f15a24] hover:bg-neutral-100 font-sans font-black text-xl focus:outline-none transition-colors disabled:opacity-40" aria-label="Aumentar">+</button>
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
            window.productColorPicker = function (variants, defaultId) {
                const list = Array.isArray(variants) ? variants : [];
                const initial = list.find((v) => v.id === defaultId) || list[0] || null;
                return {
                    variants: list,
                    selectedId: initial?.id || null,
                    mainImage: initial?.images?.[0]?.path || @js($product->image ?: 'https://via.placeholder.com/600?text=MotoWorld'),
                    activeThumb: 0,
                    get selected() { return this.variants.find((v) => v.id === this.selectedId) || null; },
                    get selectedStock() { return Number(this.selected?.available_stock || 0); },
                    get cartQty() { return Number(this.selected?.cart_quantity || 0); },
                    get galleryImages() { return this.selected?.images?.length ? this.selected.images : [{ path: this.mainImage }]; },
                    get stockLabel() {
                        if (!this.selected) return 'Sin colores';
                        return this.selectedStock > 0 ? `En Stock (${this.selectedStock} u.)` : 'Agotado';
                    },
                    selectVariant(id) {
                        this.selectedId = id;
                        this.activeThumb = 0;
                        this.mainImage = this.selected?.images?.[0]?.path || this.mainImage;

                        this.$nextTick(() => {
                            const root = this.$root.querySelector('[data-product-cart]')
                                || document.querySelector('[data-product-cart]');
                            if (!root) return;

                            root.dataset.variantId = String(this.selectedId || '');
                            root.dataset.maxStock = String(this.selectedStock || 0);

                            const qty = this.cartQty;
                            const addBlock = root.querySelector('[data-cart-add]');
                            const qtyBlock = root.querySelector('[data-cart-qty]');
                            const qtyValue = root.querySelector('[data-cart-qty-value]');
                            const incrementBtn = root.querySelector('[data-cart-action="increment"]');
                            const errorEl = root.querySelector('[data-cart-error]');

                            errorEl?.classList.add('hidden');

                            if (qty <= 0) {
                                addBlock?.classList.remove('hidden');
                                qtyBlock?.classList.add('hidden');
                            } else {
                                addBlock?.classList.add('hidden');
                                qtyBlock?.classList.remove('hidden');
                                if (qtyValue) qtyValue.textContent = String(qty);
                                if (incrementBtn) incrementBtn.disabled = qty >= this.selectedStock;
                            }
                        });
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

                <button type="button"
                        @click="currentTab = 'technical'"
                        :class="currentTab === 'technical' ? 'text-primary border-[#f15a24]' : 'text-neutral-400 border-transparent hover:text-secondary'"
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
                            Descarga o visualiza la ficha técnica de este producto en formato PDF.
                        </p>
                        <a
                            href="{{ $product->technical_sheet }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded bg-primary px-5 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-black transition-colors"
                        >
                            Ver ficha técnica (PDF)
                        </a>
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

<div
    class="mt-8 space-y-5 border-t border-border pt-6"
    x-data="productVariantsForm({
        variants: @js($initialVariants),
        colors: @js($colorsJson),
        defaultImages: @js($defaultImages),
        defaultStock: {{ (int) $initialDefaultStock }},
        mode: @js($initialProductMode),
        coloredVariantIds: @js($initialColoredVariantIds),
    })"
>
    <div>
        <h3 class="text-sm font-title text-text">Tipo de producto</h3>
        <p class="mt-1 text-xs text-muted">Elige cómo se vende este producto. Solo se guarda lo de la pestaña activa.</p>
    </div>

    <div class="inline-flex rounded-lg border border-border bg-secondary p-1 gap-1">
        <button type="button"
                @click="setMode('unique')"
                :class="productMode === 'unique' ? 'bg-white text-text shadow-sm border-border' : 'bg-transparent text-muted hover:text-text border-transparent'"
                class="rounded-md border px-4 py-2 text-xs font-bold uppercase tracking-wide transition-colors">
            Producto único
        </button>
        <button type="button"
                @click="setMode('variants')"
                :class="productMode === 'variants' ? 'bg-white text-text shadow-sm border-border' : 'bg-transparent text-muted hover:text-text border-transparent'"
                class="rounded-md border px-4 py-2 text-xs font-bold uppercase tracking-wide transition-colors">
            Producto con variantes
        </button>
    </div>
    <input type="hidden" name="product_mode" :value="productMode">

    {{-- PRODUCTO ÚNICO --}}
    <template x-if="productMode === 'unique'">
        <div class="space-y-5 rounded-xl border border-border bg-white p-5 shadow-sm">
            <template x-for="id in initialColoredVariantIds" :key="'rm-unique-' + id">
                <input type="hidden" name="remove_variant_ids[]" :value="id">
            </template>

            <div>
                <h4 class="text-sm font-semibold text-text">Stock e imágenes</h4>
                <p class="mt-1 text-xs text-muted">Un solo stock y una galería. Arrastra las imágenes para ordenar; la primera es la principal.</p>
            </div>

            <div class="max-w-xs">
                <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Stock disponible *</label>
                <input type="number" min="0" name="default_available_stock"
                       x-model.number="defaultStock"
                       class="{{ $fieldClass }}">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imágenes</label>
                @include('admin.products._image-dropzone', ['scope' => 'default'])
            </div>
        </div>
    </template>

    {{-- PRODUCTO CON VARIANTES --}}
    <template x-if="productMode === 'variants'">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h4 class="text-sm font-semibold text-text">Variantes por color</h4>
                    <p class="mt-1 text-xs text-muted">Cada bloque es una opción vendible (un color o una combinación).</p>
                </div>
                <button type="button" @click="addVariant()"
                        class="rounded bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-primary-hover">
                    + Agregar variante
                </button>
            </div>

            <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                 x-show="variants.length > 0 && visibleGalleryItems(defaultGalleryItems, defaultRemoveImageIds).length > 0 && !variants.some(v => v.id)"
                 x-cloak>
                Al guardar, las imágenes del producto único (si las había) pasarán a la primera variante.
            </div>

            <template x-for="(variant, index) in variants" :key="variant._key">
                <div class="rounded-xl border border-border bg-white p-5 shadow-sm space-y-5">
                    <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id || ''">

                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border pb-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-semibold text-text">
                                Variante <span x-text="index + 1"></span>
                                <span class="text-muted font-normal text-sm" x-show="variantLabel(variant)">
                                    — <span x-text="variantLabel(variant)"></span>
                                </span>
                            </p>
                        </div>
                        <button type="button" @click="removeVariant(index)"
                                class="text-xs font-bold uppercase tracking-wide text-red-600 hover:underline"
                                x-show="variants.length > 1">
                            Quitar variante
                        </button>
                    </div>

                    <div class="max-w-md">
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">SKU / Código de barras *</label>
                        <input type="text" required maxlength="100"
                               :name="`variants[${index}][sku]`"
                               x-model="variant.sku"
                               class="{{ $fieldClass }} font-mono uppercase"
                               placeholder="Ej. 7750123456789">
                    </div>

                    <div class="max-w-xs">
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Stock disponible *</label>
                        <input type="number" min="0" required
                               :name="`variants[${index}][available_stock]`"
                               x-model.number="variant.available_stock"
                               class="{{ $fieldClass }}">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted">Colores</label>
                        <p class="text-xs text-muted">Busca un color existente o escribe un nombre nuevo para crearlo. Puedes combinar varios.</p>

                        <div class="flex flex-wrap gap-2 min-h-[2rem]">
                            <template x-for="colorId in variant.color_ids" :key="'chip-' + variant._key + '-' + colorId">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-secondary px-2.5 py-1 text-xs font-semibold text-text">
                                    <input type="hidden" :name="`variants[${index}][color_ids][]`" :value="colorId">
                                    <span class="inline-block h-3 w-3 rounded-full border border-border"
                                          :style="`background:${colorById(colorId)?.hex || '#d1d5db'}`"></span>
                                    <span x-text="colorById(colorId)?.name || ('#' + colorId)"></span>
                                    <button type="button" @click="removeColorFromVariant(variant, colorId)"
                                            class="ml-0.5 text-red-600 hover:text-red-800 font-bold leading-none">×</button>
                                </span>
                            </template>
                            <template x-for="(nc, ncIndex) in variant.new_colors" :key="'nchip-' + variant._key + '-' + ncIndex">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary">
                                    <input type="hidden" :name="`variants[${index}][new_colors][${ncIndex}][name]`" :value="nc.name">
                                    <input type="hidden" :name="`variants[${index}][new_colors][${ncIndex}][hex]`" :value="nc.hex">
                                    <span class="inline-block h-3 w-3 rounded-full border border-border" :style="`background:${nc.hex || '#FF6600'}`"></span>
                                    <span x-text="nc.name"></span>
                                    <span class="text-[10px] opacity-70">nuevo</span>
                                    <button type="button" @click="removeNewColor(variant, ncIndex)"
                                            class="ml-0.5 text-red-600 hover:text-red-800 font-bold leading-none">×</button>
                                </span>
                            </template>
                            <p class="text-xs text-muted" x-show="!hasColors(variant)">Ningún color aún.</p>
                        </div>

                        <div class="relative" @click.outside="variant.colorPickerOpen = false">
                            <div class="flex items-stretch overflow-hidden rounded-lg border border-border bg-white focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                                <input type="text"
                                       x-model="variant.colorQuery"
                                       @focus="variant.colorPickerOpen = true"
                                       @input="variant.colorPickerOpen = true"
                                       @keydown.enter.prevent="commitColorQuery(variant)"
                                       placeholder="Buscar o crear color (ej. Rojo)..."
                                       class="min-w-0 flex-1 border-0 bg-transparent px-4 py-2.5 text-sm text-text outline-none focus:ring-0">
                                <div class="flex items-center gap-1.5 border-l border-border px-2 shrink-0"
                                     x-show="canCreateFromQuery(variant)"
                                     x-cloak>
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-muted hidden sm:inline">Hex</span>
                                    <input type="color"
                                           x-model="variant.newColorHex"
                                           class="h-8 w-10 cursor-pointer rounded border border-border bg-white p-0.5"
                                           title="Color del nuevo tono"
                                           @click.stop>
                                </div>
                            </div>

                            <div
                                class="absolute z-30 mt-1 w-full rounded-lg border border-border bg-white shadow-lg overflow-hidden"
                                x-show="variant.colorPickerOpen"
                                x-cloak
                            >
                                <ul class="max-h-52 overflow-y-auto py-1">
                                    <template x-for="color in filteredColors(variant)" :key="'opt-' + variant._key + '-' + color.id">
                                        <li>
                                            <button type="button"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-secondary"
                                                    @click="selectExistingColor(variant, color)">
                                                <span class="inline-block h-3.5 w-3.5 rounded-full border border-border"
                                                      :style="color.hex ? `background:${color.hex}` : 'background:#d1d5db'"></span>
                                                <span x-text="color.name"></span>
                                            </button>
                                        </li>
                                    </template>
                                    <li class="px-3 py-2 text-xs text-muted" x-show="filteredColors(variant).length === 0 && !canCreateFromQuery(variant)">
                                        Escribe un nombre para buscar o crear.
                                    </li>
                                </ul>
                                <div class="border-t border-border bg-primary-soft/40 px-3 py-2" x-show="canCreateFromQuery(variant)">
                                    <button type="button"
                                            class="flex w-full items-center gap-2 text-left text-sm font-semibold text-primary hover:underline"
                                            @click="createColorFromQuery(variant)">
                                        <input type="color"
                                               x-model="variant.newColorHex"
                                               class="h-7 w-8 cursor-pointer rounded border border-border bg-white p-0.5 shrink-0"
                                               title="Elegir color"
                                               @click.stop>
                                        <span>Crear «<span x-text="variant.colorQuery.trim()"></span>»</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imágenes</label>
                        <p class="text-xs text-muted mb-3">Arrastra las imágenes para ordenar; la primera es la principal.</p>
                        @include('admin.products._image-dropzone', ['scope' => 'variant'])
                    </div>
                </div>
            </template>

            <template x-for="id in removedVariantIds" :key="'rm-var-' + id">
                <input type="hidden" name="remove_variant_ids[]" :value="id">
            </template>

            <p class="text-sm text-muted" x-show="variants.length === 0">
                Agrega al menos una variante con color para guardar este producto.
            </p>
        </div>
    </template>
</div>

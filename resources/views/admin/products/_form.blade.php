@php
    /** @var \App\Models\Products\Product|null $product */
    $product = $product ?? null;
    $isEdit = $product !== null;
    /** @var \Illuminate\Support\Collection<int, \App\Models\Products\Color> $colors */
    $colors = $colors ?? collect();

    $statusLabels = [
        'active' => 'Activo',
        'pending' => 'Pendiente',
        'disabled' => 'Inactivo',
        'locked' => 'Bloqueado',
    ];

    $selectedBrandId = old(
        'brand_id',
        $product?->vehicleModel?->brand_id ?? $product?->vehicleModel?->brand?->id
    );
    $selectedModelId = old('model_id', $product?->model_id);
    $selectedCategoryId = old('category_id', $product?->category_id);

    $fieldClass = 'w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';
    $readonlyClass = 'w-full rounded border border-border bg-secondary px-4 py-2.5 text-sm text-muted cursor-not-allowed';

    $initialVariants = old('variants');
    if (! is_array($initialVariants)) {
        $initialVariants = ($product?->variants ?? collect())->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'color_ids' => $variant->colors->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
                'new_colors' => [],
                'available_stock' => (int) ($variant->inventory?->available_stock ?? 0),
                'images' => $variant->images->map(fn ($img) => [
                    'id' => $img->id,
                    'path' => $img->path,
                    'is_primary' => (bool) $img->is_primary,
                ])->values()->all(),
                'remove_image_ids' => [],
            ];
        })->values()->all();
    }

    $colorsJson = $colors->map(fn ($c) => [
        'id' => (string) $c->id,
        'name' => $c->name,
        'hex' => $c->hex,
    ])->values();
@endphp

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-2" data-product-form
     data-brands-store-url="{{ route('admin.brands.store') }}"
     data-categories-store-url="{{ route('admin.categories.store') }}"
     data-models-store-url="{{ route('admin.models.store') }}">

    <div>
        <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Código base</label>
        @if ($isEdit)
            <input id="sku" type="text" value="{{ $product->sku }}" readonly class="{{ $readonlyClass }}">
            <p class="mt-1.5 text-xs text-muted">Prefijo automático. El SKU vendible se genera por cada color.</p>
        @else
            <input id="sku" type="text" value="" readonly
                   placeholder="Se generará automáticamente al guardar"
                   class="{{ $readonlyClass }}">
            <p class="mt-1.5 text-xs text-muted">El SKU de cada color se genera al guardar (producto + colores).</p>
        @endif
    </div>

    <div>
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $product?->name) }}"
               class="{{ $fieldClass }}">
    </div>

    <div class="flex items-end gap-2.5">
        <div class="min-w-0 flex-1">
            <x-searchable-select
                name="category_id"
                label="Categoría"
                :options="$categories"
                :selected="$selectedCategoryId"
                placeholder="Seleccionar categoría"
                :required="true"
                data-ss-id="category"
            />
        </div>
        <div class="shrink-0 flex flex-col">
            <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-transparent select-none leading-none" aria-hidden="true">&nbsp;</span>
            <button type="button" data-open-quick-modal="category-modal"
                    class="admin-quick-add-btn"
                    title="Nueva categoría" aria-label="Nueva categoría">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span class="hidden sm:inline">Nuevo</span>
            </button>
        </div>
    </div>

    <div class="flex items-end gap-2.5">
        <div class="min-w-0 flex-1">
            <x-searchable-select
                name="brand_id"
                label="Marca"
                :options="$brands"
                :selected="$selectedBrandId"
                placeholder="Sin marca"
                empty-label="Sin marca"
                data-ss-id="brand"
                data-brand-select
            />
        </div>
        <div class="shrink-0 flex flex-col">
            <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-transparent select-none leading-none" aria-hidden="true">&nbsp;</span>
            <button type="button" data-open-quick-modal="brand-modal"
                    class="admin-quick-add-btn"
                    title="Nueva marca" aria-label="Nueva marca">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span class="hidden sm:inline">Nuevo</span>
            </button>
        </div>
    </div>

    <div class="flex items-end gap-2.5">
        <div class="min-w-0 flex-1">
            <x-searchable-select
                name="model_id"
                label="Modelo"
                :options="$models"
                :selected="$selectedModelId"
                placeholder="Sin modelo"
                empty-label="Sin modelo"
                filter-key="brand_id"
                data-ss-id="model"
                data-model-select
                data-filter-source="brand"
            />
        </div>
        <div class="shrink-0 flex flex-col">
            <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-transparent select-none leading-none" aria-hidden="true">&nbsp;</span>
            <button type="button" data-open-quick-modal="model-modal"
                    class="admin-quick-add-btn"
                    title="Nuevo modelo" aria-label="Nuevo modelo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span class="hidden sm:inline">Nuevo</span>
            </button>
        </div>
    </div>
    <p class="lg:col-span-2 -mt-3 text-xs text-muted">Elige primero la marca para filtrar sus modelos.</p>

    <div>
        <label for="price_amount" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Precio *</label>
        <input id="price_amount" name="price_amount" type="number" step="0.01" min="0" required
               value="{{ old('price_amount', $product?->price_amount) }}"
               class="{{ $fieldClass }}">
    </div>

    <div>
        <label for="currency" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Moneda *</label>
        <select id="currency" name="currency" required class="{{ $fieldClass }}">
            @foreach (['PEN' => 'PEN (Soles)', 'USD' => 'USD (Dólares)'] as $code => $label)
                <option value="{{ $code }}" @selected(old('currency', $product?->currency ?? 'PEN') === $code)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado *</label>
        <select id="status" name="status" required class="{{ $fieldClass }}">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $product?->status?->value ?? 'pending') === $status->value)>
                    {{ $statusLabels[$status->value] ?? $status->value }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="lg:col-span-2">
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
        <textarea id="description" name="description" rows="4" class="{{ $fieldClass }}">{{ old('description', $product?->description) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label for="additional_information" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Información adicional</label>
        <textarea id="additional_information" name="additional_information" rows="3" class="{{ $fieldClass }}">{{ old('additional_information', $product?->additional_information) }}</textarea>
    </div>
</div>

<div
    class="mt-8 space-y-4 border-t border-border pt-6"
    x-data="productVariantsForm(@js($initialVariants), @js($colorsJson))"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-title text-text">Colores / combinaciones</h3>
            <p class="mt-1 text-xs text-muted">
                Cada fila es un color o combinación (ej. Rojo / Blanco), con su stock, fotos y SKU automático.
            </p>
        </div>
        <button type="button" @click="addVariant()"
                class="rounded bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-primary-hover">
            Agregar color
        </button>
    </div>

    <template x-for="(variant, index) in variants" :key="variant._key">
        <div class="rounded-lg border border-border bg-secondary/40 p-4 space-y-4">
            <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id || ''">

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-text">Combinación <span x-text="index + 1"></span></p>
                    <p class="text-xs text-muted" x-show="variant.sku">
                        SKU: <span class="font-mono" x-text="variant.sku"></span>
                    </p>
                    <p class="text-xs text-muted" x-show="!variant.sku">SKU se generará al guardar</p>
                </div>
                <button type="button" @click="removeVariant(index)"
                        class="text-xs font-bold uppercase tracking-wide text-red-600 hover:underline">
                    Quitar
                </button>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Colores existentes</label>
                    <div class="max-h-40 overflow-y-auto rounded border border-border bg-surface p-2 space-y-1">
                        <template x-for="color in availableColors" :key="color.id">
                            <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-secondary cursor-pointer">
                                <input type="checkbox"
                                       :name="`variants[${index}][color_ids][]`"
                                       :value="color.id"
                                       :checked="variant.color_ids.includes(color.id)"
                                       @change="toggleColor(variant, color.id, $event.target.checked)"
                                       class="rounded border-border-strong text-primary focus:ring-primary">
                                <span class="inline-block h-3.5 w-3.5 rounded-full border border-border"
                                      :style="color.hex ? `background:${color.hex}` : 'background:#d1d5db'"></span>
                                <span x-text="color.name"></span>
                            </label>
                        </template>
                        <p class="px-2 py-1 text-xs text-muted" x-show="availableColors.length === 0">Aún no hay colores. Crea uno abajo.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Stock disponible *</label>
                    <input type="number" min="0" required
                           :name="`variants[${index}][available_stock]`"
                           x-model.number="variant.available_stock"
                           class="{{ $fieldClass }}">
                    <p class="mt-1.5 text-xs text-muted">Puedes combinar 2+ colores marcándolos juntos.</p>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted">Colores nuevos en esta combinación</label>
                    <button type="button" @click="addNewColor(variant)" class="text-xs font-bold text-primary hover:underline">+ Nuevo color</button>
                </div>
                <template x-for="(nc, ncIndex) in variant.new_colors" :key="ncIndex">
                    <div class="flex flex-wrap gap-2 items-center">
                        <input type="text" :name="`variants[${index}][new_colors][${ncIndex}][name]`" x-model="nc.name"
                               placeholder="Nombre (ej. Rojo)" class="{{ $fieldClass }} max-w-xs">
                        <input type="color" :name="`variants[${index}][new_colors][${ncIndex}][hex]`" x-model="nc.hex"
                               class="h-10 w-14 rounded border border-border bg-surface p-1">
                        <button type="button" @click="variant.new_colors.splice(ncIndex, 1)" class="text-xs text-red-600">Quitar</button>
                    </div>
                </template>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imagen principal</label>
                    <template x-if="variant.images.filter(i => i.is_primary && !variant.remove_image_ids.includes(i.id)).length">
                        <div class="mb-2 flex items-center gap-3 rounded border border-border bg-surface p-2">
                            <img :src="variant.images.find(i => i.is_primary && !variant.remove_image_ids.includes(i.id))?.path" class="h-14 w-14 rounded object-cover border border-border" alt="">
                            <label class="text-xs text-red-600 cursor-pointer">
                                <input type="checkbox"
                                       :name="`variants[${index}][remove_image_ids][]`"
                                       :value="variant.images.find(i => i.is_primary)?.id"
                                       @change="toggleRemoveImage(variant, variant.images.find(i => i.is_primary)?.id, $event.target.checked)">
                                Eliminar
                            </label>
                        </div>
                    </template>
                    <input type="file" accept="image/*" :name="`variants[${index}][primary_image]`"
                           class="block w-full text-sm text-text-soft file:mr-3 file:rounded file:border-0 file:bg-primary file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imágenes secundarias</label>
                    <div class="mb-2 flex flex-wrap gap-2" x-show="variant.images.some(i => !i.is_primary)">
                        <template x-for="img in variant.images.filter(i => !i.is_primary)" :key="img.id">
                            <div class="relative" x-show="!variant.remove_image_ids.includes(img.id)">
                                <img :src="img.path" class="h-14 w-14 rounded object-cover border border-border" alt="">
                                <label class="absolute inset-x-0 bottom-0 bg-black/70 text-[9px] text-center text-red-200 cursor-pointer">
                                    <input type="checkbox" class="sr-only"
                                           :name="`variants[${index}][remove_image_ids][]`"
                                           :value="img.id"
                                           @change="toggleRemoveImage(variant, img.id, $event.target.checked)">
                                    Del
                                </label>
                            </div>
                        </template>
                    </div>
                    <input type="file" accept="image/*" multiple :name="`variants[${index}][secondary_images][]`"
                           class="block w-full text-sm text-text-soft file:mr-3 file:rounded file:border-0 file:bg-primary file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                </div>
            </div>
        </div>
    </template>

    <template x-for="id in removedVariantIds" :key="id">
        <input type="hidden" name="remove_variant_ids[]" :value="id">
    </template>

    <p class="text-sm text-muted" x-show="variants.length === 0">
        Sin colores aún. Agrega al menos uno para poder vender el producto con stock.
    </p>
</div>

<div class="mt-8 space-y-4 border-t border-border pt-6" data-product-technical-sheet>
    <div>
        <h3 class="text-sm font-title text-text">Ficha técnica (PDF)</h3>
        <p class="mt-1 text-xs text-muted">Documento opcional en PDF (máx. 10 MB). Se muestra en la ficha pública del producto.</p>
    </div>

    @if ($product?->technical_sheet)
        <div class="flex flex-wrap items-center gap-3 rounded border border-border bg-secondary p-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-border bg-surface text-red-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-text font-semibold">Ficha técnica actual</p>
                <a href="{{ $product->technical_sheet }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-sky-700 hover:underline">
                    Ver / descargar PDF
                </a>
            </div>
            <label class="inline-flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                <input type="checkbox" name="remove_technical_sheet" value="1"
                       class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500">
                Eliminar
            </label>
        </div>
    @endif

    <div>
        <label for="technical_sheet" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
            {{ $product?->technical_sheet ? 'Reemplazar PDF' : 'Subir PDF' }}
        </label>
        <input
            id="technical_sheet"
            type="file"
            name="technical_sheet"
            accept="application/pdf,.pdf"
            class="block w-full text-sm text-text-soft file:mr-4 file:rounded file:border-0 file:bg-primary file:px-4 file:py-2 file:text-xs file:font-bold file:uppercase file:tracking-wide file:text-white hover:file:bg-primary-hover"
        >
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit"
            class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
    </button>
    <a href="{{ route('admin.products.index') }}"
       class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

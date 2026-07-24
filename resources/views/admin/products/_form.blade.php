@php
    /** @var \App\Models\Products\Product|null $product */
    $product = $product ?? null;
    $isEdit = $product !== null;
    $existingImages = $product?->images ?? collect();
    $primaryImage = $existingImages->firstWhere('is_primary', true) ?? $existingImages->first();
    $secondaryImages = $existingImages->where('id', '!=', $primaryImage?->id)->values();
    $availableStock = (int) ($product?->inventory?->available_stock ?? 0);
    $reservedStock = (int) ($product?->inventory?->reserved_stock ?? 0);

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
        <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">SKU</label>
        @if ($isEdit)
            <input id="sku" type="text" value="{{ $product->sku }}" readonly class="{{ $readonlyClass }}">
        @else
            <input id="sku" name="sku" type="text" value="" readonly
                   placeholder="Se generará automáticamente al guardar"
                   class="{{ $readonlyClass }}">
            <p class="mt-1.5 text-xs text-muted">Código único generado por el sistema.</p>
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
        <label for="currency" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Moneda</label>
        <input id="currency" type="text" value="PEN" readonly class="{{ $readonlyClass }}">
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

    <div>
        <label for="available_stock" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
            Stock disponible{{ $isEdit ? '' : ' *' }}
        </label>
        @if ($isEdit)
            <input id="available_stock" type="number" value="{{ $availableStock }}" readonly class="{{ $readonlyClass }}">
        @else
            <input id="available_stock" name="available_stock" type="number" min="0" required
                   value="{{ old('available_stock', 0) }}"
                   class="{{ $fieldClass }}">
        @endif
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Stock reservado</label>
        <div class="{{ $readonlyClass }}">{{ $reservedStock }}</div>
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

<div class="mt-8 space-y-6 border-t border-border pt-6" data-product-images>
    <div>
        <h3 class="text-sm font-title text-text">Imágenes</h3>
        <p class="mt-1 text-xs text-muted">Sube archivos locales (JPG, PNG, WEBP o GIF, máx. 5 MB cada una).</p>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imagen principal</label>

        @if ($primaryImage)
            <div class="mb-3 flex items-center gap-3 rounded border border-border bg-secondary p-3" data-existing-primary>
                <img src="{{ $primaryImage->path }}" alt="" class="h-16 w-16 rounded object-cover border border-border">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-text font-semibold truncate">Imagen actual</p>
                    <p class="text-xs text-muted">Se reemplazará si subes otra.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                    <input type="checkbox" name="remove_image_ids[]" value="{{ $primaryImage->id }}"
                           class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500">
                    Eliminar
                </label>
            </div>
        @endif

        <div data-dropzone="primary"
             class="flex min-h-[160px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-secondary px-4 py-8 text-center transition-colors hover:border-primary">
            <input id="primary_image" type="file" name="primary_image" accept="image/*" class="sr-only" data-file-input="primary">
            <button type="button" data-dropzone-trigger class="flex flex-col items-center cursor-pointer">
                <svg class="h-8 w-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V19a2 2 0 002 2h14a2 2 0 002-2v-2.5M16 8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <p class="mt-3 text-sm text-text-soft">Arrastra la imagen principal o haz clic para seleccionar</p>
                <p class="mt-1 text-xs text-muted">Una sola imagen</p>
            </button>
            <div data-preview="primary" class="mt-4 hidden w-full"></div>
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imágenes secundarias</label>

        @if ($secondaryImages->isNotEmpty())
            <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($secondaryImages as $image)
                    <div class="relative overflow-hidden rounded border border-border bg-secondary">
                        <img src="{{ $image->path }}" alt="" class="h-28 w-full object-cover">
                        <label class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-black/70 py-1.5 text-[11px] font-bold uppercase tracking-wide text-red-300 cursor-pointer">
                            <input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}"
                                   class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500">
                            Eliminar
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <div data-dropzone="secondary"
             class="flex min-h-[180px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-secondary px-4 py-8 text-center transition-colors hover:border-primary">
            <input id="secondary_images" type="file" name="secondary_images[]" accept="image/*" multiple class="sr-only" data-file-input="secondary">
            <button type="button" data-dropzone-trigger class="flex flex-col items-center cursor-pointer">
                <svg class="h-8 w-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-3 text-sm text-text-soft">Arrastra varias imágenes o haz clic para seleccionar</p>
                <p class="mt-1 text-xs text-muted">Puedes agregar más sin perder la selección anterior</p>
            </button>
            <div data-preview="secondary" class="mt-4 hidden w-full grid grid-cols-2 gap-3 sm:grid-cols-4"></div>
        </div>
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

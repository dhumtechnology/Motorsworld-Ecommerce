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

    $mapImages = static function ($images) {
        return collect($images)
            ->sortByDesc(fn ($img) => (bool) (is_array($img) ? ($img['is_primary'] ?? false) : $img->is_primary))
            ->values()
            ->map(function ($img) {
                if (is_array($img)) {
                    return [
                        'id' => $img['id'],
                        'path' => $img['path'],
                        'is_primary' => (bool) ($img['is_primary'] ?? false),
                    ];
                }

                return [
                    'id' => $img->id,
                    'path' => $img->path,
                    'is_primary' => (bool) $img->is_primary,
                ];
            })
            ->all();
    };

    $productVariants = $product?->variants ?? collect();
    $standardVariant = $productVariants->first(fn ($variant) => $variant->colors->isEmpty());
    $coloredVariants = $productVariants->filter(fn ($variant) => $variant->colors->isNotEmpty())->values();

    $initialVariants = old('variants');
    if (! is_array($initialVariants)) {
        $initialVariants = $coloredVariants->map(function ($variant) use ($mapImages) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'color_ids' => $variant->colors->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
                'new_colors' => [],
                'available_stock' => (int) ($variant->inventory?->available_stock ?? 0),
                'images' => $mapImages($variant->images),
                'remove_image_ids' => [],
            ];
        })->values()->all();
    }

    $defaultImages = old('default_images_meta');
    if (! is_array($defaultImages)) {
        $defaultImages = ($product?->images ?? collect())
            ->filter(fn ($img) => $img->product_variant_id === null)
            ->values();

        if ($defaultImages->isEmpty() && $standardVariant) {
            $defaultImages = $standardVariant->images;
        }

        $defaultImages = $mapImages($defaultImages);
    }

    $initialDefaultStock = old(
        'default_available_stock',
        (int) ($standardVariant?->inventory?->available_stock ?? 0),
    );

    $initialProductMode = old('product_mode');
    if (! in_array($initialProductMode, ['unique', 'variants'], true)) {
        $initialProductMode = $coloredVariants->isNotEmpty() ? 'variants' : 'unique';
    }

    $initialColoredVariantIds = $coloredVariants->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

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

    <div class="lg:col-span-2">
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
                <option value="{{ $status->value }}" @selected(old('status', $product?->status?->value ?? 'active') === $status->value)>
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

    {{-- Ficha técnica: justo después de información adicional --}}
    <div class="lg:col-span-2 space-y-3 border-t border-border pt-5" data-product-technical-sheet>
        <div>
            <h3 class="text-sm font-title text-text">Ficha técnica (PDF)</h3>
            <p class="mt-1 text-xs text-muted">Opcional. Arrastra un PDF o haz clic para seleccionarlo (máx. 10 MB).</p>
        </div>

        @if ($product?->technical_sheet)
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-white p-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-border bg-secondary text-red-600">
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

        <div
            data-dropzone="technical"
            class="rounded-lg border-2 border-dashed border-border bg-white px-4 py-8 text-center transition-colors cursor-pointer hover:border-primary/50 hover:bg-primary-soft/10"
        >
            <input type="file" name="technical_sheet" accept="application/pdf,.pdf" data-file-input="technical" class="sr-only">
            <div data-dropzone-empty class="space-y-2">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-text">
                    {{ $product?->technical_sheet ? 'Arrastra un PDF para reemplazar' : 'Arrastra tu ficha técnica aquí' }}
                </p>
                <p class="text-xs text-muted">o <button type="button" data-dropzone-trigger class="font-bold text-primary hover:underline">elige un archivo</button></p>
            </div>
            <div data-preview="technical" class="hidden"></div>
        </div>
    </div>
</div>


@include('admin.products._product-type-tabs')

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

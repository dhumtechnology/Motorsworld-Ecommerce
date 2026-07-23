@php
    /** @var \App\Models\Products\Product|null $product */
    $product = $product ?? null;
    $isEdit = $product !== null;
    $existingImages = $product?->images ?? collect();
    $primaryImage = $existingImages->firstWhere('is_primary', true) ?? $existingImages->first();
    $secondaryImages = $existingImages->where('id', '!=', $primaryImage?->id)->values();
    $reservedStock = (int) ($product?->inventory?->reserved_stock ?? 0);

    $statusLabels = [
        'active' => 'Activo',
        'pending' => 'Pendiente',
        'disabled' => 'Inactivo',
        'locked' => 'Bloqueado',
    ];

    $selectedBrandId = (int) old(
        'brand_id',
        $product?->vehicleModel?->brand_id ?? $product?->vehicleModel?->brand?->id
    );
    $selectedModelId = (int) old('model_id', $product?->model_id);
@endphp

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-2" data-brand-model-form>
    <div>
        <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">SKU *</label>
        <input id="sku" name="sku" type="text" required value="{{ old('sku', $product?->sku) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $product?->name) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="category_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Categoría *</label>
        <select id="category_id" name="category_id" required
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">Seleccionar categoría</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product?->category_id) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="brand_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Marca</label>
        <select id="brand_id" name="brand_id" data-brand-select
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">Sin marca</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected($selectedBrandId === $brand->id)>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="model_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Modelo</label>
        <select id="model_id" name="model_id" data-model-select
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">Sin modelo</option>
            @foreach ($models as $model)
                <option
                    value="{{ $model->id }}"
                    data-brand-id="{{ $model->brand_id }}"
                    @selected($selectedModelId === $model->id)
                >
                    {{ $model->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-muted">Elige primero la marca para ver sus modelos.</p>
    </div>

    <div>
        <label for="price_amount" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Precio *</label>
        <input id="price_amount" name="price_amount" type="number" step="0.01" min="0" required
               value="{{ old('price_amount', $product?->price_amount) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="currency" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Moneda</label>
        <input id="currency" type="text" value="PEN" readonly
               class="w-full rounded border border-border bg-secondary px-4 py-2.5 text-sm text-muted cursor-not-allowed">
        <p class="mt-1.5 text-xs text-muted">Fijada en soles (PEN).</p>
    </div>

    <div>
        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado *</label>
        <select id="status" name="status" required
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $product?->status?->value ?? 'pending') === $status->value)>
                    {{ $statusLabels[$status->value] ?? $status->value }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="available_stock" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Stock disponible *</label>
        <input id="available_stock" name="available_stock" type="number" min="0" required
               value="{{ old('available_stock', $product?->inventory?->available_stock ?? 0) }}"
        <p class="mt-1.5 text-xs text-muted">Preferible gestionar altas/bajas desde Entradas y Salidas. Un cambio aquí genera un ajuste en el kardex.</p>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Stock reservado</label>
        <div class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text-soft">
            {{ $reservedStock }}
        </div>
        <p class="mt-1.5 text-xs text-muted">Se mantiene al editar el stock disponible.</p>
    </div>

    <div class="lg:col-span-2">
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
        <textarea id="description" name="description" rows="4"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('description', $product?->description) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label for="additional_information" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Información adicional</label>
        <textarea id="additional_information" name="additional_information" rows="3"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('additional_information', $product?->additional_information) }}</textarea>
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
                    <input
                        type="checkbox"
                        name="remove_image_ids[]"
                        value="{{ $primaryImage->id }}"
                        class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500"
                    >
                    Eliminar
                </label>
            </div>
        @endif

        <div
            data-dropzone="primary"
            class="flex min-h-[160px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-secondary px-4 py-8 text-center transition-colors hover:border-primary"
        >
            <input
                id="primary_image"
                type="file"
                name="primary_image"
                accept="image/*"
                class="sr-only"
                data-file-input="primary"
            >
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
                            <input
                                type="checkbox"
                                name="remove_image_ids[]"
                                value="{{ $image->id }}"
                                class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500"
                            >
                            Eliminar
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <div
            data-dropzone="secondary"
            class="flex min-h-[180px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-secondary px-4 py-8 text-center transition-colors hover:border-primary"
        >
            <input
                id="secondary_images"
                type="file"
                name="secondary_images[]"
                accept="image/*"
                multiple
                class="sr-only"
                data-file-input="secondary"
            >
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

<script>
    (function () {
        const root = document.querySelector('[data-brand-model-form]');
        if (!root) return;

        const brandSelect = root.querySelector('[data-brand-select]');
        const modelSelect = root.querySelector('[data-model-select]');
        if (!brandSelect || !modelSelect) return;

        const syncModels = (resetIfInvalid) => {
            const brandId = brandSelect.value;
            let selectedStillVisible = false;

            Array.from(modelSelect.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const matches = !brandId || option.dataset.brandId === brandId;
                option.hidden = !matches;

                if (matches && option.selected) {
                    selectedStillVisible = true;
                }
            });

            if (resetIfInvalid && !selectedStillVisible) {
                modelSelect.value = '';
            }
        };

        brandSelect.addEventListener('change', () => syncModels(true));
        syncModels(false);
    })();

    (function () {
        const section = document.querySelector('[data-product-images]');
        if (!section) return;

        const isImageFile = (file) => file && file.type && file.type.startsWith('image/');

        const setFilesOnInput = (input, files, append) => {
            const transfer = new DataTransfer();
            const incoming = Array.from(files).filter(isImageFile);

            if (append) {
                Array.from(input.files || []).forEach((file) => transfer.items.add(file));
            }

            incoming.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        };

        const renderPrimaryPreview = (input, container) => {
            container.innerHTML = '';
            const file = input.files?.[0];

            if (!file) {
                container.classList.add('hidden');
                return;
            }

            const url = URL.createObjectURL(file);
            container.classList.remove('hidden');
            container.innerHTML =
                '<div class="inline-flex items-center gap-3 rounded-lg border border-border bg-surface p-2">' +
                '<img src="' + url + '" alt="" class="h-16 w-16 rounded object-cover">' +
                '<div class="text-left"><p class="text-sm text-text font-semibold truncate max-w-[14rem]">' + file.name + '</p>' +
                '<button type="button" data-clear-primary class="mt-1 text-xs font-bold uppercase tracking-wide text-red-600 hover:text-red-300">Quitar</button></div></div>';

            container.querySelector('[data-clear-primary]')?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                input.value = '';
                renderPrimaryPreview(input, container);
            });
        };

        const renderSecondaryPreview = (input, container) => {
            container.innerHTML = '';
            const files = Array.from(input.files || []);

            if (files.length === 0) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');

            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                const card = document.createElement('div');
                card.className = 'relative overflow-hidden rounded-lg border border-border bg-surface';
                card.innerHTML =
                    '<img src="' + url + '" alt="" class="h-28 w-full object-cover">' +
                    '<button type="button" data-remove-index="' + index + '" class="absolute inset-x-0 bottom-0 bg-black/70 py-1.5 text-[11px] font-bold uppercase tracking-wide text-red-300 hover:text-red-200">Quitar</button>';
                container.appendChild(card);
            });

            container.querySelectorAll('[data-remove-index]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const removeIndex = Number(button.getAttribute('data-remove-index'));
                    const transfer = new DataTransfer();
                    Array.from(input.files || []).forEach((file, index) => {
                        if (index !== removeIndex) transfer.items.add(file);
                    });
                    input.files = transfer.files;
                    renderSecondaryPreview(input, container);
                });
            });
        };

        const setupDropzone = (type) => {
            const zone = section.querySelector('[data-dropzone="' + type + '"]');
            const input = section.querySelector('[data-file-input="' + type + '"]');
            const preview = section.querySelector('[data-preview="' + type + '"]');
            if (!zone || !input || !preview) return;

            let stashedFiles = [];

            const highlight = (on) => {
                zone.classList.toggle('border-primary', on);
                zone.classList.toggle('bg-primary-soft/20', on);
            };

            const refresh = () => {
                if (type === 'primary') {
                    renderPrimaryPreview(input, preview);
                } else {
                    renderSecondaryPreview(input, preview);
                }
            };

            zone.querySelector('[data-dropzone-trigger]')?.addEventListener('click', () => {
                if (type === 'secondary') {
                    stashedFiles = Array.from(input.files || []);
                }
                input.click();
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                zone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    highlight(true);
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                zone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    highlight(false);
                });
            });

            zone.addEventListener('drop', (event) => {
                const files = event.dataTransfer?.files;
                if (!files?.length) return;

                if (type === 'primary') {
                    setFilesOnInput(input, [files[0]], false);
                } else {
                    setFilesOnInput(input, files, true);
                }

                refresh();
            });

            input.addEventListener('change', () => {
                if (type === 'secondary' && stashedFiles.length > 0) {
                    const next = Array.from(input.files || []);
                    const transfer = new DataTransfer();
                    stashedFiles.concat(next).filter(isImageFile).forEach((file) => transfer.items.add(file));
                    input.files = transfer.files;
                    stashedFiles = [];
                }

                refresh();
            });
        };

        setupDropzone('primary');
        setupDropzone('secondary');
    })();
</script>

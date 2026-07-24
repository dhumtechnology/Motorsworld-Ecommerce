@extends('layouts.admin')

@section('title', 'Productos — Admin')
@section('page-title', 'Productos')
@section('page-subtitle', 'Listado del catálogo con filtros')

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];

        $selectedCategories = $filters['categories'] ?? [];
        $selectedBrands = $filters['brands'] ?? [];
        $selectedModels = $filters['models'] ?? [];
        $boundMin = (float) $priceBounds['min'];
        $boundMax = (float) $priceBounds['max'];
        $step = $boundMax > 1000 ? 10 : ($boundMax > 100 ? 1 : 0.5);
        if ($boundMax <= $boundMin) {
            $boundMax = $boundMin + 1;
        }
    @endphp

    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.products.index') }}" id="admin-products-filters" class="space-y-5">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-3">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Buscar
                    </label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="SKU, nombre, marca o categoría..."
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>

                <div class="lg:col-span-3">
                    <x-multi-select
                        name="categories"
                        label="Categorías"
                        placeholder="Todas las categorías"
                        :options="$categories"
                        :selected="$selectedCategories"
                    />
                </div>

                <div class="lg:col-span-3">
                    <x-multi-select
                        name="brands"
                        label="Marcas"
                        placeholder="Todas las marcas"
                        :options="$brands"
                        :selected="$selectedBrands"
                    />
                </div>

                <div class="lg:col-span-3">
                    <x-multi-select
                        name="models"
                        label="Modelos"
                        placeholder="Selecciona una marca"
                        :options="$models"
                        :selected="$selectedModels"
                        depends-on="brands"
                    />
                </div>

                <div class="lg:col-span-2">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Estado
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ $statusLabels[$status->value]['label'] ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted">
                            Rango de precio
                        </label>
                        <p class="text-xs text-muted font-mono">
                            <span id="price-min-label">{{ number_format($filters['price_min'], 2) }}</span>
                            —
                            <span id="price-max-label">{{ number_format($filters['price_max'], 2) }}</span>
                        </p>
                    </div>

                    <div
                        class="relative h-10 rounded border border-border bg-secondary px-3 flex items-center"
                        data-dual-range
                        data-min="{{ $boundMin }}"
                        data-max="{{ $boundMax }}"
                    >
                        <div class="absolute inset-x-3 top-1/2 h-1.5 -translate-y-1/2">
                            <div class="relative w-full h-full rounded-full bg-border-strong">
                                <div
                                    id="price-range-fill"
                                    class="absolute top-0 h-full rounded-full bg-primary"
                                ></div>
                            </div>
                        </div>

                        <input
                            type="range"
                            id="price_min"
                            name="price_min"
                            min="{{ $boundMin }}"
                            max="{{ $boundMax }}"
                            step="{{ $step }}"
                            value="{{ $filters['price_min'] }}"
                            class="dual-range-thumb absolute inset-x-3 top-0 h-10 w-[calc(100%-1.5rem)] appearance-none bg-transparent pointer-events-none"
                            aria-label="Precio mínimo"
                        >
                        <input
                            type="range"
                            id="price_max"
                            name="price_max"
                            min="{{ $boundMin }}"
                            max="{{ $boundMax }}"
                            step="{{ $step }}"
                            value="{{ $filters['price_max'] }}"
                            class="dual-range-thumb absolute inset-x-3 top-0 h-10 w-[calc(100%-1.5rem)] appearance-none bg-transparent pointer-events-none"
                            aria-label="Precio máximo"
                        >
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-muted">
                    Los filtros se aplican automáticamente
                </p>
                @if ($hasActiveFilters)
                    <a
                        href="{{ route('admin.products.index') }}"
                        class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                id="bulk-delete-btn"
                disabled
                data-open-confirm="bulk-delete-modal"
                class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Eliminar seleccionados
                <span id="bulk-delete-count" class="hidden">(0)</span>
            </button>
        </div>
        <a
            href="{{ route('admin.products.create') }}"
            class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Agregar producto
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden" data-products-table>
        <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-4">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $products->total() }}</span>
                {{ $products->total() === 1 ? 'producto' : 'productos' }}
                @if ($hasActiveFilters)
                    <span class="text-muted">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold w-12">
                            <input
                                type="checkbox"
                                id="select-all-products"
                                class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                                title="Seleccionar todos"
                                @disabled($products->isEmpty())
                            >
                        </th>
                        <th scope="col" class="px-5 py-3 font-bold w-16">Img</th>
                        <th scope="col" class="px-5 py-3 font-bold">SKU</th>
                        <th scope="col" class="px-5 py-3 font-bold">Nombre</th>
                        <th scope="col" class="px-5 py-3 font-bold">Categoría</th>
                        <th scope="col" class="px-5 py-3 font-bold">Marca / Modelo</th>
                        <th scope="col" class="px-5 py-3 font-bold">Precio</th>
                        <th scope="col" class="px-5 py-3 font-bold">Stock</th>
                        <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($products as $product)
                        @php
                            $statusKey = $product->status instanceof \App\Enums\Products\ProductStatus
                                ? $product->status->value
                                : (string) $product->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                            $imageUrl = $product->catalogImageUrl();
                        @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input
                                    type="checkbox"
                                    name="product_ids[]"
                                    value="{{ $product->id }}"
                                    data-product-checkbox
                                    data-product-name="{{ $product->name }}"
                                    class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                                >
                            </td>
                            <td class="px-5 py-3">
                                @if ($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt=""
                                        class="h-10 w-10 rounded object-cover border border-border bg-secondary"
                                    >
                                @else
                                    <div class="h-10 w-10 rounded border border-border bg-secondary flex items-center justify-center text-muted text-xs">
                                        —
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-text-soft">{{ $product->sku }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $product->name }}</p>
                                <a
                                    href="{{ route('shop.product.show', $product) }}"
                                    target="_blank"
                                    class="text-xs text-primary hover:text-primary"
                                >
                                    Ver en tienda ↗
                                </a>
                            </td>
                            <td class="px-5 py-3 text-text-soft">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-text-soft">
                                {{ $product->vehicleModel?->brand?->name ?? '—' }}
                                @if ($product->vehicleModel?->name)
                                    <span class="block text-xs text-muted">{{ $product->vehicleModel->name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text font-semibold whitespace-nowrap">
                                {{ number_format((float) $product->price_amount, 2) }}
                                <span class="text-muted text-xs">{{ $product->currency }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @php $stock = $product->inventory?->available_stock ?? 0; @endphp
                                <span class="{{ $stock > 0 ? 'text-emerald-700' : 'text-red-600' }} font-semibold">
                                    {{ $stock }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.products.show', $product) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        title="Ver detalle"
                                        aria-label="Ver detalle de {{ $product->name }}"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </a>
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors"
                                        title="Editar"
                                        aria-label="Editar {{ $product->name }}"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                        </svg>
                                    </a>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors"
                                        title="Eliminar"
                                        aria-label="Eliminar {{ $product->name }}"
                                        data-open-confirm="single-delete-modal"
                                        data-delete-url="{{ route('admin.products.destroy', $product) }}"
                                        data-delete-message="¿Eliminar el producto «{{ $product->name }}»? Esta acción no se puede deshacer."
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-muted">
                                No se encontraron productos con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $products->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    <x-confirm-modal
        id="single-delete-modal"
        title="Eliminar producto"
        message="¿Seguro que deseas eliminar este producto?"
        confirm-label="Eliminar"
        method="DELETE"
        :action="route('admin.products.index')"
    />

    <x-confirm-modal
        id="bulk-delete-modal"
        title="Eliminar productos"
        message="¿Eliminar los productos seleccionados? Esta acción no se puede deshacer."
        confirm-label="Eliminar seleccionados"
        method="DELETE"
        :action="route('admin.products.bulk-destroy')"
    />

    <style>
        .dual-range-thumb::-webkit-slider-runnable-track {
            background: transparent;
            height: 0.375rem;
        }
        .dual-range-thumb::-moz-range-track {
            background: transparent;
            height: 0.375rem;
        }
        .dual-range-thumb::-webkit-slider-thumb {
            appearance: none;
            pointer-events: auto;
            width: 1rem;
            height: 1rem;
            border-radius: 9999px;
            background: #ea580c;
            border: 2px solid #fff;
            cursor: pointer;
            margin-top: -0.3rem;
            position: relative;
            z-index: 20;
        }
        .dual-range-thumb::-moz-range-thumb {
            pointer-events: auto;
            width: 1rem;
            height: 1rem;
            border-radius: 9999px;
            background: #ea580c;
            border: 2px solid #fff;
            cursor: pointer;
            position: relative;
            z-index: 20;
        }
    </style>

    <script>
        (function () {
            const form = document.getElementById('admin-products-filters');
            if (!form) return;

            let submitTimer = null;
            let isSubmitting = false;

            const setHint = (text) => {
                const hint = document.getElementById('filters-live-hint');
                if (hint) hint.textContent = text;
            };

            const submitFilters = () => {
                if (isSubmitting) return;
                isSubmitting = true;
                setHint('Actualizando resultados…');
                form.requestSubmit ? form.requestSubmit() : form.submit();
            };

            const scheduleSubmit = (delay = 250) => {
                clearTimeout(submitTimer);
                setHint('Aplicando filtros…');
                submitTimer = setTimeout(submitFilters, delay);
            };

            const multiSelects = Array.from(document.querySelectorAll('[data-multi-select]'));

            const getMultiSelectByKey = (key) =>
                multiSelects.find((root) => root.dataset.multiSelectKey === key) || null;

            const updateSummary = (root) => {
                const summary = root.querySelector('[data-multi-select-summary]');
                const placeholder = root.dataset.placeholder || 'Seleccionar...';
                if (!summary) return;

                const checked = Array.from(root.querySelectorAll('[data-multi-select-option]:checked:not(:disabled)'));
                const labels = checked.map((input) => input.dataset.label).filter(Boolean);

                if (labels.length === 0) {
                    summary.textContent = placeholder;
                    summary.classList.add('text-muted');
                    summary.classList.remove('text-text');
                    return;
                }

                summary.classList.remove('text-muted');
                summary.classList.add('text-text');

                if (labels.length === 1) {
                    summary.textContent = labels[0];
                } else if (labels.length <= 3) {
                    summary.textContent = labels.join(', ');
                } else {
                    summary.textContent = labels.length + ' seleccionadas';
                }
            };

            const syncDependentMultiSelect = (dependentRoot) => {
                const dependsOnKey = dependentRoot.dataset.dependsOn;
                if (!dependsOnKey) return false;

                const parent = getMultiSelectByKey(dependsOnKey);
                if (!parent) return false;

                const selectedGroups = Array.from(
                    parent.querySelectorAll('[data-multi-select-option]:checked'),
                ).map((input) => String(input.value));

                const requiresParent = selectedGroups.length > 0;
                let changedSelection = false;
                let visibleCount = 0;

                dependentRoot.querySelectorAll('[data-multi-select-item]').forEach((item) => {
                    const groupId = item.dataset.groupId;
                    const matches = requiresParent && selectedGroups.includes(String(groupId));
                    const option = item.querySelector('[data-multi-select-option]');

                    item.classList.toggle('hidden', !matches);

                    if (!matches && option?.checked) {
                        option.checked = false;
                        changedSelection = true;
                        item.setAttribute('aria-selected', 'false');
                    }

                    if (option) {
                        option.disabled = !matches;
                    }

                    if (matches) {
                        visibleCount += 1;
                    }
                });

                const emptyHint = dependentRoot.querySelector('[data-multi-select-filtered-empty]');
                if (emptyHint) {
                    emptyHint.textContent = requiresParent
                        ? 'No hay modelos para las marcas seleccionadas.'
                        : 'Selecciona una marca para ver modelos.';
                    emptyHint.classList.toggle('hidden', visibleCount > 0);
                }

                const trigger = dependentRoot.querySelector('[data-multi-select-trigger]');
                if (trigger) {
                    trigger.disabled = !requiresParent;
                    trigger.classList.toggle('opacity-60', !requiresParent);
                    trigger.classList.toggle('cursor-not-allowed', !requiresParent);
                }

                if (!requiresParent) {
                    const panel = dependentRoot.querySelector('[data-multi-select-panel]');
                    if (panel) panel.classList.add('hidden');
                }

                updateSummary(dependentRoot);

                return changedSelection;
            };

            multiSelects.forEach((root) => {
                const trigger = root.querySelector('[data-multi-select-trigger]');
                const panel = root.querySelector('[data-multi-select-panel]');

                if (!trigger || !panel) return;

                const close = () => {
                    panel.classList.add('hidden');
                    trigger.setAttribute('aria-expanded', 'false');
                };

                const open = () => {
                    panel.classList.remove('hidden');
                    trigger.setAttribute('aria-expanded', 'true');
                };

                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (trigger.disabled) return;

                    const isOpen = !panel.classList.contains('hidden');
                    document.querySelectorAll('[data-multi-select-panel]').forEach((other) => {
                        if (other !== panel) other.classList.add('hidden');
                    });
                    document.querySelectorAll('[data-multi-select-trigger]').forEach((other) => {
                        if (other !== trigger) other.setAttribute('aria-expanded', 'false');
                    });

                    if (isOpen) {
                        close();
                    } else {
                        open();
                    }
                });

                root.querySelectorAll('[data-multi-select-option]').forEach((input) => {
                    input.addEventListener('change', () => {
                        const option = input.closest('[role="option"]');
                        if (option) {
                            option.setAttribute('aria-selected', input.checked ? 'true' : 'false');
                        }

                        updateSummary(root);

                        multiSelects
                            .filter((other) => other.dataset.dependsOn === root.dataset.multiSelectKey)
                            .forEach((dependent) => syncDependentMultiSelect(dependent));

                        scheduleSubmit(200);
                    });

                    input.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                });

                panel.addEventListener('click', (event) => {
                    event.stopPropagation();
                });

                document.addEventListener('click', () => close());
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') close();
                });

                updateSummary(root);
            });

            multiSelects
                .filter((root) => root.dataset.dependsOn)
                .forEach((root) => syncDependentMultiSelect(root));

            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('input', () => scheduleSubmit(450));
                searchInput.addEventListener('search', () => scheduleSubmit(0));
            }

            const statusSelect = document.getElementById('status');
            if (statusSelect) {
                statusSelect.addEventListener('change', () => scheduleSubmit(150));
            }

            const minInput = document.getElementById('price_min');
            const maxInput = document.getElementById('price_max');
            const minLabel = document.getElementById('price-min-label');
            const maxLabel = document.getElementById('price-max-label');
            const fill = document.getElementById('price-range-fill');
            const wrapper = document.querySelector('[data-dual-range]');

            if (minInput && maxInput && wrapper && fill && minLabel && maxLabel) {
                const boundMin = Number(wrapper.dataset.min);
                const boundMax = Number(wrapper.dataset.max);
                const span = Math.max(boundMax - boundMin, 0.0001);

                const format = (value) => Number(value).toLocaleString('es-PE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                const sync = (source) => {
                    let min = Number(minInput.value);
                    let max = Number(maxInput.value);

                    if (min > max) {
                        if (source === minInput) {
                            min = max;
                            minInput.value = String(min);
                        } else {
                            max = min;
                            maxInput.value = String(max);
                        }
                    }

                    minLabel.textContent = format(min);
                    maxLabel.textContent = format(max);

                    const left = ((min - boundMin) / span) * 100;
                    const right = ((max - boundMin) / span) * 100;

                    fill.style.left = left + '%';
                    fill.style.width = Math.max(right - left, 0) + '%';

                    minInput.style.zIndex = source === minInput ? '30' : '20';
                    maxInput.style.zIndex = source === maxInput ? '30' : '20';
                };

                const onPriceInput = (source) => {
                    sync(source);
                    scheduleSubmit(500);
                };

                minInput.addEventListener('input', () => onPriceInput(minInput));
                maxInput.addEventListener('input', () => onPriceInput(maxInput));
                minInput.addEventListener('change', () => scheduleSubmit(100));
                maxInput.addEventListener('change', () => scheduleSubmit(100));
                sync(maxInput);
            }
        })();

        (function () {
            const selectAll = document.getElementById('select-all-products');
            const checkboxes = () => Array.from(document.querySelectorAll('[data-product-checkbox]'));
            const bulkBtn = document.getElementById('bulk-delete-btn');
            const bulkCount = document.getElementById('bulk-delete-count');

            const selectedCheckboxes = () => checkboxes().filter((cb) => cb.checked);

            const syncSelectionUi = () => {
                const all = checkboxes();
                const selected = selectedCheckboxes();
                const count = selected.length;

                if (selectAll) {
                    selectAll.checked = all.length > 0 && count === all.length;
                    selectAll.indeterminate = count > 0 && count < all.length;
                }

                if (bulkBtn) {
                    bulkBtn.disabled = count === 0;
                }

                if (bulkCount) {
                    if (count > 0) {
                        bulkCount.textContent = '(' + count + ')';
                        bulkCount.classList.remove('hidden');
                    } else {
                        bulkCount.classList.add('hidden');
                    }
                }
            };

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes().forEach((cb) => {
                        cb.checked = selectAll.checked;
                    });
                    syncSelectionUi();
                });
            }

            checkboxes().forEach((cb) => {
                cb.addEventListener('change', syncSelectionUi);
            });

            syncSelectionUi();

            const openModal = (modal) => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = (modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');

                const form = modal.querySelector('[data-confirm-form]');
                const extra = form?.querySelector('[data-confirm-extra-fields]');
                if (extra) {
                    extra.innerHTML = '';
                }
            };

            document.querySelectorAll('[data-confirm-modal]').forEach((modal) => {
                modal.querySelectorAll('[data-confirm-cancel], [data-confirm-overlay]').forEach((el) => {
                    el.addEventListener('click', () => closeModal(modal));
                });

                modal.querySelector('[data-confirm-submit]')?.addEventListener('click', () => {
                    modal.querySelector('[data-confirm-form]')?.submit();
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('[data-confirm-modal]:not(.hidden)').forEach((modal) => {
                    closeModal(modal);
                });
            });

            document.querySelectorAll('[data-open-confirm]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    if (trigger.disabled) return;

                    const modalId = trigger.getAttribute('data-open-confirm');
                    const modal = document.getElementById(modalId);
                    if (!modal) return;

                    const form = modal.querySelector('[data-confirm-form]');
                    const messageEl = modal.querySelector('[data-confirm-message]');
                    const extra = form?.querySelector('[data-confirm-extra-fields]');

                    if (modalId === 'single-delete-modal') {
                        const url = trigger.getAttribute('data-delete-url');
                        const message = trigger.getAttribute('data-delete-message');
                        if (form && url) {
                            form.action = url;
                        }
                        if (messageEl && message) {
                            messageEl.textContent = message;
                        }
                    }

                    if (modalId === 'bulk-delete-modal') {
                        const selected = selectedCheckboxes();
                        if (selected.length === 0) return;

                        if (messageEl) {
                            messageEl.textContent = selected.length === 1
                                ? '¿Eliminar 1 producto seleccionado? Esta acción no se puede deshacer.'
                                : '¿Eliminar ' + selected.length + ' productos seleccionados? Esta acción no se puede deshacer.';
                        }

                        if (extra) {
                            extra.innerHTML = '';
                            selected.forEach((cb) => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'ids[]';
                                input.value = cb.value;
                                extra.appendChild(input);
                            });
                        }
                    }

                    openModal(modal);
                });
            });
        })();
    </script>
@endsection

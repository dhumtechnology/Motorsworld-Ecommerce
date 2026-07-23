@extends('layouts.admin')

@section('title', 'Inventario — Admin')
@section('page-title', 'Inventario')
@section('page-subtitle', 'Entradas y salidas de stock')

@section('content')
    @php
        $selectedCategories = $filters['categories'] ?? [];
        $selectedBrands = $filters['brands'] ?? [];
        $selectedModels = $filters['models'] ?? [];
    @endphp

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.inventory.index') }}" id="admin-inventory-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Producto o SKU..."
                           class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                </div>
                <div class="lg:col-span-2">
                    <label for="type" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Tipo</label>
                    <select id="type" name="type" class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">Todos</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="date_from" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Desde</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                </div>
                <div class="lg:col-span-3">
                    <label for="date_to" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Hasta</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                </div>

                <div class="lg:col-span-4">
                    <x-multi-select
                        name="categories"
                        label="Categorías"
                        placeholder="Todas las categorías"
                        :options="$categories"
                        :selected="$selectedCategories"
                    />
                </div>
                <div class="lg:col-span-4">
                    <x-multi-select
                        name="brands"
                        label="Marcas"
                        placeholder="Todas las marcas"
                        :options="$brands"
                        :selected="$selectedBrands"
                    />
                </div>
                <div class="lg:col-span-4">
                    <x-multi-select
                        name="models"
                        label="Modelos"
                        placeholder="Todos los modelos"
                        :options="$models"
                        :selected="$selectedModels"
                        depends-on="brands"
                    />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-neutral-500">Los filtros se aplican automáticamente</p>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.inventory.index') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="export-excel-btn" disabled
                    class="rounded border border-emerald-800 bg-emerald-950/40 px-4 py-2 text-sm font-bold uppercase tracking-wide text-emerald-400 transition-colors enabled:hover:bg-emerald-900/50 disabled:cursor-not-allowed disabled:opacity-40">
                Exportar Excel <span id="export-count" class="hidden">(0)</span>
            </button>
            <button type="button" id="export-pdf-btn" disabled
                    class="rounded border border-red-800 bg-red-950/40 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-400 transition-colors enabled:hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-40">
                Exportar PDF
            </button>
            <a href="{{ route('admin.inventory.import') }}"
               class="rounded border border-sky-800 bg-sky-950/40 px-4 py-2 text-sm font-bold uppercase tracking-wide text-sky-400 hover:bg-sky-900/50 transition-colors">
                Importar
            </a>
        </div>
        <a href="{{ route('admin.inventory.create') }}"
           class="inline-flex items-center gap-2 rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
            Nuevo movimiento
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{{ $errors->first() }}</div>
    @endif

    <form id="inventory-export-form" method="POST" action="{{ route('admin.inventory.export') }}" class="hidden">
        @csrf
        <input type="hidden" name="format" id="export-format" value="xlsx">
        <div id="export-ids"></div>
    </form>

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-800">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $movements->total() }}</span>
                {{ $movements->total() === 1 ? 'movimiento' : 'movimientos' }}
                @if ($hasActiveFilters)<span class="text-neutral-500">(filtrados)</span>@endif
                <span class="text-neutral-600">· Selecciona filas para exportar</span>
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-neutral-600 bg-[#1e1e1e] text-orange-600 focus:ring-orange-500" @disabled($movements->isEmpty())>
                        </th>
                        <th class="px-5 py-3 font-bold">Fecha</th>
                        <th class="px-5 py-3 font-bold">Tipo</th>
                        <th class="px-5 py-3 font-bold">Producto</th>
                        <th class="px-5 py-3 font-bold">Cat. / Marca</th>
                        <th class="px-5 py-3 font-bold">Cant.</th>
                        <th class="px-5 py-3 font-bold">Motivo</th>
                        <th class="px-5 py-3 font-bold">Orden</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($movements as $movement)
                        @php
                            $isEntry = $movement->type === \App\Enums\Inventory\InventoryMovementType::Entry;
                            $product = $movement->product;
                        @endphp
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $movement->id }}" data-row-checkbox class="h-4 w-4 rounded border-neutral-600 bg-[#1e1e1e] text-orange-600 focus:ring-orange-500">
                            </td>
                            <td class="px-5 py-3 text-neutral-400 whitespace-nowrap">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                @if ($isEntry)
                                    <span class="inline-flex items-center rounded border border-green-800 bg-green-950 px-2 py-0.5 text-xs font-bold uppercase text-green-400">Entrada</span>
                                @else
                                    <span class="inline-flex items-center rounded border border-red-800 bg-red-950 px-2 py-0.5 text-xs font-bold uppercase text-red-400">Salida</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $product?->name ?? '—' }}</p>
                                <p class="text-xs font-mono text-neutral-500">{{ $product?->sku }}</p>
                            </td>
                            <td class="px-5 py-3 text-neutral-300 text-xs">
                                <p>{{ $product?->category?->name ?? '—' }}</p>
                                <p class="text-neutral-500">{{ $product?->vehicleModel?->brand?->name ?? '—' }} · {{ $product?->vehicleModel?->name ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 font-bold {{ $isEntry ? 'text-green-400' : 'text-red-400' }}">
                                {{ $isEntry ? '+' : '-' }}{{ $movement->quantity }}
                            </td>
                            <td class="px-5 py-3 text-neutral-300">
                                {{ $movement->reason?->label() }}
                                @if ($movement->isSaleExit())
                                    <span class="ml-1 text-[10px] font-bold uppercase text-sky-400">Auto</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($movement->order_id)
                                    <a href="{{ route('admin.orders.show', $movement->order_id) }}" class="font-mono text-sky-400 hover:text-sky-300">#{{ $movement->order_id }}</a>
                                @else
                                    <span class="text-neutral-600">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.inventory.show', $movement) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-800 bg-sky-950/50 text-sky-400 hover:bg-sky-900/60 transition-colors" title="Ver">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                    </a>
                                    @if ($movement->isReversible())
                                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-800 bg-red-950/50 text-red-400 hover:bg-red-900/60 transition-colors" title="Revertir"
                                                data-open-confirm="single-delete-modal"
                                                data-delete-url="{{ route('admin.inventory.destroy', $movement) }}"
                                                data-delete-message="¿Revertir el movimiento #{{ $movement->id }}?">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-12 text-center text-neutral-500">No hay movimientos de inventario.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">{{ $movements->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Revertir movimiento" message="¿Seguro?" confirm-label="Revertir" method="DELETE" :action="route('admin.inventory.index')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-inventory-filters',
        'entityLabelSingular' => 'movimiento',
        'entityLabelPlural' => 'movimientos',
    ])

    <script>
        (function () {
            const checkboxes = () => Array.from(document.querySelectorAll('[data-row-checkbox]'));
            const selected = () => checkboxes().filter((cb) => cb.checked);
            const excelBtn = document.getElementById('export-excel-btn');
            const pdfBtn = document.getElementById('export-pdf-btn');
            const countEl = document.getElementById('export-count');
            const form = document.getElementById('inventory-export-form');
            const formatInput = document.getElementById('export-format');
            const idsBox = document.getElementById('export-ids');
            const filterForm = document.getElementById('admin-inventory-filters');

            const syncExportUi = () => {
                const count = selected().length;
                if (excelBtn) excelBtn.disabled = count === 0;
                if (pdfBtn) pdfBtn.disabled = count === 0;
                if (countEl) {
                    if (count > 0) {
                        countEl.textContent = '(' + count + ')';
                        countEl.classList.remove('hidden');
                    } else {
                        countEl.classList.add('hidden');
                    }
                }
            };

            const submitExport = (format) => {
                const rows = selected();
                if (!form || rows.length === 0) return;
                formatInput.value = format;
                idsBox.innerHTML = '';
                rows.forEach((cb) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    idsBox.appendChild(input);
                });
                form.submit();
            };

            document.getElementById('select-all-items')?.addEventListener('change', syncExportUi);
            checkboxes().forEach((cb) => cb.addEventListener('change', syncExportUi));
            excelBtn?.addEventListener('click', () => submitExport('xlsx'));
            pdfBtn?.addEventListener('click', () => submitExport('pdf'));
            syncExportUi();

            // Dates also trigger live filter submit
            ['date_from', 'date_to'].forEach((id) => {
                document.getElementById(id)?.addEventListener('change', () => {
                    if (filterForm?.requestSubmit) filterForm.requestSubmit();
                    else filterForm?.submit();
                });
            });

            // Brand → model cascade
            const multiSelects = Array.from(document.querySelectorAll('[data-multi-select]'));
            const getByKey = (key) => multiSelects.find((root) => root.dataset.multiSelectKey === key);
            const modelsRoot = multiSelects.find((root) => root.dataset.dependsOn === 'brands');
            const brandsRoot = getByKey('brands');

            const syncModelsByBrand = () => {
                if (!modelsRoot) return;
                const brandIds = brandsRoot
                    ? Array.from(brandsRoot.querySelectorAll('[data-multi-select-option]:checked')).map((el) => String(el.value))
                    : [];
                const requiresParent = brandIds.length > 0;
                let visibleCount = 0;

                modelsRoot.querySelectorAll('[data-multi-select-item]').forEach((item) => {
                    const groupId = item.dataset.groupId;
                    const matches = requiresParent && brandIds.includes(String(groupId));
                    const option = item.querySelector('[data-multi-select-option]');
                    item.classList.toggle('hidden', !matches);
                    if (!matches && option?.checked) option.checked = false;
                    if (option) option.disabled = !matches;
                    if (matches) visibleCount += 1;
                });

                const emptyHint = modelsRoot.querySelector('[data-multi-select-filtered-empty]');
                if (emptyHint) {
                    emptyHint.textContent = requiresParent
                        ? 'No hay modelos para las marcas seleccionadas.'
                        : 'Selecciona una marca para ver modelos.';
                    emptyHint.classList.toggle('hidden', visibleCount > 0);
                }

                const trigger = modelsRoot.querySelector('[data-multi-select-trigger]');
                if (trigger) {
                    trigger.disabled = !requiresParent;
                    trigger.classList.toggle('opacity-60', !requiresParent);
                }
            };

            brandsRoot?.querySelectorAll('[data-multi-select-option]').forEach((input) => {
                input.addEventListener('change', syncModelsByBrand);
            });
            syncModelsByBrand();
        })();
    </script>
@endsection

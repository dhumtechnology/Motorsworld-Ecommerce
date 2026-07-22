@extends('layouts.admin')

@section('title', 'Categorías — Admin')
@section('page-title', 'Categorías')
@section('page-subtitle', 'Organización del catálogo')

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.categories.index') }}" id="admin-categories-filters" class="space-y-4">
            <div class="max-w-md">
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                    Buscar
                </label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Nombre o descripción..."
                    class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                >
            </div>

            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-neutral-500">
                    La búsqueda se aplica automáticamente
                </p>
                @if ($hasActiveFilters)
                    <a
                        href="{{ route('admin.categories.index') }}"
                        class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors"
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
                class="rounded border border-red-800 bg-red-950/40 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-400 transition-colors enabled:hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Eliminar seleccionados
                <span id="bulk-delete-count" class="hidden">(0)</span>
            </button>
        </div>
        <a
            href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center gap-2 rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Agregar categoría
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden" data-categories-table>
        <div class="px-5 py-4 border-b border-neutral-800">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $categories->total() }}</span>
                {{ $categories->total() === 1 ? 'categoría' : 'categorías' }}
                @if ($hasActiveFilters)
                    <span class="text-neutral-500">(filtradas)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold w-12">
                            <input
                                type="checkbox"
                                id="select-all-categories"
                                class="h-4 w-4 rounded border-neutral-600 bg-[#1e1e1e] text-orange-600 focus:ring-orange-500"
                                title="Seleccionar todos"
                                @disabled($categories->isEmpty())
                            >
                        </th>
                        <th scope="col" class="px-5 py-3 font-bold">Nombre</th>
                        <th scope="col" class="px-5 py-3 font-bold">Descripción</th>
                        <th scope="col" class="px-5 py-3 font-bold">Productos</th>
                        <th scope="col" class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3">
                                <input
                                    type="checkbox"
                                    name="category_ids[]"
                                    value="{{ $category->id }}"
                                    data-category-checkbox
                                    data-category-name="{{ $category->name }}"
                                    class="h-4 w-4 rounded border-neutral-600 bg-[#1e1e1e] text-orange-600 focus:ring-orange-500"
                                >
                            </td>
                            <td class="px-5 py-3 font-semibold text-white">{{ $category->name }}</td>
                            <td class="px-5 py-3 text-neutral-400 max-w-md">
                                <span class="line-clamp-2">{{ $category->description ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-neutral-700 bg-[#252525] px-2 py-0.5 text-xs font-bold text-neutral-300">
                                    {{ $category->products_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.categories.edit', $category) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-800 bg-sky-950/50 text-sky-400 hover:bg-sky-900/60 transition-colors"
                                        title="Editar"
                                        aria-label="Editar {{ $category->name }}"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                        </svg>
                                    </a>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-800 bg-red-950/50 text-red-400 hover:bg-red-900/60 transition-colors"
                                        title="Eliminar"
                                        aria-label="Eliminar {{ $category->name }}"
                                        data-open-confirm="single-delete-modal"
                                        data-delete-url="{{ route('admin.categories.destroy', $category) }}"
                                        data-delete-message="¿Eliminar la categoría «{{ $category->name }}»? Esta acción no se puede deshacer."
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
                            <td colspan="5" class="px-5 py-12 text-center text-neutral-500">
                                No se encontraron categorías.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">
                {{ $categories->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>

    <x-confirm-modal
        id="single-delete-modal"
        title="Eliminar categoría"
        message="¿Seguro que deseas eliminar esta categoría?"
        confirm-label="Eliminar"
        method="DELETE"
        :action="route('admin.categories.index')"
    />

    <x-confirm-modal
        id="bulk-delete-modal"
        title="Eliminar categorías"
        message="¿Eliminar las categorías seleccionadas? Esta acción no se puede deshacer."
        confirm-label="Eliminar seleccionados"
        method="DELETE"
        :action="route('admin.categories.bulk-destroy')"
    />

    <script>
        (function () {
            const form = document.getElementById('admin-categories-filters');
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

            const scheduleSubmit = (delay = 450) => {
                clearTimeout(submitTimer);
                setHint('Aplicando búsqueda…');
                submitTimer = setTimeout(submitFilters, delay);
            };

            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('input', () => scheduleSubmit(450));
                searchInput.addEventListener('search', () => scheduleSubmit(0));
            }
        })();

        (function () {
            const selectAll = document.getElementById('select-all-categories');
            const checkboxes = () => Array.from(document.querySelectorAll('[data-category-checkbox]'));
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
                                ? '¿Eliminar 1 categoría seleccionada? Esta acción no se puede deshacer.'
                                : '¿Eliminar ' + selected.length + ' categorías seleccionadas? Esta acción no se puede deshacer.';
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

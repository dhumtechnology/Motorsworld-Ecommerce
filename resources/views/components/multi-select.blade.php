@props([
    'name',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Seleccionar...',
    'label' => null,
    'id' => null,
    'dependsOn' => null,
    'source' => null,
])

@php
    $fieldId = $id ?? $name;
    $selectedArray = collect(is_array($selected) ? $selected : ($selected ? [$selected] : []))
        ->map(fn ($value) => (int) $value)
        ->all();

    $selectedLabels = collect($options)
        ->filter(fn ($option) => in_array((int) $option->id, $selectedArray, true))
        ->pluck('name')
        ->values();

    $summary = match (true) {
        $selectedLabels->isEmpty() => $placeholder,
        $selectedLabels->count() === 1 => $selectedLabels->first(),
        $selectedLabels->count() <= 3 => $selectedLabels->implode(', '),
        default => $selectedLabels->count().' seleccionadas',
    };

    $isRemote = filled($source);
@endphp

<div
    class="relative z-20"
    data-multi-select
    data-multi-select-key="{{ $name }}"
    data-placeholder="{{ $placeholder }}"
    @if ($dependsOn)
        data-depends-on="{{ $dependsOn }}"
    @endif
    @if ($isRemote)
        data-multi-select-source="{{ $source }}"
    @endif
>
    @if ($label)
        <label for="{{ $fieldId }}-trigger" class="admin-label">
            {{ $label }}
        </label>
    @endif

    <button
        type="button"
        id="{{ $fieldId }}-trigger"
        data-multi-select-trigger
        class="flex w-full items-center justify-between gap-2 rounded-lg border border-border bg-surface px-4 py-2.5 text-left text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary font-secondary"
        aria-haspopup="listbox"
        aria-expanded="false"
    >
        <span data-multi-select-summary class="truncate {{ $selectedLabels->isEmpty() ? 'text-muted' : 'text-text' }}">
            {{ $summary }}
        </span>
        <svg class="h-4 w-4 shrink-0 text-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        data-multi-select-panel
        class="absolute left-0 right-0 z-50 mt-1 hidden overflow-hidden rounded-lg border border-border bg-surface shadow-lg"
        role="listbox"
        aria-multiselectable="true"
    >
        @if ($isRemote)
            <div class="border-b border-border p-2">
                <input
                    type="search"
                    data-multi-select-search
                    placeholder="Buscar distrito…"
                    class="w-full rounded border border-border bg-secondary px-3 py-2 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
            </div>
        @endif
        <ul class="max-h-56 overflow-y-auto py-1" data-multi-select-options>
            @forelse ($options as $option)
                @php
                    $optionId = (int) $option->id;
                    $isChecked = in_array($optionId, $selectedArray, true);
                    $optionDomId = $fieldId.'-option-'.$optionId;
                    $groupId = $option->brand_id ?? null;
                @endphp
                <li
                    role="option"
                    aria-selected="{{ $isChecked ? 'true' : 'false' }}"
                    data-multi-select-item
                    @if ($groupId !== null)
                        data-group-id="{{ $groupId }}"
                    @endif
                >
                    <label
                        for="{{ $optionDomId }}"
                        class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm text-text-soft hover:bg-secondary hover:text-text font-secondary"
                    >
                        <input
                            id="{{ $optionDomId }}"
                            type="checkbox"
                            name="{{ $name }}[]"
                            value="{{ $optionId }}"
                            data-multi-select-option
                            data-label="{{ $option->name }}"
                            @if ($groupId !== null)
                                data-group-id="{{ $groupId }}"
                            @endif
                            class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                            @checked($isChecked)
                        >
                        <span>{{ $option->name }}</span>
                    </label>
                </li>
            @empty
                <li class="px-3 py-3 text-xs text-muted" data-multi-select-empty>
                    {{ $isRemote ? 'Escribe para buscar o desplázate para cargar más.' : 'No hay opciones disponibles.' }}
                </li>
            @endforelse
            <li class="hidden px-3 py-3 text-xs text-muted" data-multi-select-filtered-empty>
                No hay opciones para la selección actual.
            </li>
            @if ($isRemote)
                <li class="hidden px-3 py-2 text-xs text-muted" data-multi-select-loading>Cargando…</li>
            @endif
        </ul>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const bindRemoteSelect = (root) => {
                    if (root.dataset.remoteReady) return;
                    root.dataset.remoteReady = '1';

                    const source = root.dataset.multiSelectSource;
                    const fieldName = root.dataset.multiSelectKey;
                    const trigger = root.querySelector('[data-multi-select-trigger]');
                    const panel = root.querySelector('[data-multi-select-panel]');
                    const summary = root.querySelector('[data-multi-select-summary]');
                    const list = root.querySelector('[data-multi-select-options]');
                    const search = root.querySelector('[data-multi-select-search]');
                    const empty = root.querySelector('[data-multi-select-empty]');
                    const loadingEl = root.querySelector('[data-multi-select-loading]');
                    const placeholder = root.dataset.placeholder || 'Seleccionar...';
                    if (!source || !trigger || !panel || !summary || !list) return;

                    let page = 1;
                    let nextPage = 1;
                    let loading = false;
                    let searchTerm = '';
                    let searchTimer = null;

                    const updateSummary = () => {
                        const checked = Array.from(root.querySelectorAll('[data-multi-select-option]:checked'));
                        const labels = checked.map((input) => input.dataset.label).filter(Boolean);
                        if (labels.length === 0) {
                            summary.textContent = placeholder;
                            summary.classList.add('text-muted');
                            summary.classList.remove('text-text');
                            return;
                        }
                        summary.classList.remove('text-muted');
                        summary.classList.add('text-text');
                        summary.textContent = labels.length === 1
                            ? labels[0]
                            : (labels.length <= 3 ? labels.join(', ') : labels.length + ' seleccionados');
                    };

                    const existingIds = () => new Set(
                        Array.from(root.querySelectorAll('[data-multi-select-option]')).map((input) => String(input.value))
                    );

                    const appendOption = (item) => {
                        const id = String(item.id);
                        const optionId = fieldName + '-option-' + id;
                        const li = document.createElement('li');
                        li.setAttribute('role', 'option');
                        li.dataset.multiSelectItem = '';
                        li.dataset.remoteItem = '1';

                        const label = document.createElement('label');
                        label.setAttribute('for', optionId);
                        label.className = 'flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm text-text-soft hover:bg-secondary hover:text-text font-secondary';

                        const input = document.createElement('input');
                        input.id = optionId;
                        input.type = 'checkbox';
                        input.name = fieldName + '[]';
                        input.value = id;
                        input.dataset.multiSelectOption = '';
                        input.dataset.label = item.name || '';
                        input.className = 'h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary';
                        input.addEventListener('change', updateSummary);
                        input.addEventListener('click', (event) => event.stopPropagation());

                        const text = document.createElement('span');
                        text.textContent = item.name || '';

                        label.append(input, text);
                        li.appendChild(label);
                        if (loadingEl) {
                            list.insertBefore(li, loadingEl);
                        } else {
                            list.appendChild(li);
                        }
                    };

                    const load = async (reset) => {
                        if (loading) return;
                        if (!reset && nextPage === null) return;
                        loading = true;
                        if (loadingEl) loadingEl.classList.remove('hidden');

                        if (reset) {
                            page = 1;
                            nextPage = 1;
                            list.querySelectorAll('[data-remote-item]').forEach((item) => item.remove());
                        }

                        try {
                            const url = new URL(source, window.location.origin);
                            url.searchParams.set('page', String(page));
                            if (searchTerm) url.searchParams.set('search', searchTerm);
                            const response = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            const payload = await response.json().catch(() => ({ data: [] }));
                            const seen = existingIds();
                            (payload.data || []).forEach((item) => {
                                if (seen.has(String(item.id))) return;
                                appendOption(item);
                                seen.add(String(item.id));
                            });
                            nextPage = payload.next_page ?? null;
                            page = nextPage ?? page;
                        } catch (err) {
                            console.warn('No se pudieron cargar distritos', err);
                            nextPage = null;
                        } finally {
                            loading = false;
                            if (loadingEl) loadingEl.classList.add('hidden');
                            const hasItems = root.querySelectorAll('[data-multi-select-item]').length > 0;
                            if (empty) {
                                empty.textContent = searchTerm && !hasItems
                                    ? 'No se encontraron distritos.'
                                    : 'Escribe para buscar o desplázate para cargar más.';
                                empty.classList.toggle('hidden', hasItems);
                            }
                        }
                    };

                    const close = () => {
                        panel.classList.add('hidden');
                        trigger.setAttribute('aria-expanded', 'false');
                    };

                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const isOpen = !panel.classList.contains('hidden');
                        document.querySelectorAll('[data-multi-select-panel]').forEach((other) => {
                            if (other !== panel) other.classList.add('hidden');
                        });
                        if (isOpen) {
                            close();
                            return;
                        }
                        panel.classList.remove('hidden');
                        trigger.setAttribute('aria-expanded', 'true');
                        search?.focus();
                        if (nextPage !== null && list.querySelectorAll('[data-remote-item]').length === 0) {
                            load(true);
                        }
                    });

                    list.addEventListener('scroll', () => {
                        if (list.scrollTop + list.clientHeight >= list.scrollHeight - 64) {
                            load(false);
                        }
                    });

                    if (search) {
                        search.addEventListener('input', () => {
                            clearTimeout(searchTimer);
                            searchTimer = setTimeout(() => {
                                searchTerm = search.value.trim();
                                load(true);
                            }, 250);
                        });
                        search.addEventListener('click', (event) => event.stopPropagation());
                    }

                    root.querySelectorAll('[data-multi-select-option]').forEach((input) => {
                        input.addEventListener('change', updateSummary);
                        input.addEventListener('click', (event) => event.stopPropagation());
                    });

                    panel.addEventListener('click', (event) => event.stopPropagation());
                    document.addEventListener('click', () => close());
                    updateSummary();
                };

                document.querySelectorAll('[data-multi-select][data-multi-select-source]').forEach(bindRemoteSelect);
            })();
        </script>
    @endpush
@endonce

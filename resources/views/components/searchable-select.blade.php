@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Seleccionar…',
    'emptyLabel' => null,
    'required' => false,
    'disabled' => false,
    'filterKey' => null,
    'wrapperClass' => '',
])

@php
    $selectedValue = old($name, $selected);
    $selectedValue = $selectedValue === null || $selectedValue === '' ? '' : (string) $selectedValue;
    $selectedLabel = $placeholder;
    $normalized = [];

    if ($emptyLabel !== null) {
        $normalized[] = [
            'value' => '',
            'label' => $emptyLabel,
            'filter' => '',
        ];
    }

    foreach ($options as $option) {
        $value = (string) data_get($option, 'id', data_get($option, 'value'));
        $labelText = (string) data_get($option, 'name', data_get($option, 'label'));
        $filter = $filterKey ? (string) data_get($option, $filterKey, '') : '';

        $normalized[] = [
            'value' => $value,
            'label' => $labelText,
            'filter' => $filter,
        ];

        if ($selectedValue !== '' && $value === $selectedValue) {
            $selectedLabel = $labelText;
        }
    }

    if ($selectedValue === '' && $emptyLabel !== null) {
        $selectedLabel = $emptyLabel;
    }
@endphp

<div
    class="relative {{ $wrapperClass }}"
    data-searchable-select
    @if ($filterKey) data-filter-key="{{ $filterKey }}" @endif
    {{ $attributes }}
>
    <label for="{{ $name }}_display" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
        {{ $label }}{{ $required ? ' *' : '' }}
    </label>

    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $selectedValue }}" data-ss-value @required($required)>

    <button
        type="button"
        id="{{ $name }}_display"
        data-ss-trigger
        @disabled($disabled)
        class="flex w-full items-center justify-between gap-2 rounded border border-border bg-surface px-4 py-2.5 text-left text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-secondary disabled:text-muted"
    >
        <span data-ss-label class="truncate {{ $selectedValue === '' ? 'text-muted' : 'text-text' }}">{{ $selectedLabel }}</span>
        <svg class="h-4 w-4 shrink-0 text-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div data-ss-panel class="absolute z-30 mt-1 hidden w-full overflow-hidden rounded border border-border bg-surface shadow-lg">
        <div class="border-b border-border p-2">
            <input
                type="search"
                data-ss-search
                placeholder="Buscar…"
                class="w-full rounded border border-border bg-secondary px-3 py-2 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
        </div>
        <ul data-ss-list class="max-h-56 overflow-y-auto py-1" role="listbox">
            @foreach ($normalized as $item)
                <li>
                    <button
                        type="button"
                        data-ss-option
                        data-value="{{ $item['value'] }}"
                        data-label="{{ $item['label'] }}"
                        @if ($filterKey !== null) data-filter-value="{{ $item['filter'] }}" @endif
                        class="block w-full px-3 py-2 text-left text-sm text-text hover:bg-secondary {{ $selectedValue === $item['value'] ? 'bg-primary-soft text-primary font-semibold' : '' }}"
                    >
                        {{ $item['label'] }}
                    </button>
                </li>
            @endforeach
        </ul>
        <p data-ss-empty class="hidden px-3 py-3 text-sm text-muted">Sin resultados</p>
    </div>
</div>

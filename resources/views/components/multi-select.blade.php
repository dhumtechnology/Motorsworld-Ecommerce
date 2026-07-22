@props([
    'name',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Seleccionar...',
    'label' => null,
    'id' => null,
    'dependsOn' => null,
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
@endphp

<div
    class="relative"
    data-multi-select
    data-multi-select-key="{{ $name }}"
    data-placeholder="{{ $placeholder }}"
    @if ($dependsOn)
        data-depends-on="{{ $dependsOn }}"
    @endif
>
    @if ($label)
        <label for="{{ $fieldId }}-trigger" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
            {{ $label }}
        </label>
    @endif

    <button
        type="button"
        id="{{ $fieldId }}-trigger"
        data-multi-select-trigger
        class="flex w-full items-center justify-between gap-2 rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-left text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
        aria-haspopup="listbox"
        aria-expanded="false"
    >
        <span data-multi-select-summary class="truncate {{ $selectedLabels->isEmpty() ? 'text-neutral-500' : 'text-white' }}">
            {{ $summary }}
        </span>
        <svg class="h-4 w-4 shrink-0 text-neutral-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        data-multi-select-panel
        class="absolute left-0 right-0 z-30 mt-1 hidden overflow-hidden rounded border border-neutral-700 bg-[#1e1e1e] shadow-xl"
        role="listbox"
        aria-multiselectable="true"
    >
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
                        class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm text-neutral-300 hover:bg-[#252525] hover:text-white"
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
                            class="h-4 w-4 rounded border-neutral-600 bg-[#252525] text-orange-600 focus:ring-orange-500"
                            @checked($isChecked)
                        >
                        <span>{{ $option->name }}</span>
                    </label>
                </li>
            @empty
                <li class="px-3 py-3 text-xs text-neutral-500" data-multi-select-empty>
                    No hay opciones disponibles.
                </li>
            @endforelse
            <li class="hidden px-3 py-3 text-xs text-neutral-500" data-multi-select-filtered-empty>
                No hay opciones para la selección actual.
            </li>
        </ul>
    </div>
</div>

@props([
    'min' => 0,
    'max' => 100,
    'valueMin' => null,
    'valueMax' => null,
    'currency' => 'S/',
])

@php
    $boundMin = (float) $min;
    $boundMax = (float) $max;
    if ($boundMax <= $boundMin) {
        $boundMax = $boundMin + 1;
    }

    $currentMin = $valueMin !== null ? (float) $valueMin : $boundMin;
    $currentMax = $valueMax !== null ? (float) $valueMax : $boundMax;
    $currentMin = max($boundMin, min($currentMin, $boundMax));
    $currentMax = max($boundMin, min($currentMax, $boundMax));
    if ($currentMin > $currentMax) {
        [$currentMin, $currentMax] = [$currentMax, $currentMin];
    }
@endphp

<div
    class="bg-secondary p-6 rounded-md border-neutral-800 text-black select-none"
    x-data="{
        minLimit: {{ $boundMin }},
        maxLimit: {{ $boundMax }},
        minValue: {{ $currentMin }},
        maxValue: {{ $currentMax }},
        submitTimer: null,
        get leftPercent() {
            const span = this.maxLimit - this.minLimit;
            return span <= 0 ? 0 : ((this.minValue - this.minLimit) / span) * 100;
        },
        get rightPercent() {
            const span = this.maxLimit - this.minLimit;
            return span <= 0 ? 0 : ((this.maxValue - this.minLimit) / span) * 100;
        },
        clampMin() {
            if (this.minValue > this.maxValue) {
                this.minValue = this.maxValue;
            }
            this.queueSubmit();
        },
        clampMax() {
            if (this.maxValue < this.minValue) {
                this.maxValue = this.minValue;
            }
            this.queueSubmit();
        },
        queueSubmit() {
            clearTimeout(this.submitTimer);
            this.submitTimer = setTimeout(() => {
                this.$el.closest('form')?.submit();
            }, 350);
        },
        format(value) {
            return new Intl.NumberFormat('es-PE', { maximumFractionDigits: 0 }).format(value);
        }
    }"
>
    <h3 class="font-title tracking-wider uppercase text-xl mb-4 antialiased">
        Precio
    </h3>

    <div class="flex items-center justify-between text-xs font-semibold text-neutral-600 mb-4 font-sans">
        <span x-text="'{{ $currency }} ' + format(minValue)"></span>
        <span x-text="'{{ $currency }} ' + format(maxValue)"></span>
    </div>

    <div class="relative h-8 flex items-center">
        <div class="absolute inset-x-0 h-1.5 rounded-full bg-neutral-300"></div>
        <div
            class="absolute h-1.5 rounded-full bg-orange-600"
            :style="`left: ${leftPercent}%; right: ${100 - rightPercent}%`"
        ></div>

        <input
            type="range"
            name="price_min"
            :min="minLimit"
            :max="maxLimit"
            step="1"
            x-model.number="minValue"
            @input="clampMin()"
            class="catalog-range absolute inset-x-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-moz-range-thumb]:pointer-events-auto"
            aria-label="Precio mínimo"
        >

        <input
            type="range"
            name="price_max"
            :min="minLimit"
            :max="maxLimit"
            step="1"
            x-model.number="maxValue"
            @input="clampMax()"
            class="catalog-range absolute inset-x-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-moz-range-thumb]:pointer-events-auto"
            aria-label="Precio máximo"
        >
    </div>

    <div class="mt-3 flex items-center justify-between text-[10px] uppercase tracking-wider text-neutral-500 font-sans">
        <span>Min</span>
        <span>Max</span>
    </div>
</div>

<style>
    .catalog-range {
        height: 1.5rem;
        margin: 0;
        outline: none;
    }

    .catalog-range::-webkit-slider-runnable-track {
        height: 0.375rem;
        background: transparent;
    }

    .catalog-range::-moz-range-track {
        height: 0.375rem;
        background: transparent;
        border: none;
    }

    .catalog-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 1rem;
        height: 1rem;
        margin-top: -0.3125rem;
        border-radius: 9999px;
        background: #ff6600;
        border: 2px solid #ffffff;
        box-shadow: 0 1px 3px rgb(0 0 0 / 25%);
        cursor: pointer;
        position: relative;
        z-index: 20;
    }

    .catalog-range::-moz-range-thumb {
        width: 1rem;
        height: 1rem;
        border-radius: 9999px;
        background: #ff6600;
        border: 2px solid #ffffff;
        box-shadow: 0 1px 3px rgb(0 0 0 / 25%);
        cursor: pointer;
        position: relative;
        z-index: 20;
    }
</style>

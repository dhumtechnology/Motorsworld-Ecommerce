@props([
    'name' => 'search',
    'placeholder' => 'Producto, marca o categoría…',
    'value' => '',
    'action' => null,
])

@php
    $actionUrl = $action ?? route('shop.catalog');
    $current = is_string($value) ? $value : (string) ($value ?? '');
    $startsOpen = $current !== '';
@endphp

<form
    action="{{ $actionUrl }}"
    method="GET"
    x-data="{
        open: {{ $startsOpen ? 'true' : 'false' }},
        query: @js($current),
        toggle() {
            if (this.open && this.query.trim() !== '') {
                this.$refs.form.submit();
                return;
            }
            this.open = ! this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },
        collapseIfEmpty() {
            if (this.query.trim() === '') {
                this.open = false;
            }
        },
    }"
    x-ref="form"
    @keydown.escape.window="collapseIfEmpty()"
    @click.outside="collapseIfEmpty()"
    {{ $attributes->class('relative flex items-center justify-end') }}
>
    <div
        class="flex items-center overflow-hidden rounded-full transition-[width,background-color,box-shadow,padding] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
        :class="open
            ? 'w-[min(19rem,72vw)] bg-white pl-4 pr-1 py-1 shadow-sm'
            : 'w-9 bg-transparent p-0 shadow-none'"
    >
        <input
            x-ref="input"
            type="search"
            name="{{ $name }}"
            x-model="query"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @keydown.enter.prevent="$refs.form.submit()"
            class="min-w-0 flex-1 border-0 bg-transparent px-1 py-1.5 text-sm leading-normal text-neutral-900 placeholder:text-neutral-400 outline-none ring-0 focus:outline-none focus:ring-0 [&::-webkit-search-cancel-button]:appearance-none"
            :class="open ? 'opacity-100' : 'w-0 opacity-0 pointer-events-none'"
            :tabindex="open ? 0 : -1"
            :aria-hidden="(! open).toString()"
        />

        <button
            type="button"
            @click="toggle()"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70"
            :class="open ? 'text-neutral-900 hover:text-orange-600' : 'text-white hover:text-orange-400'"
            :aria-expanded="open.toString()"
            aria-label="Buscar"
            title="Buscar"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-4.35-4.35M16 10.5a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z"
                />
            </svg>
        </button>
    </div>
</form>

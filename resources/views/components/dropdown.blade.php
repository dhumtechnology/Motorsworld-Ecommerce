@props([
    'title' => 'Menú',
])

<li
    x-data="{ open: false }"
    class="relative flex h-full"
>
    <button
        type="button"
        @click="open = !open"
        @click.away="open = false"
        class="inline-flex h-full items-center gap-2 px-3 text-sm font-semibold uppercase tracking-wide text-white transition-colors hover:bg-orange-600 cursor-pointer"
        :class="open ? 'bg-orange-600' : ''"
    >
        <span>{{ $title }}</span>

        <svg
            class="w-4 h-4 transition-transform"
            :class="{ 'rotate-180': open }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
            />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition
        class="absolute left-0 top-full z-50 w-56 border border-gray-100 bg-white shadow-lg"
        style="display: none;"
    >
        <div class="py-2">
            {{ $slot }}
        </div>
    </div>
</li>

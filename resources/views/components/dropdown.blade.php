<li
    x-data="{ open: false }"
    class="relative"
>
    <button
        @click="open = !open"
        @click.away="open = false"
        class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:text-blue-600 transition"
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
        class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 z-50"
        style="display: none;"
    >
        <div class="py-2">
            {{ $slot }}
        </div>
    </div>
</li>
<div class="relative w-[280px] flex items-center bg-white rounded-full border border-gray-300 focus-within:ring-1 focus-within:ring-orange-500">
    <input
        type="text"
        name="{{ $name ?? 'search' }}"
        placeholder="{{ $placeholder ?? 'Search products...' }}"
        value="{{ $value ?? '' }}"
        class="w-full h-9 px-3 py-1 text-xs text-gray-700 bg-white rounded-full border-none focus:outline-none focus:ring-1 focus:ring-orange-500"
    />

    <button
        type="submit"
        class="w-7 h-7 flex items-center justify-center bg-black rounded-full"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
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
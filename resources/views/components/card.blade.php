@props([
    'title',
    'category',
    'price',
    'oldPrice' => null,
    'image',
    'isSale' => false,
    'discountPercent' => null,
    'href' => '#',
    'cartQty' => 0,
])

<div {{ $attributes->class('bg-white text-black p-4 rounded-md flex flex-col justify-between group transition-all duration-300 border border-transparent hover:border-neutral-800 select-none') }}>

    <div class="relative w-full aspect-square bg-neutral-100 border-neutral-800 rounded-sm overflow-hidden">

        {{-- Etiqueta OFERTA + % --}}
        @if($isSale && $discountPercent)
            <span class="absolute top-2 left-2 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-xs z-10">
                Oferta {{ rtrim(rtrim(number_format((float) $discountPercent, 2, '.', ''), '0'), '.') }}%
            </span>
        @elseif($isSale)
            <span class="absolute top-2 left-2 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-xs z-10">
                Oferta
            </span>
        @endif

        @if($cartQty > 0)
            <span class="absolute top-2 right-2 z-10 min-w-[28px] h-7 px-2 rounded-full bg-neutral-900 text-white text-xs font-black flex items-center justify-center shadow-sm" title="En tu carrito">
                {{ $cartQty }}
            </span>
        @endif

        <a href="{{ $href }}">
            <img src="{{ $image }}"
                alt="{{ $title }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        </a>
    </div>

    <div class="mt-4 flex flex-col flex-grow">
        <a href="{{ $href }}" class="text-black font-bold text-lg leading-tight hover:text-orange-600 transition-colors tracking-wide block font-sans truncate">
            {{ $title }}
        </a>

        <span class="text-neutral-500 text-xs font-semibold tracking-wider uppercase mt-1">
            {{ $category }}
        </span>

        <div class="mt-4 flex items-baseline gap-3">
            <span class="text-neutral-900 font-black text-xl tracking-tight">
                S/ {{ number_format((float) $price, 2) }}
            </span>
            @if($oldPrice)
                <span class="text-neutral-400 line-through text-sm font-semibold">
                    S/ {{ number_format((float) $oldPrice, 2) }}
                </span>
            @endif
        </div>

        @if($cartQty > 0)
            <p class="mt-2 text-xs font-bold text-orange-600 uppercase tracking-wider">
                En carrito: {{ $cartQty }}
            </p>
        @endif
    </div>

</div>

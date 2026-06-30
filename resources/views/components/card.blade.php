@props([
    'title', 
    'category', 
    'price', 
    'oldPrice' => null, 
    'image', 
    'isSale' => false,
    'href' => '#'
])

<div class="text-white p-4 rounded-md flex flex-col justify-between group transition-all duration-300 border border-transparent hover:border-neutral-800 select-none">
    
    <div class="relative w-full aspect-square bg-[#151515] rounded flex items-center justify-center overflow-hidden">
        @if($isSale)
            <span class="absolute top-3 left-3 bg-orange-600 text-white font-black text-[11px] tracking-wider uppercase px-2.5 py-1 rounded-md shadow-sm z-10">
                SALE
            </span>
        @endif
        <a href="{{ $href }}">
            <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        </a>
    </div>

    <div class="mt-4 flex flex-col flex-grow">
        <a href="{{ $href }}" class="text-white font-bold text-lg leading-tight hover:text-orange-500 transition-colors tracking-wide block font-sans truncate">
            {{ $title }}
        </a>
        
        <span class="text-gray-400 text-xs font-semibold tracking-wider uppercase mt-1">
            {{ $category }}
        </span>
        
        <div class="mt-4 flex items-baseline gap-3">
            <span class="text-white font-black text-xl tracking-tight">
                ${{ number_format($price, 0, '.', '') }}
            </span>
            @if($oldPrice)
                <span class="text-gray-500 line-through text-sm font-semibold">
                    ${{ number_format($oldPrice, 0, '.', '') }}
                </span>
            @endif
        </div>
    </div>

    <div class="mt-5">
        <button type="button" class="w-full py-3 bg-orange-600 text-white font-extrabold text-xs tracking-widest rounded hover:bg-orange-700 transition-colors uppercase transition-transform active:scale-[0.98]">
            AGREGAR AL CARRITO
        </button>
    </div>

</div>
@props([
    'title', 
    'category', 
    'price', 
    'oldPrice' => null, 
    'image', 
    'isSale' => false,
    'href' => '#',
    'cartQty' => 0,
])

<div class="text-white p-4 rounded-md flex flex-col justify-between group transition-all duration-300 border border-transparent hover:border-neutral-800 select-none">
    
    <div class="relative w-full aspect-squares border bg-sidebar border-neutral-800 rounded-sm overflow-hidden">
    
        {{-- Etiqueta SALE flotante si aplica --}}
        @if($isSale)
            <span class="absolute top-2 left-2 bg-[#f15a24] text-white text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-xs z-10">
                Sale
            </span>
        @endif

        @if($cartQty > 0)
            <span class="absolute top-2 right-2 z-10 min-w-[28px] h-7 px-2 rounded-full bg-orange-600 text-white text-xs font-black flex items-center justify-center shadow-sm" title="En tu carrito">
                {{ $cartQty }}
            </span>
        @endif

        {{-- Imagen obligada a llenar el contenedor por completo --}}
        <a href="{{ $href }}">
            <img src="{{ $image }}" 
                alt="{{ $title }}" 
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
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

        @if($cartQty > 0)
            <p class="mt-2 text-xs font-bold text-orange-500 uppercase tracking-wider">
                En carrito: {{ $cartQty }}
            </p>
        @endif
    </div>

</div>

@props([
    'items' => [] {{-- Recibirá un array con el formato ['Nombre' => 'URL'] --}}
])

<nav aria-label="Breadcrumb" class="py-4 select-none">
    <ol class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm md:text-base font-sans tracking-wide">
        
        <li>
            <a href="{{ url('/') }}" class="text-neutral-400 hover:text-white uppercase font-black transition-colors duration-150 antialiased">
                HOME
            </a>
        </li>

        @foreach($items as $label => $url)
            <li class="flex items-center gap-x-3">
                <span class="w-1.5 h-1.5 bg-orange-600 shrink-0 transform rotate-0 rounded-sm"></span>
                
                @if(!$loop->last && $url)
                    <a href="{{ $url }}" class="text-orange-600 hover:text-orange-500 uppercase font-black transition-colors duration-150 antialiased">
                        {{ $label }}
                    </a>
                @else
                    <span class="text-orange-600 uppercase font-black antialiased">
                        {{ $label }}
                    </span>
                @endif
            </li>
        @endforeach

    </ol>
</nav>
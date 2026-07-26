@props([
    // Array de arrays: [['label' => 'Nombre', 'url' => 'URL|null'], ...]
    'items' => []
])

<nav aria-label="Breadcrumb" class="py-4 select-none">
    <ol class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm md:text-base tracking-wide">

        <li>
            <a href="{{ url('/') }}" class="text-black hover:text-orange-500 uppercase font-black transition-colors duration-150 antialiased">
                HOME
            </a>
        </li>

        @foreach($items as $item)
            <li class="flex items-center gap-x-3">
                <span class="w-1.5 h-1.5 bg-orange-600 shrink-0 transform rotate-0 rounded-sm"></span>

                @if(!$loop->last && !empty($item['url']))
                    <a href="{{ $item['url'] }}" class="text-black hover:text-orange-500 uppercase font-black transition-colors duration-150 antialiased">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-primary uppercase font-black antialiased">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach

    </ol>
</nav>
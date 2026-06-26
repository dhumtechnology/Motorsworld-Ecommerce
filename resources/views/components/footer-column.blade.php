<!-- <div class="flex flex-col gap-4">
    <h3 class="text-white font-bold tracking-wider uppercase text-lg font-sans">
        {{ $title }}
    </h3>

    <ul class="space-y-3">
        @foreach($links as $text => $url)
            <li>
                <a href="{{ $url }}" class="group flex items-center gap-3 text-gray-300 hover:text-white transition-colors duration-200 uppercase font-medium text-sm tracking-wide">
                    <span class="w-2.5 h-2.5 bg-orange-600 inline-block shrink-0 rounded-sm transform group-hover:scale-110 transition-transform"></span>
                    
                    <span>{{ $text }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div> -->

<div class="flex flex-col gap-3 min-w-0">
    <h3 class="text-white font-extrabold tracking-wider uppercase text-sm font-sans antialiased whitespace-nowrap">
        {{ $title }}
    </h3>

    <ul class="space-y-2.5">
        @foreach($links as $text => $url)
            <li>
                <a href="{{ $url }}" class="group flex items-center gap-2 text-gray-400 hover:text-white transition-colors duration-200 uppercase font-bold text-xs tracking-wide whitespace-nowrap">
                    <span class="w-2 h-2 bg-orange-600 inline-block shrink-0 rounded-sm"></span>
                    
                    <span class="truncate">{{ $text }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
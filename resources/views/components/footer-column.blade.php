<div class="flex flex-col gap-4">
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
</div>
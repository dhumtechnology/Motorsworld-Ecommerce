@if ($paginator->hasPages())
    <div class="w-full flex justify-start items-center select-none font-secondary">
        <nav class="inline-flex gap-2" aria-label="Paginación">
            @if ($paginator->onFirstPage())
                <span class="w-10 h-10 flex items-center justify-center border border-border text-muted text-sm rounded-lg cursor-not-allowed">
                    &lt;
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center border border-border text-text-soft hover:text-primary hover:border-primary transition-colors duration-150 text-sm rounded-lg">
                    &lt;
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="w-10 h-10 flex items-center justify-center border border-border text-muted text-sm rounded-lg">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="w-10 h-10 flex items-center justify-center bg-primary text-white font-bold text-sm rounded-lg">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center border border-border text-text-soft hover:text-primary hover:border-primary transition-colors duration-150 text-sm rounded-lg">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center border border-border text-text-soft hover:text-primary hover:border-primary transition-colors duration-150 text-sm rounded-lg">
                    &gt;
                </a>
            @else
                <span class="w-10 h-10 flex items-center justify-center border border-border text-muted text-sm rounded-lg cursor-not-allowed">
                    &gt;
                </span>
            @endif
        </nav>
    </div>
@endif

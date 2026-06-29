@if ($paginator->hasPages())
    <div class="w-full flex justify-start items-center p-4 select-none mt-12 font-sans">
        <nav class="inline-flex gap-2" aria-label="Paginación del catálogo">
            
            {{-- Botón Anterior (<) --}}
            @if ($paginator->onFirstPage())
                <span class="w-12 h-12 flex items-center justify-center bg-transparent border border-white-300 text-neutral-600 font-black text-sm rounded-sm cursor-not-allowed">
                    &lt;
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="w-12 h-12 flex items-center justify-center bg-transparent border border-white-300 text-neutral-300 hover:text-white hover:border-neutral-600 transition-colors duration-150 font-black text-sm rounded-sm">
                    &lt;
                </a>
            @endif

            {{-- Renderizado de Números Dinámicos --}}
            @foreach ($elements as $element)
                {{-- Separador de puntos "..." --}}
                @if (is_string($element))
                    <span class="w-12 h-12 flex items-center justify-center bg-transparent border border-white-300 text-neutral-500 font-black text-sm rounded-sm">
                        {{ $element }}
                    </span>
                @endif

                {{-- Bloques de páginas numéricas --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="w-12 h-12 flex items-center justify-center bg-[#f15a24] text-white font-black text-sm rounded-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-12 h-12 flex items-center justify-center bg-transparent border border-white-300 text-neutral-300 hover:text-white hover:border-neutral-600 transition-colors duration-150 font-black text-sm rounded-sm">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botón Siguiente (>) --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="w-12 h-12 flex items-center justify-center bg-transparent border border-white-300 text-neutral-300 hover:text-white hover:border-neutral-600 transition-colors duration-150 font-black text-sm rounded-sm">
                    &gt;
                </a>
            @else
                <span class="w-12 h-12 flex items-center justify-center bg-transparent border border-white-300 text-neutral-600 font-black text-sm rounded-sm cursor-not-allowed">
                    &gt;
                </span>
            @endif

        </nav>
    </div>
@endif
{{--
    Home Motosworld

    Imágenes estáticas (copia tus archivos con estos nombres exactos):
    - public/images/home/banner-hero.png
    - public/images/home/taller-1.png
    - public/images/home/taller-2.png
    - public/images/home/taller-3.png
    - public/images/home/taller-4.png
    - public/images/home/need-motos.png
    - public/images/home/need-baterias.png
    - public/images/home/need-accesorios.png
    - public/images/home/need-neumaticos.png
    - public/images/home/need-repuestos.png

    Variables:
    - $popularProducts : Collection<Product> (top 4 por ventas, sin MOTOS)
    - $needLinks       : enlaces de la sección "TENEMOS TODO LO QUE NECESITAS" (legacy/estáticos)
    - $brands          : Collection<Brand> — todas las marcas (id, name, image)
    - $categories      : Collection<Category> — TODAS las categorías incl. MOTOS (id, name, description, image)
--}}
@extends('layouts.shop')

@section('title', config('app.name').' — Inicio')

@section('content')
@php
    $heroImage = asset('images/home/banner-hero.png');
    $mapEmbedUrl = config('shop.map_embed_url');
@endphp

{{-- Banner principal: exactamente el ancho del viewport, sin desborde horizontal --}}
<section class="w-full max-w-[100%] overflow-hidden bg-neutral-900">
    <img
        src="{{ $heroImage }}"
        alt="Motosworld"
        class="block h-auto w-full max-w-full object-contain"
        onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
    >
</section>

{{-- Taller autorizado: 4 PNG verticales pegadas = 100% del ancho, carrusel infinito --}}
<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
        <h2 class="text-center text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Taller y distribuidor autorizado
        </h2>
    </div>

    <div class="w-full max-w-[100%] overflow-hidden bg-neutral-100" aria-label="Marcas y taller autorizado">
        <div class="taller-marquee flex">
            @foreach ([1, 2] as $loopCopy)
                <div class="taller-marquee-set flex w-full shrink-0 gap-0">
                    @foreach ($tallerImages as $index => $image)
                    <div class="taller-marquee-item relative aspect-[1/1] w-1/5 shrink-0 overflow-hidden bg-neutral-200">
                            <img
                                src="{{ $image }}"
                                alt="Taller y distribuidor autorizado {{ $index + 1 }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                onerror="this.classList.add('opacity-0');"
                            >
                    </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    @keyframes taller-marquee-scroll {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }

    .taller-marquee {
        width: 200%;
        animation: taller-marquee-scroll 48s linear infinite;
        will-change: transform;
    }

    .taller-marquee-set {
        width: 50%;
    }

    .taller-marquee:hover {
        animation-play-state: paused;
    }

    @media (prefers-reduced-motion: reduce) {
        .taller-marquee {
            width: 100%;
            animation: none;
        }

        .taller-marquee-set:nth-child(2) {
            display: none;
        }

        .taller-marquee-set {
            width: 100%;
            overflow-x: auto;
        }
    }

    @media (max-width: 639px) {
        .taller-marquee {
            animation-duration: 36s;
        }
    }
</style>

{{-- Tenemos todo lo que necesitas --}}
<section class="bg-white border-t border-neutral-100"
         x-data="{
             current: 0,
             get total() {
                 return this.$refs.slider ? this.$refs.slider.children.length : 0;
             },
             next() {
                 if (this.total === 0) return;
                 this.current = (this.current + 1) % this.total;
                 this.scrollToCurrent();
             },
             prev() {
                 if (this.total === 0) return;
                 this.current = (this.current - 1 + this.total) % this.total;
                 this.scrollToCurrent();
             },
             scrollToCurrent() {
                 const container = this.$refs.slider;
                 const card = container.children[this.current];
                 if (card) {
                     container.scrollTo({
                         left: card.offsetLeft - container.offsetLeft,
                         behavior: 'smooth'
                     });
                 }
             }
         }">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14 relative">
        <h2 class="mb-8 md:mb-10 text-center text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Tenemos todo lo que necesitas
        </h2>

        <!-- Contenedor relativo para posicionar las flechas -->
        <div class="relative group">
            
            <!-- Flecha Izquierda (Anterior) -->
            <button 
                @click="prev()" 
                aria-label="Anterior"
                class="absolute -left-2 md:-left-5 top-1/2 -translate-y-1/2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-neutral-800 shadow-md transition-all hover:bg-orange-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>

            <!-- Flecha Derecha (Siguiente) -->
            <button 
                @click="next()" 
                aria-label="Siguiente"
                class="absolute -right-2 md:-right-5 top-1/2 -translate-y-1/2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-neutral-800 shadow-md transition-all hover:bg-orange-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </button>

            <!-- Slider / Carrusel -->
            <div 
                x-ref="slider"
                class="flex gap-3 sm:gap-4 overflow-x-auto scrollbar-none snap-x snap-mandatory scroll-smooth py-1"
            >
                @foreach ($needLinks as $index => $link)
                    <div class="w-[calc(50%-0.375rem)] sm:w-[calc(50%-0.5rem)] md:w-[calc(20%-0.8rem)] flex-shrink-0 snap-start">
                        <a
                            href="{{ $link['href'] }}"
                            class="group relative block overflow-hidden bg-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500"
                        >
                            <img
                                src="{{ $link['image'] }}"
                                alt="{{ $link['label'] }}"
                                class="block h-auto w-full transition-transform duration-500 ease-out group-hover:scale-105"
                                onerror="this.classList.add('opacity-0');"
                            >
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
                        </a>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</section>

{{-- Productos populares --}}
<section class="bg-white border-t border-neutral-100">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
        <h2 class="mb-8 md:mb-10 text-center text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Productos populares
        </h2>

        @if ($popularProducts->isEmpty())
            <p class="text-center text-sm text-neutral-500">Pronto verás aquí los productos más vendidos.</p>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($popularProducts as $product)
                    @php
                        $brand = $product->vehicleModel?->brand?->name ?? $product->category?->name ?? 'Motosworld';
                        $description = \Illuminate\Support\Str::limit(
                            trim((string) ($product->description ?: $product->name)),
                            90
                        );
                        $price = (float) ($product->effective_price ?? $product->price_amount);
                        $image = $product->image ?: asset('images/home/product-placeholder.png');
                    @endphp

                    <a
                        href="{{ route('shop.product.show', $product) }}"
                        class="group flex flex-col overflow-hidden border border-neutral-200 bg-white transition-shadow hover:shadow-md"
                    >
                        <div class="relative aspect-square overflow-hidden bg-neutral-100">
                            <img
                                src="{{ $image }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            >
                        </div>

                        <div class="grid grid-cols-2 mt-2 gap-0 border-t border-neutral-200 min-h-[5.5rem]">
                            <div class="flex flex-col justify-center gap-1 px-3 py-3 md:px-4">
                                <p class="text-sm font-bold uppercase tracking-wider text-neutral-500">
                                    {{ $brand }}
                                </p>
                                <p class="text-xs font-semibold text-neutral-900 leading-snug line-clamp-3">
                                    {{ $description }}
                                </p>
                            </div>
                            <div class="flex items-center justify-center bg-primary px-3 py-3 text-center">
                                <span class="text-xl font-black text-white tracking-tight">
                                    S/ {{ number_format($price, 2) }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- Mapa a todo el ancho --}}
<section class="w-full bg-neutral-200" aria-label="Ubicación Motosworld">
    <div class="relative w-full aspect-[21/9] min-h-[280px] max-h-[480px]">
        <iframe
            title="Mapa Motosworld"
            src="{{ $mapEmbedUrl }}"
            class="absolute inset-0 h-full w-full border-0"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
</section>
@endsection

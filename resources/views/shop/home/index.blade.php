{{--
    Home Motoworld

    Imágenes estáticas (copia tus archivos con estos nombres exactos):
    - public/images/home/banner-hero.png
    - public/images/home/portadas/1 HOME - bienvenidos a mw 2.jpg
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
    - $popularProducts : Collection<Product> (top por ventas; incluye motos)
    - $brands          : Collection<Brand> — marcas con imagen (id, name, image)
    - $categories      : Collection<Category> — categorías con imagen (id, name, description, image)
    - $heroSlides      : list<string> — URLs del carrusel (admin o imágenes por defecto)
--}}
@extends('layouts.shop')

@section('title', config('app.name').' — Inicio')

@section('content')
@php
    $heroSlides = $heroSlides ?? [
        asset('images/home/banner-hero.png'),
        asset('images/home/portadas/1 HOME - bienvenidos a mw 2.jpg'),
    ];
    $mapEmbedUrl = config('shop.map_embed_url');
@endphp

{{-- Banner principal: carrusel con flechas y autoplay --}}
<section
    class="home-hero relative w-full max-w-[100%] overflow-hidden bg-neutral-900"
    x-data="{
        active: 0,
        total: {{ count($heroSlides) }},
        timer: null,
        next() {
            if (this.total === 0) return;
            this.active = (this.active + 1) % this.total;
            this.start();
        },
        prev() {
            if (this.total === 0) return;
            this.active = (this.active - 1 + this.total) % this.total;
            this.start();
        },
        start() {
            this.stop();
            if (this.total <= 1) return;
            this.timer = setInterval(() => {
                this.active = (this.active + 1) % this.total;
            }, 10000);
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
    }"
    x-init="start()"
    aria-label="Banner Motoworld"
>
    <div class="home-hero-frame relative w-full overflow-hidden">
        {{-- La 1.ª imagen define la altura del banner. --}}
        <img
            src="{{ $heroSlides[0] }}"
            alt=""
            aria-hidden="true"
            class="home-hero-sizer block h-auto w-full max-w-full"
        >
        @foreach ($heroSlides as $index => $slide)
            <img
                src="{{ $slide }}"
                alt="Motoworld"
                class="home-hero-slide{{ $index === 0 ? ' is-active' : '' }}"
                :class="{ 'is-active': active === {{ $index }} }"
                @if ($index === 0) loading="eager" @else loading="lazy" @endif
                :aria-hidden="active === {{ $index }} ? 'false' : 'true'"
            >
        @endforeach
    </div>

    @if (count($heroSlides) > 1)
        <button
            type="button"
            @click="prev()"
            aria-label="Banner anterior"
            class="absolute left-3 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white shadow-md transition-all hover:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500 sm:left-5"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>
        <button
            type="button"
            @click="next()"
            aria-label="Banner siguiente"
            class="absolute right-3 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white shadow-md transition-all hover:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500 sm:right-5"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    @endif
</section>

<style>
    .home-hero-frame {
        position: relative;
        width: 100%;
        overflow: hidden;
    }

    .home-hero-sizer {
        display: block;
        width: 100%;
        height: auto;
        visibility: hidden;
        pointer-events: none;
    }

    .home-hero-slide {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        /* Cubre todo el ancho; si sobra, recorta en Y (y en X si hace falta). */
        object-fit: cover;
        object-position: center;
        opacity: 0;
        transition: opacity 1.2s ease-in-out;
        will-change: opacity;
        pointer-events: none;
    }

    /* Mismo z-index + solo opacity = crossfade en ambos sentidos. */
    .home-hero-slide.is-active {
        opacity: 1;
    }
</style>

{{-- Marcas: imágenes desde Brand (admin) --}}
<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
        <h2 class="text-center text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Taller y distribuidor autorizado
        </h2>
    </div>

    @if ($brands->isEmpty())
        <div class="w-full bg-neutral-100 px-4 py-10 text-center text-sm text-neutral-500">
            Pronto verás aquí las marcas autorizadas.
        </div>
    @else
        @php
            $brandStrip = $brands->values();
            while ($brandStrip->isNotEmpty() && $brandStrip->count() < 5) {
                $brandStrip = $brandStrip->concat($brands);
            }
            // Dos vueltas idénticas = loop circular sin salto.
            $brandLoop = $brandStrip->concat($brandStrip);
        @endphp
        <div class="brands-marquee-viewport w-full overflow-hidden bg-neutral-100" aria-label="Marcas autorizadas">
            <div class="brands-marquee-track">
                @foreach ($brandLoop as $index => $brand)
                    <div class="brands-marquee-item" @if ($index >= $brandStrip->count()) aria-hidden="true" @endif>
                        <img
                            src="{{ $brand->image }}"
                            alt="{{ $brand->name }}"
                            title="{{ $brand->name }}"
                            class="brands-marquee-logo"
                            loading="eager"
                            onerror="this.classList.add('opacity-0');"
                        >
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>

<style>
    @keyframes brands-marquee-scroll {
        from { transform: translate3d(0, 0, 0); }
        to { transform: translate3d(-50%, 0, 0); }
    }

    .brands-marquee-viewport {
        width: 100%;
    }

    .brands-marquee-track {
        display: flex;
        width: max-content;
        animation: brands-marquee-scroll 40s linear infinite;
        will-change: transform;
    }

    /* Pausar solo al pasar sobre una marca (no al hover genérico del carril). */
    .brands-marquee-track:has(.brands-marquee-item:hover) {
        animation-play-state: paused;
    }

    .brands-marquee-item {
        position: relative;
        z-index: 1;
        flex: 0 0 20vw;
        width: 20vw;
        aspect-ratio: 1 / 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: visible;
        background: #fff;
    }

    .brands-marquee-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 1rem;
        transform: scale(1);
        transition: transform 0.25s ease;
        pointer-events: none;
    }

    .brands-marquee-item:hover {
        z-index: 5;
    }

    .brands-marquee-item:hover .brands-marquee-logo {
        transform: scale(1.12);
    }

    @media (max-width: 639px) {
        .brands-marquee-item {
            flex-basis: 40vw;
            width: 40vw;
        }

        .brands-marquee-track {
            animation-duration: 28s;
        }

        .brands-marquee-logo {
            padding: 0.85rem;
        }
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .brands-marquee-item {
            flex-basis: 25vw;
            width: 25vw;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .brands-marquee-track {
            animation-duration: 80s;
        }

        .brands-marquee-item:hover .brands-marquee-logo {
            transform: scale(1.06);
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
        <div class="relative">
            
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
                @forelse ($categories as $category)
                    @php
                        $isMotos = strtoupper(trim((string) $category->name)) === 'MOTOCICLETAS';
                        $categoryHref = $isMotos
                            ? route('shop.catalog', ['section' => 'motos'])
                            : route('shop.catalog', [
                                'section' => 'accesorios',
                                'categories' => [$category->id],
                            ]);
                    @endphp
                    <div class="w-[calc(50%-0.375rem)] sm:w-[calc(50%-0.5rem)] md:w-[calc(20%-0.8rem)] flex-shrink-0 snap-start">
                        <a
                            href="{{ $categoryHref }}"
                            class="group/category relative block aspect-[3/4] overflow-hidden bg-neutral-200 opacity-80 transition-opacity duration-300 hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:opacity-100"
                        >
                            <img
                                src="{{ $category->image }}"
                                alt="{{ $category->name }}"
                                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover/category:scale-105"
                                loading="lazy"
                                onerror="this.classList.add('opacity-0');"
                            >
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent"></div>
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 p-3 sm:p-4">
                                <p class="text-center text-sm font-black uppercase tracking-[0.12em] text-white font-title drop-shadow">
                                    {{ $category->name }}
                                </p>
                            </div>
                        </a>
                    </div>
                @empty
                    <p class="w-full py-8 text-center text-sm text-neutral-500">
                        Pronto verás aquí las categorías disponibles.
                    </p>
                @endforelse
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
            <div
                class="-mx-4 flex gap-4 overflow-x-auto px-4 pb-2 snap-x snap-mandatory scroll-smooth scrollbar-none md:mx-0 md:grid md:grid-cols-2 md:gap-5 md:overflow-visible md:px-0 md:pb-0 md:snap-none lg:grid-cols-4"
            >
                @foreach ($popularProducts as $product)
                    @php
                        $brand = $product->vehicleModel?->brand?->name ?? $product->category?->name ?? 'Motoworld';
                        $description = \Illuminate\Support\Str::limit(
                            trim((string) ($product->description ?: $product->name)),
                            90
                        );
                        $price = (float) ($product->effective_price ?? $product->price_amount);
                        $currencySymbol = $product->currencySymbol();
                        $image = $product->image ?: asset('images/home/product-placeholder.png');
                    @endphp

                    <a
                        href="{{ route('shop.product.show', $product) }}"
                        class="group flex w-[78%] shrink-0 snap-start flex-col overflow-hidden border border-neutral-200 bg-white transition-shadow hover:shadow-md sm:w-[45%] md:w-auto md:min-w-0 md:shrink"
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
                                    {{ $currencySymbol }} {{ number_format($price, 2) }}
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
<section class="w-full bg-neutral-200" aria-label="Ubicación Motoworld">
    <div class="relative w-full aspect-[21/9] min-h-[280px] max-h-[480px]">
        <iframe
            title="Mapa Motoworld"
            src="{{ $mapEmbedUrl }}"
            class="absolute inset-0 h-full w-full border-0"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
</section>
@endsection

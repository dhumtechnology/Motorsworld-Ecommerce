@extends('layouts.shop')

@section('title', 'Preguntas frecuentes — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/home/portadas/Enllantado.jpg'))
        ? asset('images/home/portadas/Enllantado.jpg')
        : asset('images/services/banner-servicios.png');

    $customerServiceItems = [
        [
            'label' => 'Correo electrónico',
            'value' => 'info@motoworld.pe',
            'href' => 'mailto:info@motoworld.pe',
        ],
        [
            'label' => 'Atención al cliente',
            'value' => '+51 '.$contact['mobile'],
            'href' => 'tel:'.$contact['mobile_tel'],
        ],
    ];

    $searchTips = [
        'Introduce el concepto que desees en el buscador y automáticamente se desplegarán los resultados más relevantes relacionados con tu búsqueda. Puedes buscar por tipo de productos, por modelo, por color o por el nombre del producto completo.',
        'En la parte izquierda de cada página encontrarás una lista con los filtros de búsqueda, eligiendo entre características de cada producto, tamaño, color, etc. Puedes combinar varios filtros de búsqueda para limitar al máximo los resultados y que se asemejen lo más posible a lo que estás buscando.',
        'En las páginas de catálogo podrás ordenar los resultados por popularidad o por precio.',
    ];
@endphp

{{-- Hero --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[440px] w-full">
        <img
            src="{{ $banner }}"
            alt="Preguntas frecuentes Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            loading="eager"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-black/25"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Información para clientes</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Preguntas frecuentes
            </h1>
            <p class="mt-2 max-w-2xl text-sm md:text-base text-white/85">
                Respuestas sobre atención al cliente y cómo encontrar productos en nuestra tienda.
            </p>
        </div>
    </div>
</section>

{{-- Navegación rápida --}}
<nav class="sticky top-16 z-30 border-b border-neutral-200 bg-white/95 backdrop-blur sm:top-[4.25rem] lg:top-20" aria-label="Secciones de preguntas frecuentes">
    <div class="mx-auto max-w-[95%] px-4 md:px-8">
        <ul class="flex gap-2 overflow-x-auto py-3 text-xs font-bold uppercase tracking-wider scrollbar-none">
            <li class="shrink-0"><a href="#atencion" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Atención al cliente</a></li>
            <li class="shrink-0"><a href="#busqueda" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Búsqueda de productos</a></li>
        </ul>
    </div>
</nav>

{{-- Atención al cliente --}}
<section id="atencion" class="bg-neutral-50 border-b border-neutral-100 scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mx-auto max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Contacto</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Atención al cliente
            </h2>
            <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-700">
                Con el fin de poder ayudarle ponemos a su disposición el siguiente número de teléfono y dirección de correo electrónico.
            </p>

            <ol class="mt-8 space-y-4">
                @foreach ($customerServiceItems as $index => $item)
                    <li class="flex gap-4 rounded-2xl border border-neutral-200 bg-white p-5 md:p-6 shadow-sm">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-600 text-sm font-black text-white">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500">
                                {{ $item['label'] }}
                            </p>
                            <a
                                href="{{ $item['href'] }}"
                                class="mt-1 inline-flex text-base md:text-lg font-bold text-orange-600 hover:text-orange-500 transition-colors"
                            >
                                {{ $item['value'] }}
                            </a>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

{{-- Búsqueda de productos --}}
<section id="busqueda" class="bg-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mx-auto max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Catálogo</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Búsqueda de productos
            </h2>

            <div class="mt-8 space-y-6">
                @foreach ($searchTips as $tip)
                    <article class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 md:p-8">
                        <p class="text-sm md:text-base leading-relaxed text-neutral-700">
                            {{ $tip }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-neutral-900 text-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-12 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg md:text-xl font-black uppercase tracking-wide font-title">
                ¿No encontraste lo que buscabas?
            </h2>
            <p class="mt-1 text-sm text-neutral-300">
                Visita el catálogo o escríbenos y te ayudamos personalmente.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('shop.catalog', ['section' => 'accesorios']) }}"
                class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
            >
                Ver catálogo
            </a>
            <a
                href="{{ route('shop.help') }}"
                class="inline-flex items-center justify-center rounded-xl border border-white/20 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:border-orange-500 hover:bg-white/5 transition-colors"
            >
                Ayuda
            </a>
        </div>
    </div>
</section>
@endsection

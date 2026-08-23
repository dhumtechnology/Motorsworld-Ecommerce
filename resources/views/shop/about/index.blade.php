{{--
    Nosotros / About

    Imágenes (opcionales; hay fallbacks):
    - public/images/about/banner-nosotros.png
    - public/images/about/mision.png
    - public/images/about/vision.png
--}}
@extends('layouts.shop')

@section('title', 'Nosotros — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/about/banner-nosotros.png'))
        ? asset('images/about/banner-nosotros.png')
        : asset('images/services/banner-servicios.png');

    $misionImage = file_exists(public_path('images/about/mision.png'))
        ? asset('images/about/mision.png')
        : asset('images/home/taller-1.png');

    $visionImage = file_exists(public_path('images/about/vision.png'))
        ? asset('images/about/vision.png')
        : asset('images/home/taller-2.png');

    $policies = [
        [
            'title' => 'Satisfacción del cliente',
            'body' => 'Nos esforzamos por comprender y superar las expectativas de nuestros clientes en cada interacción.',
        ],
        [
            'title' => 'Mejora continua',
            'body' => 'Buscamos constantemente formas de mejorar nuestros procesos y servicios para ofrecer la mejor calidad posible.',
        ],
        [
            'title' => 'Desarrollo del personal',
            'body' => 'Invertimos en la capacitación y desarrollo de nuestros empleados para garantizar que tengan las habilidades y conocimientos necesarios para brindar un servicio de alta calidad y a la vanguardia de la tecnología implementada en las motocicletas.',
        ],
    ];
@endphp

{{-- Banner --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[480px] w-full">
        <img
            src="images/about/NOSOTROS.jpg"
            alt="Nosotros Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Nosotros
            </h1>
            <p class="mt-2 max-w-xl text-sm md:text-base text-white/85">
                Pasión por las motos, compromiso con el servicio y calidad en cada detalle.
            </p>
        </div>
    </div>
</section>

{{-- Historia / presentación --}}
<section class="bg-neutral-50 border-b border-neutral-100">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16 text-center">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">Nuestra historia</p>
        <h2 class="mt-3 text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Quiénes somos
        </h2>
        <p class="mt-5 text-sm md:text-base leading-relaxed text-neutral-600 text-justify">
            Desde el 2022, Moto World ha logrado posicionarse como uno de los talleres de motocicletas más
            importantes en Lima, brindando la confianza y seguridad al motociclista peruano. Contamos con el
            respaldo de sólidas marcas con representación a nivel nacional e internacional.
        </p>
    </div>
</section>

{{-- Misión y Visión --}}
<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 text-center">
            <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                Misión y visión
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
            {{-- Misión --}}
            <article class="group overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm">
                <div class="aspect-[16/10] overflow-hidden bg-neutral-200">
                    <img
                        src="images/about/MISION.jpg"
                        alt="Misión Motoworld"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy"
                    >
                </div>
                <div class="p-6 md:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Nuestra misión</p>
                    <h3 class="mt-2 text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                        Misión
                    </h3>
                    <p class="mt-3 text-sm md:text-base leading-relaxed text-neutral-600">
                        Generar valor en nuestros clientes a través de productos y servicios de altos estándares
                        que contribuyan al crecimiento de la cultura motociclista peruana.
                    </p>
                </div>
            </article>

            {{-- Visión --}}
            <article class="group overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm">
                <div class="aspect-[16/10] overflow-hidden bg-neutral-200">
                    <img
                        src="images/about/vision.png"
                        alt="Visión Motoworld"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy"
                    >
                </div>
                <div class="p-6 md:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Nuestra visión</p>
                    <h3 class="mt-2 text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                        Visión
                    </h3>
                    <p class="mt-3 text-sm md:text-base leading-relaxed text-neutral-600">
                        Ser la empresa con los clientes más satisfechos y los colaboradores más motivados en el
                        mercado motociclista.
                    </p>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- Políticas de calidad --}}
<section id="politicas-de-calidad" class="relative bg-neutral-100 overflow-hidden">
    <div class="relative mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12 items-start">
            <div class="lg:col-span-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Compromiso Motoworld</p>
                <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                    Políticas de calidad
                </h2>
                <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-600">
                    En Moto World estamos comprometidos con la excelencia en la prestación de nuestros servicios.
                    Nuestra política de calidad está basada en los siguientes principios:
                </p>
            </div>

            <div class="lg:col-span-7 space-y-4">
                @foreach ($policies as $index => $policy)
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 md:p-6 shadow-sm">
                        <div class="flex gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-600 text-sm font-black text-white">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                                    {{ $policy['title'] }}
                                </h3>
                                <p class="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {{ $policy['body'] }}
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach

                <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 md:p-6">
                    <p class="text-sm md:text-base leading-relaxed text-neutral-800">
                        Nuestro compromiso de calidad va dirigido a obtener la satisfacción total de nuestros clientes.
                        En Motoworld nos comprometemos a cumplir con los compromisos adquiridos durante la venta de
                        unidades, repuestos y servicio técnico de los rubros en los que participamos. Orientamos nuestra
                        gestión hacia la mejora continua de los procesos y el desarrollo integral de nuestros colaboradores.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-black">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-12 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg md:text-xl font-black uppercase tracking-wide text-white font-title">
                ¿Listo para visitarnos?
            </h2>
            <p class="mt-1 text-sm text-white/70">
                Reserva tu cita en taller o explora nuestra tienda.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('shop.services.index') }}"
                class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
            >
                Reservar cita
            </a>
            <a
                href="{{ route('shop.catalog', ['section' => 'accesorios']) }}"
                class="inline-flex items-center justify-center rounded-xl border border-white/30 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:border-orange-500 hover:text-orange-400 transition-colors"
            >
                Ver tienda
            </a>
        </div>
    </div>
</section>
@endsection

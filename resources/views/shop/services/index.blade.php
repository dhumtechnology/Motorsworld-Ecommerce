{{--
    Servicios - portada
    Portada: public/images/home/portadas/NUESTROS SERVICIOS.jpg
--}}
@extends('layouts.shop')

@section('title', 'Servicios — '.config('app.name'))

@section('content')
@php
$bannerPath = public_path('images/home/portadas/NUESTROS SERVICIOS.jpg');
$banner = file_exists($bannerPath)
? asset('images/home/portadas/NUESTROS SERVICIOS.jpg')
: asset('images/services/banner-servicios.png');

// Variables para las imágenes de las tarjetas
$imgExplorar = asset('images/services/explorar-servicios.jpg');
$imgReservar = asset('images/services/reservar-servicio.jpg');
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[480px] w-full">
        <img
            src="{{ $banner }}"
            alt="Servicios Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Servicio Técnico Especializado
            </h1>
            <p class="mt-2 text-sm md:text-base text-white/85 max-w-xl">
                Horario de atención: Lunes a viernes de 9:30 a.m. a 6:00 p.m.
            </p>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 text-center">
            <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                Elige una opción
            </h2>
            <p class="mt-2 text-sm text-neutral-500">
                Explora todos los servicios o agenda tu cita en pocos pasos.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
            {{-- Misión --}}
            <article class="group overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm">
                <div class="aspect-[16/10] overflow-hidden bg-neutral-200">
                    <img
                        src="images/about/MISION.jpg"
                        alt="Misión Motoworld"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy">
                </div>
                <div class="flex flex-1 flex-col p-6 md:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Explorar</p>
                    <h3 class="mt-2 text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                        Todos los servicios
                    </h3>
                    <p class="mt-3 text-sm md:text-base leading-relaxed text-neutral-600 flex-1">
                        Revisa el listado completo de servicios técnicos disponibles para tu moto.
                    </p>
                    <a
                        href="{{ route('shop.services.list') }}"
                        class="mt-6 inline-flex w-full sm:w-auto self-start items-center justify-center rounded-lg bg-neutral-900 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-700 focus-visible:ring-offset-2">
                        Ver servicios
                    </a>
                </div>
            </article>

            {{-- Visión --}}
            <article class="group overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm">
                <div class="aspect-[16/10] overflow-hidden bg-neutral-200">
                    <img
                        src="images/about/vision.png"
                        alt="Visión Motoworld"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy">
                </div>
                <div class="flex flex-1 flex-col p-6 md:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-700">Reservar</p>
                    <h3 class="mt-2 text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                        Agendar un servicio
                    </h3>
                    <p class="mt-3 text-sm md:text-base leading-relaxed text-neutral-700 flex-1">
                        Completa el formulario y selecciona el horario para registrar tu reserva.
                    </p>
                    <a
                        href="{{ route('shop.services.booking') }}"
                        class="mt-6 inline-flex w-full sm:w-auto self-start items-center justify-center rounded-lg bg-orange-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2">
                        Ir al formulario
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
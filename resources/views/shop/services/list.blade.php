{{--
    Servicios - listado
--}}
@extends('layouts.shop')

@section('title', 'Todos los servicios — '.config('app.name'))

@section('content')
@php
$bannerPath = public_path('images/home/portadas/NUESTROS SERVICIOS.jpg');
$banner = file_exists($bannerPath)
? asset('images/home/portadas/NUESTROS SERVICIOS.jpg')
: asset('images/services/banner-servicios.png');
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[420px] w-full">
        <img
            src="{{ $banner }}"
            alt="Servicios Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Todos nuestros servicios
            </h1>
            <p class="mt-2 text-sm md:text-base text-white/85 max-w-xl">
                Conoce las soluciones que tenemos para mantener tu moto en óptimo estado.
            </p>
        </div>
    </div>
</section>

@if ($serviceTypes->isNotEmpty())
<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
        <div class="mb-8 text-center">
            <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                Nuestros servicios
            </h2>
            <p class="mt-2 text-sm text-neutral-500">
                Explora cada servicio disponible en el taller Motoworld.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($serviceTypes as $type)
            <article class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition hover:shadow-md">
                <div class="aspect-[4/3] overflow-hidden bg-neutral-100">
                    @if ($type->image)
                    <img
                        src="{{ $type->image }}"
                        alt="{{ $type->name }}"
                        class="h-full w-full object-cover"
                        loading="lazy">
                    @else
                    <div class="flex h-full w-full items-center justify-center bg-neutral-200 text-neutral-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    @endif
                </div>
                <div class="p-5">
                    <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                        {{ $type->name }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-600">
                        {{ $type->description ?: 'Consulta disponibilidad y reserva tu cita en el taller.' }}
                    </p>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
<section class="bg-neutral-50 border-b border-neutral-100">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16 text-center">
        <h2 class="mt-3 text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Agenda tu cita en pocos pasos
        </h2>
        <p class="mt-5 text-sm md:text-base leading-relaxed text-neutral-600 text-center">
            Si ya elegiste el servicio que deseas, puedes agendar tu cita en línea de manera rápida y sencilla para que tu moto reciba la atención que necesita. Nuestro equipo de expertos estará listo para brindarte el mejor servicio.
        </p>
        <br>
        <a
            href="{{ route('shop.services.booking') }}"
            class="inline-flex items-center justify-center rounded-lg bg-orange-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2">
            Agendar un servicio
        </a>
    </div>
</section>
@endif
@endsection
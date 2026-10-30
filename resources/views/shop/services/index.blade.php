{{--
    Servicios - portada
    Portada: public/images/home/portadas/NUESTROS SERVICIOS.jpg
--}}
@extends('layouts.shop')

@section('title', 'Servicios — '.config('app.name'))

@section('content')
@php
// Variables para las imágenes de las tarjetas
$imgExplorar = asset('images/services/explorar-servicios.jpg');
$imgReservar = asset('images/services/reservar-servicio.jpg');
@endphp

<x-shop.page-header
    page="services"
    title="Servicio Técnico Especializado"
    subtitle="Horario de atención: lunes a viernes de 9:00 a 13:00 h y de 14:00 a 18:30 h. Sábados, de 9:00 a 14:00 h."
    alt="Servicios Motoworld"
    overlay="from-black/70 via-black/25 to-black/20"
/>

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
                        src="{{ asset('images/services/todos-los-servicios.jpeg') }}"
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
                        class="mt-6 inline-flex w-full sm:w-auto self-start items-center justify-center rounded-lg border border-orange-600 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-orange-600 transition hover:bg-orange-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2">
                        Ver servicios
                    </a>
                </div>
            </article>

            {{-- Visión --}}
            <article class="group overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm">
                <div class="aspect-[16/10] overflow-hidden bg-neutral-200">
                    <img
                        src="{{ asset('images/services/agendar-servicio.jpeg') }}"
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
                        class="mt-6 inline-flex w-full sm:w-auto self-start items-center justify-center rounded-lg border border-orange-600 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-orange-600 transition hover:bg-orange-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2">
                        Ir al formulario
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
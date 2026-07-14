{{--
    Home — maqueta base para diseñador / frontend.

    Ruta: GET /
    Nombre: shop.home
--}}
@extends('layouts.shop')

@section('title', config('app.name').' — Inicio')

@section('content')
<div class="text-white">
    {{-- Hero (placeholder) --}}
    <section class="border-b border-neutral-800">
        <div class="mx-auto max-w-[95%] px-4 md:px-8 py-20 md:py-28">
            <p class="text-xs font-bold uppercase tracking-widest text-orange-500 mb-3">Motosworld</p>
            <h1 class="text-3xl md:text-5xl font-black uppercase tracking-wide max-w-2xl leading-tight">
                Todo para tu moto en un solo lugar
            </h1>
            <p class="mt-4 text-neutral-400 text-sm md:text-base max-w-xl leading-relaxed">
                Maqueta temporal del Home. El diseñador y frontend reemplazarán esta sección.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}"
                   class="inline-block rounded bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors">
                    Ver catálogo
                </a>
                <a href="{{ route('shop.catalog', ['section' => 'motos']) }}"
                   class="inline-block rounded border border-neutral-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-neutral-300 hover:border-neutral-400 hover:text-white transition-colors">
                    Ver motos
                </a>
            </div>
        </div>
    </section>

    {{-- Bloques placeholder --}}
    <section class="border-b border-neutral-800">
        <div class="mx-auto max-w-[95%] px-4 md:px-8 py-14 grid gap-6 md:grid-cols-3">
            <div class="rounded border border-dashed border-neutral-700 bg-[#1e1e1e]/60 p-6 min-h-[140px]">
                <p class="text-xs font-bold uppercase tracking-widest text-neutral-500 mb-2">Sección 1</p>
                <p class="text-sm text-neutral-400">Espacio reservado (destacados, banners, etc.).</p>
            </div>
            <div class="rounded border border-dashed border-neutral-700 bg-[#1e1e1e]/60 p-6 min-h-[140px]">
                <p class="text-xs font-bold uppercase tracking-widest text-neutral-500 mb-2">Sección 2</p>
                <p class="text-sm text-neutral-400">Espacio reservado (servicios, beneficios, etc.).</p>
            </div>
            <div class="rounded border border-dashed border-neutral-700 bg-[#1e1e1e]/60 p-6 min-h-[140px]">
                <p class="text-xs font-bold uppercase tracking-widest text-neutral-500 mb-2">Sección 3</p>
                <p class="text-sm text-neutral-400">Espacio reservado (promos, blog, etc.).</p>
            </div>
        </div>
    </section>
</div>
@endsection

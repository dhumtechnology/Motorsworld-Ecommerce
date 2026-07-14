@extends('layouts.admin')

@section('title', 'Dashboard — Admin')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen del panel administrativo')

@section('content')
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
            <p class="text-xs uppercase tracking-widest text-neutral-500 font-bold">Módulos activos</p>
            <p class="text-3xl font-black text-white mt-2">2</p>
            <p class="text-sm text-neutral-400 mt-2">Dashboard y productos</p>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
            <p class="text-xs uppercase tracking-widest text-neutral-500 font-bold">Productos</p>
            <p class="text-3xl font-black text-orange-500 mt-2">{{ $productCount }}</p>
            <a href="{{ route('admin.products.index') }}" class="inline-block mt-3 text-sm font-bold text-orange-500 hover:text-orange-400 transition-colors">
                Ver listado →
            </a>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 opacity-60">
            <p class="text-xs uppercase tracking-widest text-neutral-500 font-bold">Próximamente</p>
            <p class="text-sm text-neutral-400 mt-3">Pedidos, usuarios, citas y más módulos se irán habilitando en el sidebar.</p>
        </div>
    </div>

    <div class="mt-8 rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
        <h2 class="text-sm font-black uppercase tracking-wide text-white mb-2">Bienvenido al panel</h2>
        <p class="text-neutral-400 text-sm leading-relaxed">
            Este es el punto de partida del administrador de Motosworld. Usa el menú lateral para navegar entre módulos.
            Comienza gestionando el catálogo desde <strong class="text-neutral-300">Productos</strong>.
        </p>
    </div>
@endsection

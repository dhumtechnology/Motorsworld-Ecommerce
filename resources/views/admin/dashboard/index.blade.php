@extends('layouts.admin')

@section('title', 'Dashboard — Admin')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen del panel administrativo')

@section('content')
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="admin-card p-6">
            <p class="admin-label mb-0">Módulos activos</p>
            <p class="text-3xl font-title text-text mt-2">{{ 10 }}</p>
            <p class="text-sm text-muted mt-2 font-secondary">Catálogo, ventas, reservas e inventario</p>
        </div>

        <div class="admin-card p-6">
            <p class="admin-label mb-0">Productos</p>
            <p class="text-3xl font-title text-primary mt-2">{{ $productCount }}</p>
            <a href="{{ route('admin.products.index') }}" class="inline-block mt-3 text-sm font-bold text-primary hover:text-primary-hover transition-colors font-secondary">
                Ver listado →
            </a>
        </div>

        <div class="admin-card p-6">
            <p class="admin-label mb-0">Accesos rápidos</p>
            <div class="mt-3 flex flex-col gap-2 text-sm font-secondary">
                <a href="{{ route('admin.orders.index') }}" class="text-text-soft hover:text-primary transition-colors">Órdenes</a>
                <a href="{{ route('admin.inventory.index') }}" class="text-text-soft hover:text-primary transition-colors">Inventario</a>
                <a href="{{ route('admin.appointments.index') }}" class="text-text-soft hover:text-primary transition-colors">Reservas</a>
            </div>
        </div>
    </div>

    <div class="mt-6 admin-card p-6">
        <h2 class="font-title text-lg text-text mb-2">Bienvenido al panel</h2>
        <p class="text-muted text-sm leading-relaxed font-secondary max-w-3xl">
            Usa el menú lateral para navegar entre módulos. El panel usa la identidad visual de Motosworld:
            tipografías Inter / Roboto / Oswald y el naranja primario de la marca.
        </p>
    </div>
@endsection

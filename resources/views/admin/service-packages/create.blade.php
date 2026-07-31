@extends('layouts.admin')

@section('title', 'Nuevo paquete — Admin')
@section('page-title', 'Nuevo paquete')
@section('page-subtitle', 'Paquete de servicio para reservas')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.service-packages.store') }}">
            @csrf
            @include('admin.service-packages._form')
        </form>
    </div>
@endsection

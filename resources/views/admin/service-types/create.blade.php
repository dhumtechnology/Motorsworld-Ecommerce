@extends('layouts.admin')

@section('title', 'Nuevo servicio — Admin')
@section('page-title', 'Nuevo servicio')
@section('page-subtitle', 'Tipo de servicio para reservas')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.service-types.store') }}">
            @csrf
            @include('admin.service-types._form')
        </form>
    </div>
@endsection

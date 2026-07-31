@extends('layouts.admin')

@section('title', 'Editar paquete — Admin')
@section('page-title', 'Editar paquete')
@section('page-subtitle', $servicePackage->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.service-packages.update', $servicePackage) }}">
            @csrf
            @method('PUT')
            @include('admin.service-packages._form', ['servicePackage' => $servicePackage])
        </form>
    </div>
@endsection

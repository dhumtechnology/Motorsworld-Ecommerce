@extends('layouts.admin')

@section('title', 'Editar servicio — Admin')
@section('page-title', 'Editar servicio')
@section('page-subtitle', $serviceType->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.service-types.update', $serviceType) }}">
            @csrf
            @method('PUT')
            @include('admin.service-types._form', ['serviceType' => $serviceType])
        </form>
    </div>
@endsection

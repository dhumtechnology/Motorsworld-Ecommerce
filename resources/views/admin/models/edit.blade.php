@extends('layouts.admin')

@section('title', 'Editar modelo — Admin')
@section('page-title', 'Editar modelo')
@section('page-subtitle', $vehicleModel->name)

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.models.update', $vehicleModel) }}">
            @csrf
            @method('PUT')
            @include('admin.models._form', ['vehicleModel' => $vehicleModel])
        </form>
    </div>
@endsection

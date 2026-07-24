@extends('layouts.admin')

@section('title', 'Nuevo producto — Admin')
@section('page-title', 'Nuevo producto')
@section('page-subtitle', 'Completa los datos del producto')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.products._form')
        </form>
    </div>

    {{-- Fuera del form del producto: HTML no admite formularios anidados --}}
    @include('admin.products._quick-create-modals')
    @include('admin.products._form-scripts')
@endsection

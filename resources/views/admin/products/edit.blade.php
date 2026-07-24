@extends('layouts.admin')

@section('title', 'Editar producto — Admin')
@section('page-title', 'Editar producto')
@section('page-subtitle', $product->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.products._form', ['product' => $product])
        </form>
    </div>

    {{-- Fuera del form del producto: HTML no admite formularios anidados --}}
    @include('admin.products._quick-create-modals')
    @include('admin.products._form-scripts')
@endsection

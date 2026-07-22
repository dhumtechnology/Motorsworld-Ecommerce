@extends('layouts.admin')

@section('title', 'Editar producto — Admin')
@section('page-title', 'Editar producto')
@section('page-subtitle', $product->name)

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.products._form', ['product' => $product])
        </form>
    </div>
@endsection

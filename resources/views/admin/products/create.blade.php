@extends('layouts.admin')

@section('title', 'Nuevo producto — Admin')
@section('page-title', 'Nuevo producto')
@section('page-subtitle', 'Completa los datos del producto')

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.products._form')
        </form>
    </div>
@endsection

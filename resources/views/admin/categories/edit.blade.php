@extends('layouts.admin')

@section('title', 'Editar categoría — Admin')
@section('page-title', 'Editar categoría')
@section('page-subtitle', $category->name)

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['category' => $category])
        </form>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Editar marca — Admin')
@section('page-title', 'Editar marca')
@section('page-subtitle', $brand->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.brands.update', $brand) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.brands._form', ['brand' => $brand])
        </form>
    </div>
@endsection

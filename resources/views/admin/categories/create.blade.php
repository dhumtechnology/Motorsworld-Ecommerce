@extends('layouts.admin')

@section('title', 'Nueva categoría — Admin')
@section('page-title', 'Nueva categoría')
@section('page-subtitle', 'Completa los datos de la categoría')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            @include('admin.categories._form')
        </form>
    </div>
@endsection

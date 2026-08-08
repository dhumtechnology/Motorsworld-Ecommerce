@extends('layouts.admin')

@section('title', 'Nuevo rol — Admin')
@section('page-title', 'Nuevo rol')
@section('page-subtitle', 'Define el rol y asígnale permisos')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            @include('admin.roles._form')
        </form>
    </div>
@endsection

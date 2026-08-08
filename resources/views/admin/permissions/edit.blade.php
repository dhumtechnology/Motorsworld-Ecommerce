@extends('layouts.admin')

@section('title', 'Editar permiso — Admin')
@section('page-title', 'Editar permiso')
@section('page-subtitle', $permission->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
            @csrf
            @method('PUT')
            @include('admin.permissions._form', ['permission' => $permission])
        </form>
    </div>
@endsection

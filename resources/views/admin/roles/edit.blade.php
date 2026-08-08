@extends('layouts.admin')

@section('title', 'Editar rol — Admin')
@section('page-title', 'Editar rol')
@section('page-subtitle', $role->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('admin.roles._form', ['role' => $role])
        </form>
    </div>
@endsection

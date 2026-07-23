@extends('layouts.admin')

@section('title', 'Nuevo usuario — Admin')
@section('page-title', 'Nuevo usuario')
@section('page-subtitle', 'Cuenta con acceso al panel administrativo')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users._form')
        </form>
    </div>
@endsection

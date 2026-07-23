@extends('layouts.admin')

@section('title', 'Nuevo usuario — Admin')
@section('page-title', 'Nuevo usuario')
@section('page-subtitle', 'Cuenta con acceso al panel administrativo')

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users._form')
        </form>
    </div>
@endsection

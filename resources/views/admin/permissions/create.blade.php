@extends('layouts.admin')

@section('title', 'Nuevo permiso — Admin')
@section('page-title', 'Nuevo permiso')
@section('page-subtitle', 'Define un permiso para asignarlo a roles')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf
            @include('admin.permissions._form')
        </form>
    </div>
@endsection

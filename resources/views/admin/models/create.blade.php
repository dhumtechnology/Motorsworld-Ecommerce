@extends('layouts.admin')

@section('title', 'Nuevo modelo — Admin')
@section('page-title', 'Nuevo modelo')
@section('page-subtitle', 'Asocia el modelo a una marca')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.models.store') }}">
            @csrf
            @include('admin.models._form')
        </form>
    </div>
@endsection

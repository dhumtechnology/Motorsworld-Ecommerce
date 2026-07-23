@extends('layouts.admin')

@section('title', 'Nueva marca — Admin')
@section('page-title', 'Nueva marca')
@section('page-subtitle', 'Completa los datos de la marca')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.brands._form')
        </form>
    </div>
@endsection

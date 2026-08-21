@extends('layouts.admin')

@section('title', 'Nuevo banner — Admin')
@section('page-title', 'Nuevo banner')
@section('page-subtitle', 'Configuración del home')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.home-banners.store') }}" enctype="multipart/form-data" id="home-banner-form">
            @csrf
            @include('admin.home-banners._form')
        </form>
    </div>
@endsection

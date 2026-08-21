@extends('layouts.admin')

@section('title', 'Editar banner — Admin')
@section('page-title', 'Editar banner')
@section('page-subtitle', $banner->title ?: 'Banner del home')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.home-banners.update', $banner) }}" enctype="multipart/form-data" id="home-banner-form">
            @csrf
            @method('PUT')
            @include('admin.home-banners._form', ['banner' => $banner])
        </form>
    </div>
@endsection

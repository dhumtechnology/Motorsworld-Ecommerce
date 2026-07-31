@extends('layouts.admin')

@section('title', 'Nueva publicación — Admin')
@section('page-title', 'Nueva publicación')
@section('page-subtitle', 'Crea un artículo para el blog')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.blog-posts.store') }}" enctype="multipart/form-data" id="blog-post-form">
            @csrf
            @include('admin.blog-posts._form')
        </form>
    </div>
@endsection

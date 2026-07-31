@extends('layouts.admin')

@section('title', 'Editar publicación — Admin')
@section('page-title', 'Editar publicación')
@section('page-subtitle', $blogPost->title)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.blog-posts.update', $blogPost) }}" enctype="multipart/form-data" id="blog-post-form">
            @csrf
            @method('PUT')
            @include('admin.blog-posts._form', ['blogPost' => $blogPost])
        </form>
    </div>
@endsection

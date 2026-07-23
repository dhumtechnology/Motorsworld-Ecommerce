@extends('layouts.admin')

@section('title', 'Editar usuario — Admin')
@section('page-title', 'Editar usuario')
@section('page-subtitle', $user->email)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])
        </form>
    </div>
@endsection

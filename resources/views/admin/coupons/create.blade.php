@extends('layouts.admin')

@section('title', 'Nuevo cupón — Admin')
@section('page-title', 'Nuevo cupón')
@section('page-subtitle', 'Código de descuento para el checkout')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            @include('admin.coupons._form')
        </form>
    </div>
@endsection

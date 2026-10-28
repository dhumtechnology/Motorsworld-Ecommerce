@extends('layouts.admin')

@section('title', 'Nueva tarifa — Admin')
@section('page-title', 'Nueva tarifa')
@section('page-subtitle', 'Zona geográfica y precio de envío')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.shipping-rates.store') }}">
            @csrf
            @include('admin.shipping-rates._form')
        </form>
    </div>
@endsection

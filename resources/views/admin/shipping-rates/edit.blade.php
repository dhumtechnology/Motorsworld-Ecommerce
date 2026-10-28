@extends('layouts.admin')

@section('title', 'Editar tarifa — Admin')
@section('page-title', 'Editar tarifa')
@section('page-subtitle', $shippingRate->name)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.shipping-rates.update', $shippingRate) }}">
            @csrf
            @method('PUT')
            @include('admin.shipping-rates._form', ['shippingRate' => $shippingRate])
        </form>
    </div>
@endsection

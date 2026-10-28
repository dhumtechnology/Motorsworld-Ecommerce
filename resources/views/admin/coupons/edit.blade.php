@extends('layouts.admin')

@section('title', 'Editar cupón — Admin')
@section('page-title', 'Editar cupón')
@section('page-subtitle', $coupon->code)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
            @csrf
            @method('PUT')
            @include('admin.coupons._form', ['coupon' => $coupon])
        </form>
    </div>
@endsection

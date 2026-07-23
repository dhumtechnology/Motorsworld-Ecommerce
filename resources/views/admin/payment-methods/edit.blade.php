@extends('layouts.admin')

@section('title', 'Editar medio de pago — Admin')
@section('page-title', 'Editar medio de pago')
@section('page-subtitle', $paymentMethod->name)

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.payment-methods.update', $paymentMethod) }}">
            @csrf
            @method('PUT')
            @include('admin.payment-methods._form', ['paymentMethod' => $paymentMethod])
        </form>
    </div>
@endsection

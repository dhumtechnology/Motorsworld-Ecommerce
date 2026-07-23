@extends('layouts.admin')

@section('title', 'Nuevo medio de pago — Admin')
@section('page-title', 'Nuevo medio de pago')
@section('page-subtitle', 'Agregar un método disponible para cobros')

@section('content')
    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.payment-methods.store') }}">
            @csrf
            @include('admin.payment-methods._form')
        </form>
    </div>
@endsection

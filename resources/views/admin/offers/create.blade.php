@extends('layouts.admin')

@section('title', 'Nueva oferta — Admin')
@section('page-title', 'Nueva oferta')
@section('page-subtitle', 'Define un precio promocional para un producto')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.offers.store') }}">
            @csrf
            @include('admin.offers._form', [
                'products' => $products,
                'preselectedProductId' => $preselectedProductId,
                'redirectTo' => $preselectedProductId ? 'product' : null,
                'cancelUrl' => $preselectedProductId
                    ? route('admin.products.show', $preselectedProductId)
                    : route('admin.offers.index'),
            ])
        </form>
    </div>
@endsection

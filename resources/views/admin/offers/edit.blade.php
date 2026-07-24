@extends('layouts.admin')

@section('title', 'Editar oferta — Admin')
@section('page-title', 'Editar oferta')
@section('page-subtitle', '#'.$offer->id)

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.offers.update', $offer) }}">
            @csrf
            @method('PUT')
            @include('admin.offers._form', [
                'offer' => $offer,
                'products' => $products,
            ])
        </form>
    </div>
@endsection

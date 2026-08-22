{{--
    Libro de reclamaciones (hoja virtual)

    Portada: public/images/home/portadas/Scaneo de motocicleta.jpg
--}}
@extends('layouts.shop')

@section('title', 'Libro de reclamaciones — '.config('app.name'))

@section('content')
@php
    $bannerPath = public_path('images/home/portadas/Scaneo de motocicleta.jpg');
    $banner = file_exists($bannerPath)
        ? asset('images/home/portadas/Scaneo de motocicleta.jpg')
        : asset('images/services/banner-servicios.png');

    $field = 'w-full rounded-lg border border-neutral-200 bg-white px-5 py-2.5 text-sm text-neutral-900 shadow-sm transition placeholder:text-neutral-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $label = 'mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500';
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[420px] w-full">
        <img
            src="{{ $banner }}"
            alt="Libro de reclamaciones Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Atención al consumidor</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Libro de reclamaciones
            </h1>
            <p class="mt-2 max-w-xl text-sm md:text-base text-white/85">
                Conforme al Código de Protección y Defensa del Consumidor (Ley N.º 29571).
            </p>
        </div>
    </div>
</section>

<section class="bg-neutral-100">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-8">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 md:p-8 shadow-[0_18px_50px_-28px_rgba(0,0,0,0.25)]">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-neutral-100 pb-6">
                        <div>
                            <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                                Hoja de reclamación
                            </h2>
                            <p class="mt-2 text-sm text-neutral-500">
                                Completa todos los campos marcados con *. La respuesta se emitirá en el plazo legal.
                            </p>
                        </div>
                        <img
                            src="{{ asset('images/home/libro-de-reclamaciones.png') }}"
                            alt="Libro de reclamaciones"
                            class="h-16 w-auto shrink-0"
                            width="64"
                            height="74"
                        >
                    </div>

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <p class="font-bold mb-1">Revisa el formulario:</p>
                            <ul class="list-disc pl-5 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('shop.claim-book.store') }}" class="mt-6 space-y-8">
                        @csrf

                        <fieldset>
                            <legend class="mb-4 text-sm font-black uppercase tracking-[0.14em] text-neutral-900 font-title">
                                1. Identificación del consumidor
                            </legend>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="first_name" class="{{ $label }}">Nombres *</label>
                                    <input id="first_name" type="text" name="first_name" required maxlength="100"
                                        value="{{ old('first_name', $prefill['first_name'] ?? '') }}" class="{{ $field }}" placeholder="Tus nombres">
                                </div>
                                <div>
                                    <label for="last_name" class="{{ $label }}">Apellidos *</label>
                                    <input id="last_name" type="text" name="last_name" required maxlength="100"
                                        value="{{ old('last_name', $prefill['last_name'] ?? '') }}" class="{{ $field }}" placeholder="Tus apellidos">
                                </div>
                                <div>
                                    <label for="document" class="{{ $label }}">DNI / CE / RUC *</label>
                                    <input id="document" type="text" name="document" required maxlength="20"
                                        value="{{ old('document', $prefill['document'] ?? '') }}" class="{{ $field }}" placeholder="Documento de identidad">
                                </div>
                                <div>
                                    <label for="phone" class="{{ $label }}">Teléfono *</label>
                                    <input id="phone" type="text" name="phone" required maxlength="30"
                                        value="{{ old('phone', $prefill['phone'] ?? '') }}" class="{{ $field }}" placeholder="Ej. 920 883 723">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="address" class="{{ $label }}">Domicilio *</label>
                                    <input id="address" type="text" name="address" required maxlength="255"
                                        value="{{ old('address', $prefill['address'] ?? '') }}" class="{{ $field }}" placeholder="Dirección completa">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="email" class="{{ $label }}">Email *</label>
                                    <input id="email" type="email" name="email" required maxlength="255"
                                        value="{{ old('email', $prefill['email'] ?? '') }}" class="{{ $field }}" placeholder="tu@email.com">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="mb-4 text-sm font-black uppercase tracking-[0.14em] text-neutral-900 font-title">
                                2. Identificación del bien contratado
                            </legend>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <p class="{{ $label }}">Tipo *</p>
                                    <div class="flex flex-wrap gap-4">
                                        <label class="inline-flex items-center gap-2 text-sm text-neutral-800 cursor-pointer">
                                            <input type="radio" name="good_type" value="product" required
                                                class="text-orange-600 focus:ring-orange-500"
                                                @checked(old('good_type', 'product') === 'product')>
                                            Producto
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm text-neutral-800 cursor-pointer">
                                            <input type="radio" name="good_type" value="service" required
                                                class="text-orange-600 focus:ring-orange-500"
                                                @checked(old('good_type') === 'service')>
                                            Servicio
                                        </label>
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="good_description" class="{{ $label }}">Descripción *</label>
                                    <textarea id="good_description" name="good_description" rows="3" required maxlength="2000"
                                        class="{{ $field }}"
                                        placeholder="Describe el producto o servicio contratado">{{ old('good_description') }}</textarea>
                                </div>
                                <div>
                                    <label for="claimed_amount" class="{{ $label }}">Monto reclamado (S/)</label>
                                    <input id="claimed_amount" type="number" name="claimed_amount" min="0" step="0.01" max="9999999.99"
                                        value="{{ old('claimed_amount') }}" class="{{ $field }}" placeholder="Opcional">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="mb-4 text-sm font-black uppercase tracking-[0.14em] text-neutral-900 font-title">
                                3. Detalle de la reclamación
                            </legend>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <p class="{{ $label }}">Tipo *</p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer gap-3 rounded-xl border border-neutral-200 p-4 transition hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50/50">
                                            <input type="radio" name="claim_type" value="claim" required
                                                class="mt-1 text-orange-600 focus:ring-orange-500"
                                                @checked(old('claim_type', 'claim') === 'claim')>
                                            <span>
                                                <span class="block text-sm font-bold text-neutral-900">Reclamo</span>
                                                <span class="mt-0.5 block text-xs text-neutral-500">
                                                    Disconformidad relacionada a los productos o servicios.
                                                </span>
                                            </span>
                                        </label>
                                        <label class="flex cursor-pointer gap-3 rounded-xl border border-neutral-200 p-4 transition hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50/50">
                                            <input type="radio" name="claim_type" value="complaint" required
                                                class="mt-1 text-orange-600 focus:ring-orange-500"
                                                @checked(old('claim_type') === 'complaint')>
                                            <span>
                                                <span class="block text-sm font-bold text-neutral-900">Queja</span>
                                                <span class="mt-0.5 block text-xs text-neutral-500">
                                                    Malestar o descontento respecto a la atención al público.
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label for="detail" class="{{ $label }}">Detalle *</label>
                                    <textarea id="detail" name="detail" rows="4" required maxlength="5000"
                                        class="{{ $field }}"
                                        placeholder="Describe los hechos que motivan tu reclamo o queja">{{ old('detail') }}</textarea>
                                </div>
                                <div>
                                    <label for="consumer_request" class="{{ $label }}">Pedido del consumidor *</label>
                                    <textarea id="consumer_request" name="consumer_request" rows="3" required maxlength="5000"
                                        class="{{ $field }}"
                                        placeholder="Indica lo que solicitas (reembolso, cambio, reparación, disculpas, etc.)">{{ old('consumer_request') }}</textarea>
                                </div>
                            </div>
                        </fieldset>

                        <p class="text-xs leading-relaxed text-neutral-500">
                            La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.
                            El proveedor deberá dar respuesta en un plazo no mayor a quince (15) días hábiles.
                        </p>

                        <div class="flex justify-center pt-2">
                            <button type="submit"
                                class="inline-flex min-w-[220px] items-center justify-center rounded-xl bg-orange-600 px-12 py-4 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-orange-600/25 transition hover:bg-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2">
                                Enviar hoja de reclamación
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <aside class="lg:col-span-4 space-y-4">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Proveedor</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide text-neutral-900 font-title">Motoworld</h3>
                    <ul class="mt-3 space-y-2 text-sm text-neutral-600">
                        <li>
                            <span class="font-semibold text-neutral-800">Dirección:</span>
                            {{ $contact['address'] }}
                        </li>
                        <li>
                            <span class="font-semibold text-neutral-800">Teléfono:</span>
                            <a href="tel:{{ $contact['phone_tel'] }}" class="hover:text-orange-600">{{ $contact['phone'] }}</a>
                        </li>
                        <li>
                            <span class="font-semibold text-neutral-800">Email:</span>
                            <a href="mailto:{{ $contact['email'] }}" class="hover:text-orange-600">{{ $contact['email'] }}</a>
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-black p-6 text-white shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Información</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide font-title">¿Reclamo o queja?</h3>
                    <ul class="mt-3 space-y-3 text-sm text-white/80">
                        <li>
                            <span class="font-semibold text-white">Reclamo:</span>
                            relacionado al producto o servicio.
                        </li>
                        <li>
                            <span class="font-semibold text-white">Queja:</span>
                            relacionado a la atención recibida.
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Otras vías</p>
                    <p class="mt-2 text-sm text-neutral-600">
                        También puedes escribirnos por el formulario de contacto o acercarte al taller.
                    </p>
                    <a href="{{ route('shop.contact') }}" class="mt-3 inline-flex text-sm font-bold text-orange-600 hover:text-orange-500">
                        Ir a contacto →
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection

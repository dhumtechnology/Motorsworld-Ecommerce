{{--
    Contacto

    Banner opcional: public/images/contact/banner-contacto.png
--}}
@extends('layouts.shop')

@section('title', 'Contáctanos — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/contact/banner-contacto.png'))
        ? asset('images/contact/banner-contacto.png')
        : asset('images/services/banner-servicios.png');

    $field = 'w-full rounded-lg border border-neutral-200 bg-white px-5 py-2.5 text-sm text-neutral-900 shadow-sm transition placeholder:text-neutral-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $label = 'mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500';
@endphp

{{-- Banner --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[420px] w-full">
        <img
            src="{{ $banner }}"
            alt="Contáctanos Motosworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Motosworld</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Contáctanos
            </h1>
            <p class="mt-2 max-w-xl text-sm md:text-base text-white/85">
                Estamos listos para ayudarte. Escríbenos o visita nuestro taller.
            </p>
        </div>
    </div>
</section>

{{-- Formulario + info --}}
<section class="bg-neutral-100">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
            {{-- Formulario --}}
            <div class="lg:col-span-7">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 md:p-8 shadow-[0_18px_50px_-28px_rgba(0,0,0,0.25)]">
                    <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                        Escríbenos
                    </h2>
                    <p class="mt-2 text-sm text-neutral-500">
                        Completa el formulario y te responderemos a la brevedad.
                    </p>

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

                    <form method="POST" action="{{ route('shop.contact.store') }}" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @csrf

                        <div>
                            <label for="first_name" class="{{ $label }}">Nombres *</label>
                            <input id="first_name" type="text" name="first_name" required maxlength="100"
                                value="{{ old('first_name') }}" class="{{ $field }}" placeholder="Tus nombres">
                        </div>

                        <div>
                            <label for="last_name" class="{{ $label }}">Apellidos *</label>
                            <input id="last_name" type="text" name="last_name" required maxlength="100"
                                value="{{ old('last_name') }}" class="{{ $field }}" placeholder="Tus apellidos">
                        </div>

                        <div>
                            <label for="document" class="{{ $label }}">DNI *</label>
                            <input id="document" type="text" name="document" required maxlength="20"
                                value="{{ old('document') }}" class="{{ $field }}" placeholder="Documento de identidad">
                        </div>

                        <div>
                            <label for="phone" class="{{ $label }}">Teléfono *</label>
                            <input id="phone" type="text" name="phone" required maxlength="30"
                                value="{{ old('phone') }}" class="{{ $field }}" placeholder="Ej. 920 883 723">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="{{ $label }}">Email *</label>
                            <input id="email" type="email" name="email" required maxlength="255"
                                value="{{ old('email') }}" class="{{ $field }}" placeholder="tu@email.com">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="message" class="{{ $label }}">Mensaje *</label>
                            <textarea id="message" name="message" rows="5" required maxlength="5000"
                                class="{{ $field }}"
                                placeholder="¿En qué podemos ayudarte?">{{ old('message') }}</textarea>
                        </div>

                        <div class="sm:col-span-2 flex justify-center pt-2">
                            <button type="submit"
                                class="inline-flex min-w-[220px] items-center justify-center rounded-xl bg-orange-600 px-12 py-4 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-orange-600/25 transition hover:bg-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2">
                                Enviar mensaje
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Info de contacto --}}
            <aside class="lg:col-span-5 space-y-4">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Ubícanos</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide text-neutral-900 font-title">Dirección</h3>
                    <p class="mt-2 text-sm text-neutral-600">{{ $contact['address'] }}</p>
                    <a
                        href="https://maps.google.com/?q=Av.+Militar+2134,+Lince"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex text-sm font-bold text-orange-600 hover:text-orange-500"
                    >
                        Ver en Google Maps →
                    </a>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Atención al cliente</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide text-neutral-900 font-title">Contáctanos</h3>
                    <ul class="mt-3 space-y-2 text-sm text-neutral-600">
                        <li>
                            <span class="font-semibold text-neutral-800">Teléfono:</span>
                            <a href="tel:{{ $contact['phone_tel'] }}" class="hover:text-orange-600">{{ $contact['phone'] }}</a>
                        </li>
                        <li>
                            <span class="font-semibold text-neutral-800">Celular:</span>
                            <a href="tel:{{ $contact['mobile_tel'] }}" class="hover:text-orange-600">{{ $contact['mobile'] }}</a>
                        </li>
                        <li>
                            <span class="font-semibold text-neutral-800">Email:</span>
                            <a href="mailto:{{ $contact['email'] }}" class="hover:text-orange-600">{{ $contact['email'] }}</a>
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-black p-6 text-white shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Horarios</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide font-title">Horarios de atención</h3>
                    <ul class="mt-3 space-y-2 text-sm text-white/80">
                        <li>{{ $contact['hours']['weekdays'] }}</li>
                        <li>{{ $contact['hours']['saturday'] }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>

{{-- Mapa --}}
<section class="w-full bg-neutral-200" aria-label="Ubicación Motosworld">
    <div class="relative w-full aspect-[21/9] min-h-[280px] max-h-[480px]">
        <iframe
            title="Mapa Motosworld — Av. Militar 2134, Lince"
            src="{{ $mapEmbedUrl }}"
            class="absolute inset-0 h-full w-full border-0"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
</section>
@endsection

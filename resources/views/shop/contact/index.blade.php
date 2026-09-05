{{--
    Contacto

    Portada: public/images/home/portadas/Enllantado.jpg
--}}
@extends('layouts.shop')

@section('title', 'Contáctanos — '.config('app.name'))

@section('content')
@php
    $bannerPath = public_path('images/home/portadas/Enllantado.jpg');
    $banner = file_exists($bannerPath)
        ? asset('images/home/portadas/Enllantado.jpg')
        : asset('images/services/banner-servicios.png');

    $field = 'w-full rounded-lg border border-neutral-200 bg-white px-5 py-2.5 text-sm text-neutral-900 shadow-sm transition placeholder:text-neutral-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $label = 'mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500';
@endphp

{{-- Banner --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[420px] w-full">
        <img
            src="{{ $banner }}"
            alt="Contáctanos Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Motoworld</p>
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

                    <form
                        method="POST"
                        action="{{ route('shop.contact.store') }}"
                        class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2"
                        x-data="{ submitting: false }"
                        @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
                    >
                        @csrf

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
                            <label for="document" class="{{ $label }}">DNI *</label>
                            <input id="document" type="text" name="document" required maxlength="20"
                                value="{{ old('document', $prefill['document'] ?? '') }}" class="{{ $field }}" placeholder="Documento de identidad">
                        </div>

                        <div>
                            <label for="phone" class="{{ $label }}">Teléfono *</label>
                            <input id="phone" type="text" name="phone" required maxlength="30"
                                value="{{ old('phone', $prefill['phone'] ?? '') }}" class="{{ $field }}" placeholder="Ej. 920 883 723">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="{{ $label }}">Email *</label>
                            <input id="email" type="email" name="email" required maxlength="255"
                                value="{{ old('email', $prefill['email'] ?? '') }}" class="{{ $field }}" placeholder="tu@email.com">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="message" class="{{ $label }}">Mensaje *</label>
                            <textarea id="message" name="message" rows="5" required maxlength="5000"
                                class="{{ $field }}"
                                placeholder="¿En qué podemos ayudarte?">{{ old('message') }}</textarea>
                        </div>

                        <div class="sm:col-span-2 flex justify-center pt-2">
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex min-w-[220px] items-center justify-center gap-2 rounded-xl bg-orange-600 px-12 py-4 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-orange-600/25 transition hover:bg-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <svg
                                    x-show="submitting"
                                    x-cloak
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Enviando…' : 'Enviar mensaje'">Enviar mensaje</span>
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
                <div class="rounded-2xl border border-neutral-200 bg-white px-6 py-4 shadow-sm font-title font-bold uppercase">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Síguenos</p>
                    <p>Redes sociales</p>
                    <div class="flex items-center gap-2 pt-2">
                        <a href="https://www.facebook.com/motoworld.pe/?ref=PROFILE_EDIT_xav_ig_profile_page_web#" target="_blank">
                            <svg width="30px" height="30px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450">
                                <g transform="translate(0 450) scale(1 -1)">
                                    <g transform="translate(-252.9396,-221.9863)">
                                    <path
                                        fill="#000000"
                                        fill-rule="evenodd"
                                        d="m 0,0 h -306.422 c -39.484,0 -71.789,32.305 -71.789,71.789 v 306.422 c 0,39.484 32.305,71.789 71.789,71.789 H 0 c 39.484,0 71.789,-32.305 71.789,-71.789 V 71.789 C 71.789,32.305 39.484,0 0,0"
                                        transform="translate(631.1506,221.9863)"
                                    />

                                    <path
                                        fill="#ffffff"
                                        fill-rule="evenodd"
                                        d="m 0,0 h 61.34 v 141.501 h 45.308 l 9.062,57.158 H 61.34 v 43.217 c 0,16.032 15.335,25.094 29.973,25.094 h 26.488 v 47.399 L 70.402,316.46 C 25.094,319.248 0,283.699 0,241.179 v -42.52 H -51.582 V 141.501 H 0 Z"
                                        transform="translate(435.7835,277.1723)"
                                    />
                                    </g>
                                </g>
                            </svg>
                        </a>
                        <a href="https://www.instagram.com/motoworld.pe">
                            <svg width="30px" height="30px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450">
                                <g transform="translate(0 450) scale(1 -1)">
                                    <g transform="translate(-900.6796,-221.9863)">
                                    <path
                                        fill="#000000"
                                        fill-rule="evenodd"
                                        d="m 0,0 h -306.422 c -39.484,0 -71.789,32.305 -71.789,71.789 v 306.422 c 0,39.484 32.305,71.789 71.789,71.789 H 0 c 39.484,0 71.789,-32.305 71.789,-71.789 V 71.789 C 71.789,32.305 39.484,0 0,0"
                                        transform="translate(1278.8906,221.9863)"
                                    />

                                    <path
                                        fill="#ffffff"
                                        fill-rule="evenodd"
                                        d="m 0,0 h 130.895 c 29.468,0 53.454,-23.986 53.454,-53.454 v -130.21 c 0,-29.468 -23.986,-53.454 -53.454,-53.454 H 0 c -29.468,0 -53.454,23.986 -53.454,53.454 v 130.21 C -53.454,-23.986 -29.468,0 0,0 m 65.105,-67.161 h 0.685 c 28.098,0 51.398,-23.3 51.398,-51.398 0,-28.783 -23.3,-52.084 -51.398,-52.084 h -0.685 c -28.098,0 -51.399,23.301 -51.399,52.084 0,28.098 23.301,51.398 51.399,51.398 m 0,26.728 h 0.685 c 42.489,0 78.126,-35.636 78.126,-78.126 0,-43.175 -35.637,-78.125 -78.126,-78.125 h -0.685 c -42.49,0 -77.441,34.95 -77.441,78.125 0,42.49 34.951,78.126 77.441,78.126 m 77.44,15.076 v 0 c 8.909,0 16.448,-7.538 16.448,-16.447 0,-8.909 -7.539,-16.447 -16.448,-16.447 -9.594,0 -16.447,7.538 -16.447,16.447 0,8.909 6.853,16.447 16.447,16.447 M -0.685,24.671 H 131.58 c 42.489,0 77.44,-34.951 77.44,-77.44 v -131.58 c 0,-42.489 -34.951,-77.44 -77.44,-77.44 H -0.685 c -42.489,0 -77.441,34.951 -77.441,77.44 v 131.58 c 0,42.489 34.952,77.44 77.441,77.44"
                                        transform="translate(1060.3845,565.3926)"
                                    />
                                    </g>
                                </g>
                            </svg>            
                        </a>
                        <a href="https://www.tiktok.com/@motoworld.pe?_r=1&_t=ZS-996dDIBvCpY">
                            <svg width="30px" height="30px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450">
                                <g transform="translate(0 450) scale(1 -1)">
                                    <g transform="translate(-1548.794,-221.9863)">
                                    <path
                                        fill="#000000"
                                        fill-rule="evenodd"
                                        d="m 0,0 h -306.423 c -39.483,0 -71.788,32.305 -71.788,71.789 v 306.422 c 0,39.484 32.305,71.789 71.788,71.789 H 0 c 39.484,0 71.789,-32.305 71.789,-71.789 V 71.789 C 71.789,32.305 39.484,0 0,0"
                                        transform="translate(1927.005,221.9863)"
                                    />

                                    <path
                                        fill="#ffffff"
                                        fill-rule="evenodd"
                                        d="m 0,0 c -16.333,10.051 -28.897,28.898 -32.039,47.743 -0.628,4.398 -1.256,8.167 -1.256,12.564 h -49.628 v -116.845 -81.038 c 0,-23.244 -18.846,-42.09 -42.089,-42.09 -7.539,0 -13.821,1.885 -20.103,5.026 -13.192,6.91 -22.615,21.359 -22.615,37.064 0,23.243 18.846,42.089 42.718,42.089 4.397,0 8.166,-0.628 12.564,-1.884 v 38.948 11.308 c -4.398,0.628 -8.167,1.256 -12.564,1.256 -50.885,0 -92.346,-40.833 -92.346,-91.717 0,-30.782 15.705,-58.423 39.577,-74.756 15.077,-10.052 33.294,-16.333 52.769,-16.333 50.884,0 91.717,40.833 91.717,91.089 v 103.025 c 18.846,-16.962 43.974,-25.128 69.103,-23.243 v 37.063 12.564 C 23.243,-10.679 10.679,-6.91 0,0"
                                        transform="translate(1863.6635,532.9338)"
                                    />
                                    </g>
                                </g>
                            </svg>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

{{-- Mapa --}}
<section class="w-full bg-neutral-200" aria-label="Ubicación Motoworld">
    <div class="relative w-full aspect-[21/9] min-h-[280px] max-h-[480px]">
        <iframe
            title="Mapa Motoworld — Av. Militar 2134, Lince"
            src="{{ $mapEmbedUrl }}"
            class="absolute inset-0 h-full w-full border-0"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
</section>
@endsection

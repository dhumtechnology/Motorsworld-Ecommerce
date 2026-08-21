@extends('layouts.shop')

@section('title', 'Ayuda — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/home/portadas/NUESTROS SERVICIOS.jpg'))
        ? asset('images/home/portadas/NUESTROS SERVICIOS.jpg')
        : asset('images/services/banner-servicios.png');

    $contactChannels = [
        [
            'title' => 'Consultas generales y pedidos',
            'description' => 'Dudas de carácter general o relacionadas con pedidos realizados o que deseas realizar.',
            'email' => 'workshop@motoworld.pe',
            'accent' => 'bg-orange-600',
        ],
        [
            'title' => 'Repuestos y pedidos',
            'description' => 'Dudas sobre repuestos, pedidos realizados o que deseas realizar.',
            'email' => 'info@motoworld.pe',
            'accent' => 'bg-neutral-900',
        ],
    ];

    $schedule = [
        ['label' => 'Lunes a viernes', 'hours' => '9:00 a 13:00 h · 14:00 a 18:30 h'],
        ['label' => 'Sábados', 'hours' => '9:00 a 14:00 h'],
    ];
@endphp

{{-- Hero --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[440px] w-full">
        <img
            src="{{ $banner }}"
            alt="Ayuda Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            loading="eager"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-black/25"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Atención al cliente</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Ayuda
            </h1>
            <p class="mt-2 max-w-2xl text-sm md:text-base text-white/85">
                ¿Necesitas ayuda? Consulta a nuestros expertos por correo, WhatsApp o teléfono.
            </p>
        </div>
    </div>
</section>

{{-- Intro --}}
<section class="bg-neutral-50 border-b border-neutral-100">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-10 md:py-12 text-center">
        <p class="text-sm md:text-base leading-relaxed text-neutral-600">
            Estamos aquí para resolver tus dudas sobre pedidos, repuestos y cualquier consulta relacionada con Moto World.
            Elige el canal que prefieras y te responderemos lo antes posible.
        </p>
    </div>
</section>

{{-- Canales --}}
<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Escríbenos</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Correo electrónico
            </h2>
        </div>

        <div class="mx-auto grid max-w-4xl grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($contactChannels as $channel)
                <article class="overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm transition-shadow hover:shadow-md">
                    <div class="h-1.5 {{ $channel['accent'] }}"></div>
                    <div class="p-6 md:p-8">
                        <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                            {{ $channel['title'] }}
                        </h3>
                        <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                            {{ $channel['description'] }}
                        </p>
                        <a
                            href="mailto:{{ $channel['email'] }}"
                            class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-orange-600 hover:text-orange-500 transition-colors"
                        >
                            <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ $channel['email'] }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- WhatsApp, teléfono y horario --}}
<section class="bg-neutral-900 text-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-7">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Respuesta rápida</p>
                <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] font-title">
                    WhatsApp y teléfono
                </h2>
                <p class="mt-4 max-w-xl text-sm md:text-base leading-relaxed text-neutral-300">
                    También puedes escribirnos por WhatsApp o llamarnos directamente. Nuestro equipo estará encantado de ayudarte.
                </p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                    <a
                        href="https://wa.me/{{ $contact['whatsapp'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-3 rounded-xl bg-[#25D366] px-6 py-4 text-sm font-bold uppercase tracking-wider text-white hover:bg-[#20bd5a] transition-colors"
                    >
                        <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                        </svg>
                        +51 {{ $contact['mobile'] }}
                    </a>

                    <a
                        href="tel:{{ $contact['phone_tel'] }}"
                        class="inline-flex items-center justify-center gap-3 rounded-xl border border-white/20 bg-white/5 px-6 py-4 text-sm font-bold uppercase tracking-wider text-white hover:border-orange-500 hover:bg-white/10 transition-colors"
                    >
                        <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        Tel: {{ $contact['phone'] }}
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-2xl border border-white/10 bg-black/20 p-6 md:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Horario de atención</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide font-title">
                        Tienda
                    </h3>
                    <ul class="mt-5 space-y-4">
                        @foreach ($schedule as $item)
                            <li class="flex flex-col gap-1 border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <span class="text-sm font-bold text-white">{{ $item['label'] }}</span>
                                <span class="text-sm text-neutral-300">{{ $item['hours'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-5 text-xs leading-relaxed text-neutral-400">
                        El horario de atención de nuestra tienda es de lunes a viernes de 9:00 a 13:00 h y de 14:00 a 18:30 h. Sábados, de 9:00 a 14:00 h.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-white border-t border-neutral-100">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-12 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                ¿Prefieres usar el formulario?
            </h2>
            <p class="mt-1 text-sm text-neutral-500">
                Déjanos tu mensaje y te contactaremos a la brevedad.
            </p>
        </div>
        <a
            href="{{ route('shop.contact') }}"
            class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
        >
            Ir a contacto
        </a>
    </div>
</section>
@endsection

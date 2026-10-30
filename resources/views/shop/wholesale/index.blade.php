{{--
    Ventas al por mayor
    Portada: public/images/home/portadas/REPUESTOS GENERALES .jpeg
--}}
@extends('layouts.shop')

@section('title', 'Ventas al por mayor — '.config('app.name'))

@section('content')
@php
    $outlineButton = 'inline-flex items-center justify-center rounded-xl border border-orange-600 bg-white px-6 py-3 text-sm font-bold uppercase tracking-wider text-orange-600 transition-colors hover:bg-orange-600 hover:text-white';

    $audiences = [
        [
            'title' => 'Talleres y mecánicos',
            'body' => 'Abastece tu taller con repuestos y consumibles de las marcas que trabajamos, con precios pensados para volumen.',
        ],
        [
            'title' => 'Tiendas y distribuidores',
            'body' => 'Arma tu inventario de accesorios, recambios y productos para motociclistas con un catálogo amplio y reposición continua.',
        ],
        [
            'title' => 'Flotas y empresas',
            'body' => 'Atendemos compras corporativas para flotas, courier y equipos que necesitan recambios o servicio técnico de forma recurrente.',
        ],
    ];

    $benefits = [
        [
            'title' => 'Precios de mayorista',
            'body' => 'Cotizamos según volumen, frecuencia de compra y tipo de producto para que tu margen sea competitivo.',
        ],
        [
            'title' => 'Asesoría especializada',
            'body' => 'Un asesor te ayuda a identificar referencias, compatibilidad por modelo y alternativas equivalentes.',
        ],
        [
            'title' => 'Despacho a Lima y provincias',
            'body' => 'Coordinamos envíos según nuestras tarifas vigentes o recojo en tienda en Av. Militar 2134, Lince.',
        ],
        [
            'title' => 'Marcas de respaldo',
            'body' => 'Trabajamos con representación nacional e internacional para que tu negocio ofrezca productos confiables.',
        ],
    ];

    $steps = [
        [
            'title' => 'Escríbenos',
            'body' => 'Cuéntanos qué necesitas: rubro, marcas, cantidades estimadas y si eres taller, tienda o empresa.',
        ],
        [
            'title' => 'Evaluamos tu pedido',
            'body' => 'Revisamos stock, plazos y condiciones. Si aplica, te pedimos RUC y datos de facturación.',
        ],
        [
            'title' => 'Recibes tu cotización',
            'body' => 'Te enviamos precios, disponibilidad y opciones de entrega o recojo en tienda.',
        ],
        [
            'title' => 'Confirmas y despachamos',
            'body' => 'Con el pago o acuerdo confirmado, preparamos el pedido y te avisamos cuando esté en camino o listo para recoger.',
        ],
    ];

    $categories = [
        [
            'title' => 'Repuestos',
            'body' => 'Recambios y partes para mantenimiento y reparación.',
            'href' => route('shop.catalog', ['section' => 'accesorios']),
        ],
        [
            'title' => 'Accesorios',
            'body' => 'Equipamiento y complementos para el motociclista.',
            'href' => route('shop.catalog', ['section' => 'accesorios']),
        ],
        [
            'title' => 'Motocicletas',
            'body' => 'Consulta disponibilidad y condiciones para compras de volumen.',
            'href' => route('shop.catalog', ['section' => 'motos']),
        ],
    ];
@endphp

<x-shop.page-header
    page="wholesale"
    eyebrow="Canal empresas"
    title="Ventas al por mayor"
    subtitle="Repuestos, accesorios y motocicletas para talleres, tiendas y empresas en todo el Perú."
    alt="Ventas al por mayor Motoworld"
    overlay="from-black/80 via-black/35 to-black/25"
/>

<nav class="sticky top-16 z-30 border-b border-neutral-200 bg-white/95 backdrop-blur sm:top-[4.25rem] lg:top-20" aria-label="Secciones de ventas al por mayor">
    <div class="mx-auto max-w-[95%] px-4 md:px-8">
        <ul class="flex gap-2 overflow-x-auto py-3 text-xs font-bold uppercase tracking-wider scrollbar-none">
            <li class="shrink-0"><a href="#para-quien" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Para quién</a></li>
            <li class="shrink-0"><a href="#beneficios" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Beneficios</a></li>
            <li class="shrink-0"><a href="#como-funciona" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Cómo funciona</a></li>
            <li class="shrink-0"><a href="#catalogo" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Catálogo</a></li>
            <li class="shrink-0"><a href="#contacto" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Contacto</a></li>
        </ul>
    </div>
</nav>

<section class="bg-neutral-50 border-b border-neutral-100">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16 text-center">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">Moto World Enterprise</p>
        <h2 class="mt-3 text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Abastece tu negocio con nosotros
        </h2>
        <p class="mt-5 text-sm md:text-base leading-relaxed text-neutral-600">
            Si compras con regularidad o en volumen, te armamos una cotización mayorista. Atendemos desde nuestra tienda en
            <strong class="text-neutral-900">{{ $contact['address'] }}</strong>
            y despachamos a Lima y provincias.
        </p>
    </div>
</section>

<section id="para-quien" class="bg-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Perfiles</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                ¿Para quién es este canal?
            </h2>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($audiences as $item)
                <article class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 shadow-sm">
                    <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                        {{ $item['title'] }}
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                        {{ $item['body'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="beneficios" class="bg-neutral-100 border-y border-neutral-200 scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Por qué cotizar con nosotros</p>
            <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                Beneficios mayoristas
            </h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($benefits as $item)
                <article class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                        {{ $item['title'] }}
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                        {{ $item['body'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="como-funciona" class="bg-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Proceso</p>
            <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                Cómo solicitar una cotización
            </h2>
        </div>

        <ol class="grid gap-4 lg:grid-cols-4">
            @foreach ($steps as $index => $step)
                <li class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-600 text-sm font-black text-white">
                        {{ $index + 1 }}
                    </span>
                    <h3 class="mt-4 text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                        {{ $step['title'] }}
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                        {{ $step['body'] }}
                    </p>
                </li>
            @endforeach
        </ol>

        <div class="mt-8 rounded-2xl border border-orange-200 bg-orange-50 p-6 md:p-8">
            <h3 class="text-sm font-black uppercase tracking-wider text-neutral-900">Ten a la mano</h3>
            <ul class="mt-4 space-y-3">
                @foreach ([
                    'Razón social, RUC y datos de contacto de la empresa o taller.',
                    'Lista de productos, referencias o modelos de moto que necesitas.',
                    'Cantidades estimadas y si el pedido es único o recurrente.',
                    'Preferencia de recojo en tienda o envío a Lima / provincias.',
                ] as $item)
                    <li class="flex gap-3 text-sm leading-relaxed text-neutral-700">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500" aria-hidden="true"></span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

<section id="catalogo" class="bg-neutral-50 border-y border-neutral-200 scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Líneas de producto</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Qué puedes cotizar
            </h2>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($categories as $item)
                <article class="flex h-full flex-col rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                        {{ $item['title'] }}
                    </h3>
                    <p class="mt-3 flex-1 text-sm leading-relaxed text-neutral-600">
                        {{ $item['body'] }}
                    </p>
                    <a href="{{ $item['href'] }}" class="mt-5 {{ $outlineButton }}">
                        Ver catálogo
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="contacto" class="bg-neutral-900 text-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-7">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Hablemos</p>
                <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] font-title">
                    Solicita tu cotización mayorista
                </h2>
                <p class="mt-4 max-w-xl text-sm md:text-base leading-relaxed text-neutral-300">
                    El equipo comercial te responde con disponibilidad, precios y plazos. Para pedidos y repuestos usa
                    <a href="mailto:info@motoworld.pe" class="font-semibold text-white hover:text-orange-400">info@motoworld.pe</a>.
                    También puedes escribirnos por WhatsApp o visitar la tienda.
                </p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                    <a
                        href="https://wa.me/{{ $contact['whatsapp'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-3 rounded-xl bg-[#25D366] px-6 py-4 text-sm font-bold uppercase tracking-wider text-white hover:bg-[#20bd5a] transition-colors"
                    >
                        WhatsApp {{ $contact['mobile'] }}
                    </a>
                    <a href="mailto:info@motoworld.pe" class="inline-flex items-center justify-center gap-3 rounded-xl border border-white/20 bg-white/5 px-6 py-4 text-sm font-bold uppercase tracking-wider text-white hover:border-orange-500 hover:bg-white/10 transition-colors">
                        info@motoworld.pe
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-2xl border border-white/10 bg-black/20 p-6 md:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Horario de atención</p>
                    <h3 class="mt-2 text-lg font-black uppercase tracking-wide font-title">Tienda Lince</h3>
                    <ul class="mt-5 space-y-4">
                        <li class="flex flex-col gap-1 border-b border-white/10 pb-4">
                            <span class="text-sm font-bold text-white">Lunes a viernes</span>
                            <span class="text-sm text-neutral-300">9:00 a 13:00 h · 14:00 a 18:30 h</span>
                        </li>
                        <li class="flex flex-col gap-1">
                            <span class="text-sm font-bold text-white">Sábados</span>
                            <span class="text-sm text-neutral-300">9:00 a 14:00 h</span>
                        </li>
                    </ul>
                    <p class="mt-5 text-sm text-neutral-300">{{ $contact['address'] }}</p>
                    <p class="mt-2 text-sm text-neutral-400">Tel. {{ $contact['phone'] }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border-t border-neutral-100">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-12 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                ¿Prefieres el formulario de contacto?
            </h2>
            <p class="mt-1 text-sm text-neutral-500">
                Indica que tu consulta es de ventas al por mayor y te responderemos a la brevedad.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('shop.contact') }}" class="{{ $outlineButton }}">
                Contáctanos
            </a>
            <a href="{{ route('shop.catalog') }}" class="{{ $outlineButton }}">
                Ver tienda
            </a>
        </div>
    </div>
</section>
@endsection

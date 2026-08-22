@extends('layouts.shop')

@section('title', 'Envíos y devoluciones — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/home/portadas/Enllantado.jpg'))
        ? asset('images/home/portadas/Enllantado.jpg')
        : asset('images/services/banner-servicios.png');

    $deliveryImage = file_exists(public_path('images/home/portadas/NUESTROS SERVICIOS.jpg'))
        ? asset('images/home/portadas/NUESTROS SERVICIOS.jpg')
        : asset('images/services/banner-servicios.png');

    $pickupImage = file_exists(public_path('images/home/portadas/Tienda Royal Enfield.jpeg'))
        ? asset('images/home/portadas/Tienda Royal Enfield.jpeg')
        : asset('images/home/banner-hero.png');

    $returnsImage = file_exists(public_path('images/home/portadas/REPUESTOS GENERALES .jpeg'))
        ? asset('images/home/portadas/REPUESTOS GENERALES .jpeg')
        : asset('images/home/need-repuestos.png');

    $zones = [
        [
            'label' => 'Zona Cercana',
            'price' => 'S/ 12',
            'areas' => 'Miraflores, San Isidro, Barranco, Surquillo, San Borja, Santiago de Surco, Magdalena, Lince, Jesús María, San Miguel, La Victoria',
        ],
        [
            'label' => 'Zona Intermedia',
            'price' => 'S/ 18',
            'areas' => 'Lima Cercado, Breña, San Luis, Pueblo Libre, Rímac, Chorrillos, La Molina, Ate, Santa Anita, El Agustino, Callao',
        ],
        [
            'label' => 'Zona Lejana',
            'price' => 'S/ 25',
            'areas' => 'Los Olivos, San Martín de Porres, San Juan de Lurigancho, Villa El Salvador, Villa María del Triunfo, Lurín, Lurigancho',
        ],
        [
            'label' => 'Provincias',
            'price' => 'S/ 35',
            'areas' => 'Envío vía Shalom u Olva a nivel nacional.',
        ],
    ];

    $returnExclusions = [
        'Si se modifican, alteran o sustituyen algunos de los datos de la misma o del ticket o factura de compra.',
        'Si se manipula el número identificativo, como el propio artículo, sin conocimiento de MOTO WORLD ENTERPRISE, S.A.C.',
        'Si carece de ticket o factura de compra.',
    ];
@endphp

{{-- Hero --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[440px] w-full">
        <img
            src="{{ $banner }}"
            alt="Envíos Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            loading="eager"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-black/25"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Información para clientes</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Envíos y devoluciones
            </h1>
            <p class="mt-2 max-w-2xl text-sm md:text-base text-white/85">
                Tarifas, plazos de entrega y condiciones para devolver tu compra con MOTO WORLD ENTERPRISE, S.A.C.
            </p>
        </div>
    </div>
</section>

{{-- Navegación rápida --}}
<nav class="sticky top-16 z-30 border-b border-neutral-200 bg-white/95 backdrop-blur sm:top-[4.25rem] lg:top-20" aria-label="Secciones de envíos y devoluciones">
    <div class="mx-auto max-w-[95%] px-4 md:px-8">
        <ul class="flex gap-2 overflow-x-auto py-3 text-xs font-bold uppercase tracking-wider scrollbar-none">
            <li class="shrink-0"><a href="#tarifas" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Tarifas</a></li>
            <li class="shrink-0"><a href="#entrega" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Entrega</a></li>
            <li class="shrink-0"><a href="#plazos" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Plazos</a></li>
            <li class="shrink-0"><a href="#devoluciones" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Devoluciones</a></li>
        </ul>
    </div>
</nav>

{{-- Tarifas --}}
<section id="tarifas" class="bg-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Gastos y formas de envío</p>
            <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                Zonas y tarifas
            </h2>
            <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-600">
                MOTO WORLD ENTERPRISE, S.A.C. pone a disposición de sus clientes diferentes métodos y tarifas de envío.
            </p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($zones as $zone)
                <article class="flex h-full flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 shadow-sm transition-shadow hover:shadow-md">
                    <div class="bg-orange-600 px-5 py-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-orange-100">{{ $zone['label'] }}</p>
                        <p class="mt-1 text-2xl font-black text-white">{{ $zone['price'] }}</p>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <p class="text-sm leading-relaxed text-neutral-600">{{ $zone['areas'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Formas de entrega --}}
<section id="entrega" class="bg-neutral-100 border-y border-neutral-200 scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-6">
                <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <img
                        src="{{ $deliveryImage }}"
                        alt="Entrega de pedidos Motoworld"
                        class="aspect-[4/3] w-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>
            <div class="lg:col-span-6">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Formas de entrega</p>
                <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                    Despachos en Lima y provincias
                </h2>
                <div class="mt-5 space-y-4 text-sm md:text-base leading-relaxed text-neutral-600">
                    <p>
                        Realizamos despachos para Lima Metropolitana y provincias de lunes a viernes de 09:00 a.m. a 06:00 p.m.
                    </p>
                    <p>
                        La entrega de la mercancía en Lima Metropolitana se realizará en el domicilio designado por el remitente, salvo por ausencia del destinatario o que, por el peso, volumen o naturaleza del inmueble, no sea posible la entrega. En este caso, y con previo aviso, se efectuará dicha entrega a puerta de calle o en el centro de servicio de destino.
                    </p>
                    <p>
                        En caso de ausencia del destinatario, se dejará nota de aviso de intento de entrega, así como la forma de acordar la misma. Será necesario, para proceder a la entrega de la mercancía, la firma de la boleta de entrega por el destinatario.
                    </p>
                    <p>
                        MOTO WORLD ENTERPRISE, S.A.C. no garantiza las entregas en horas específicas y concretas. Si el comprador señala una fecha concreta de entrega, o dentro de ella, muestra preferencia por una franja horaria determinada, se entenderá que lo hace con carácter orientativo. Comunicaremos esta circunstancia al transportista para que, en la medida de lo posible, se ajuste a esta indicación, sin que asumamos ninguna otra obligación o responsabilidad.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-1 items-stretch gap-6 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <div class="h-full overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <img
                        src="{{ $pickupImage }}"
                        alt="Recojo en tienda Motoworld"
                        class="aspect-[16/10] w-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>
            <div class="lg:col-span-7 flex">
                <div class="flex h-full w-full flex-col justify-center rounded-2xl border border-orange-200 bg-orange-50 p-6 md:p-8">
                    <h3 class="text-lg font-black uppercase tracking-wide text-neutral-900 font-title">
                        Recojo en tienda
                    </h3>
                    <p class="mt-3 text-sm md:text-base leading-relaxed text-neutral-700">
                        Si lo deseas, puedes recoger tu pedido en nuestra tienda de
                        <strong class="text-neutral-900">Av. Militar 2134, Lince</strong>.
                        Te informaremos por correo electrónico cuando tu pedido esté listo, indicándote días y horarios de recogida.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Plazos --}}
<section id="plazos" class="bg-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Tiempos de entrega</p>
            <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                Plazos de entrega
            </h2>
            <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-600">
                Despachamos todos los pedidos lo antes posible para optimizar tu experiencia. Los tiempos máximos indicados en cada tarifa son referenciales. Te notificaremos cuando tu pedido esté en camino por correo electrónico y por WhatsApp si registraste tu número al realizar tu compra.
            </p>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-3">
            <article class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-600 text-sm font-black text-white">1</span>
                <h3 class="mt-4 text-base font-black uppercase tracking-wide text-neutral-900 font-title">Stock en tienda</h3>
                <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                    Pedidos con pago confirmado antes de las 12:00 p.m., de lunes a viernes (no festivos), con mercancía en stock de tienda, salen de nuestro almacén ese mismo día. A partir de esa hora, la entrega se efectuará en un plazo de 3 a 5 días.
                </p>
            </article>
            <article class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-600 text-sm font-black text-white">2</span>
                <h3 class="mt-4 text-base font-black uppercase tracking-wide text-neutral-900 font-title">Stock de almacén</h3>
                <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                    Si el producto aparece en stock pero no está en tienda física, el stock es de almacén. Si el pedido se realiza antes de las 12:00 p.m. podemos solicitarlo ese mismo día; llegaría a tienda en 2 a 5 días laborables y luego se envía (24 horas adicionales). Plazo total estimado: 5 a 10 días laborables.
                </p>
            </article>
            <article class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-600 text-sm font-black text-white">3</span>
                <h3 class="mt-4 text-base font-black uppercase tracking-wide text-neutral-900 font-title">Situaciones especiales</h3>
                <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                    Los plazos se entienden sin perjuicio de retrasos por fuerza mayor o caso fortuito. Si un artículo no está disponible, te informaremos por teléfono y/o e-mail. En casos excepcionales de inventario, la salida puede demorar 24 a 48 horas adicionales.
                </p>
            </article>
        </div>

        <p class="mt-6 rounded-xl border border-neutral-200 bg-neutral-50 px-5 py-4 text-sm leading-relaxed text-neutral-600">
            La prestación de todos los servicios se efectuará en días laborables, de lunes a viernes antes de las 18:00.
        </p>
    </div>
</section>

{{-- Devoluciones --}}
<section id="devoluciones" class="bg-neutral-900 text-white scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-7">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Política de devolución</p>
                <h2 class="mt-2 text-xl md:text-3xl font-black uppercase tracking-[0.08em] text-white font-title">
                    Devolución de productos
                </h2>
                <div class="mt-5 space-y-4 text-sm md:text-base leading-relaxed text-neutral-300">
                    <p>
                        Para realizar la devolución, el producto deberá estar en perfectas condiciones, en su embalaje original y sin que haya sido manipulado o usado por el cliente, acompañado de ticket o factura de compra. En caso contrario, MOTO WORLD ENTERPRISE, S.A.C. se reserva el derecho de rechazar la devolución.
                    </p>
                    <p>
                        Se admitirá la devolución de productos defectuosos y envíos erróneos, siendo MOTO WORLD ENTERPRISE, S.A.C. quien se hará cargo de los gastos de envío siempre que el cliente comunique esta circunstancia en el plazo de 5 días contados desde la fecha de recepción del envío. Los gastos de envío sólo serán reembolsables en el caso de envío erróneo o producto defectuoso.
                    </p>
                    <p>
                        No se incluyen las deficiencias ocasionadas por negligencias, golpes, uso o manipulaciones indebidas, etc.
                    </p>
                    <p>
                        Una vez recibida la mercancía, y previa comprobación del estado de la misma, se procederá al reintegro de su importe conforme a la modalidad de pago realizada por el cliente en el plazo máximo de 48/72 horas. No se procederá al reintegro de los gastos de envío ocasionados.
                    </p>
                </div>

                <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5 md:p-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-white">La devolución no tendrá efecto si:</h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($returnExclusions as $item)
                            <li class="flex gap-3 text-sm leading-relaxed text-neutral-300">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500" aria-hidden="true"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a
                        href="{{ route('shop.contact') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
                    >
                        Solicitar devolución
                    </a>
                    <p class="text-sm text-neutral-400">
                        Escríbenos a
                        <a href="mailto:info@motoworld.pe" class="font-semibold text-white hover:text-orange-400 transition-colors">info@motoworld.pe</a>
                    </p>
                </div>
            </div>
            <div class="lg:col-span-5">
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-black/20">
                    <img
                        src="{{ $returnsImage }}"
                        alt="Devolución de productos Motoworld"
                        class="aspect-[4/5] w-full object-cover sm:aspect-[3/4]"
                        loading="lazy"
                    >
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
                ¿Tienes dudas sobre tu pedido?
            </h2>
            <p class="mt-1 text-sm text-neutral-500">
                Nuestro equipo puede ayudarte con envíos, plazos o devoluciones.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('shop.contact') }}"
                class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
            >
                Contáctanos
            </a>
            <a
                href="{{ route('shop.catalog', ['section' => 'accesorios']) }}"
                class="inline-flex items-center justify-center rounded-xl border border-neutral-200 px-6 py-3 text-sm font-bold uppercase tracking-wider text-neutral-800 hover:border-orange-500 hover:text-orange-600 transition-colors"
            >
                Ver tienda
            </a>
        </div>
    </div>
</section>
@endsection

@extends('layouts.shop')

@section('title', 'Formas de pago y promociones — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/home/portadas/REPUESTOS GENERALES .jpeg'))
        ? asset('images/home/portadas/REPUESTOS GENERALES .jpeg')
        : asset('images/services/banner-servicios.png');

    $paymentMethods = [
        [
            'title' => 'Transferencia bancaria',
            'body' => 'Recibirá un email con nuestro número de cuenta. Le informamos de que los productos no se suministrarán hasta después de la recepción del pago. Si escoges este sistema de pago, tu pedido quedará pendiente de recibir por nuestra parte la confirmación de ingreso por parte de la entidad bancaria. El plazo de entrega empieza a contar a partir de la fecha de recepción de la transferencia.',
        ],
        [
            'title' => 'PayPal',
            'body' => 'Con el sistema PayPal no deberá volver a introducir sus datos financieros en ningún otro sitio web. Nosotros los almacenamos y salvaguardamos para que cada vez que quiera pagar en Internet sólo deba introducir su cuenta de correo electrónico y su contraseña. Cuando compre con PayPal estará cubierto por nuestro programa de protección al comprador; es decir, si la compra no llega o lo hace en mal estado, o no coincide con la descripción, le reembolsaremos el dinero. La selección de este método de pago lleva aparejada una comisión del 4% sobre el importe del envío.',
        ],
        [
            'title' => 'Tarjeta de crédito o débito (Visa, Master Card)',
            'body' => 'Este modo de pago es totalmente seguro. MOTO WORLD ENTERPRISE, S.A.C. únicamente pasará al cobro el importe de los artículos disponibles, con sus correspondientes gastos de envío. Visa y Master Card han desarrollado un sistema para realizar de forma segura pagos en Internet. El sistema de Comercio Electrónico Seguro se basa en que el Emisor de la tarjeta (banco o caja de ahorros) identifique al titular de la misma antes de autorizar el pago por Internet. Una vez completada la identificación, el Emisor comunica a MOTO WORLD ENTERPRISE, S.A.C. que la compra la está realizando el titular de la tarjeta, de forma que éste pueda completar el proceso. Si la identificación no ha sido satisfactoria, el Emisor lo comunica a MOTO WORLD ENTERPRISE, S.A.C. para que proceda en consecuencia.',
        ],
    ];

    $installmentBenefits = [
        'Al instante y sin papeleos. Solo con tu DNI y tu tarjeta.',
        'En el momento de la compra se hace el primer pago con tarjeta, y el resto se automatiza en función de las mensualidades que hayas elegido.',
        'Puedes modificar el plan de pago o pagar la totalidad en cualquier momento sin costes adicionales. Y también puedes elegir o modificar el día del mes que quieres pagar.',
    ];
@endphp

{{-- Hero --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[440px] w-full">
        <img
            src="{{ $banner }}"
            alt="Formas de pago Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            loading="eager"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-black/25"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Información para clientes</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Formas de pago y promociones
            </h1>
            <p class="mt-2 max-w-2xl text-sm md:text-base text-white/85">
                Métodos de pago, fraccionamiento, impuestos y uso de vales de descuento en Moto World.
            </p>
        </div>
    </div>
</section>

{{-- Navegación rápida --}}
<nav class="sticky top-16 z-30 border-b border-neutral-200 bg-white/95 backdrop-blur sm:top-[4.25rem] lg:top-20" aria-label="Secciones de formas de pago">
    <div class="mx-auto max-w-[95%] px-4 md:px-8">
        <ul class="flex gap-2 overflow-x-auto py-3 text-xs font-bold uppercase tracking-wider scrollbar-none">
            <li class="shrink-0"><a href="#formas-de-pago" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Formas de pago</a></li>
            <li class="shrink-0"><a href="#fracciona" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Fracciona tu pago</a></li>
            <li class="shrink-0"><a href="#impuestos" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Impuestos</a></li>
            <li class="shrink-0"><a href="#descuentos" class="inline-flex rounded-full border border-neutral-200 px-4 py-2 text-neutral-700 hover:border-orange-500 hover:text-orange-600 transition-colors">Descuentos</a></li>
        </ul>
    </div>
</nav>

{{-- Formas de pago --}}
<section id="formas-de-pago" class="bg-neutral-50 border-b border-neutral-100 scroll-mt-36">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        <div class="mb-10 text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Métodos disponibles</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Formas de pago
            </h2>
        </div>

        <div class="mx-auto grid max-w-4xl grid-cols-1 gap-6">
            @foreach ($paymentMethods as $method)
                <article class="rounded-2xl border border-neutral-200 bg-white p-6 md:p-8 shadow-sm">
                    <h3 class="text-base md:text-lg font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                        {{ $method['title'] }}
                    </h3>
                    <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-700">
                        {{ $method['body'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Fracciona tu pago --}}
<section id="fracciona" class="bg-white border-b border-neutral-100 scroll-mt-36">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16">
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-6 md:p-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Financiamiento</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Fracciona tu pago
            </h2>
            <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-700">
                Elige dividir el importe de tu pedido en 3, 6 o 12 mensualidades, en el momento de la compra.
            </p>

            <ul class="mt-6 space-y-3">
                @foreach ($installmentBenefits as $benefit)
                    <li class="flex gap-3 text-sm md:text-base leading-relaxed text-neutral-700">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-orange-500" aria-hidden="true"></span>
                        <span>{{ $benefit }}</span>
                    </li>
                @endforeach
            </ul>

            <p class="mt-8 rounded-xl border border-neutral-200 bg-white p-4 text-sm leading-relaxed text-neutral-600">
                Cualquier otra fórmula de pago no contemplada deberá informarse previamente a través de nuestro teléfono:
                <a href="tel:{{ $contact['mobile_tel'] }}" class="font-bold text-orange-600 hover:text-orange-500 transition-colors">
                    +51 {{ $contact['mobile'] }}
                </a>
            </p>
        </div>
    </div>
</section>

{{-- Impuestos --}}
<section id="impuestos" class="bg-neutral-900 text-white scroll-mt-36">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16">
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-400">Legislación vigente</p>
        <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] font-title">
            Impuestos
        </h2>

        <div class="mt-6 space-y-4 text-sm md:text-base leading-relaxed text-neutral-300">
            <p>
                El sistema de compra se somete a la legislación vigente en Perú. Los precios que figuran en la página de MOTO WORLD ENTERPRISE, S.A.C. incluyen el IGV. Los precios aplicables son los indicados en la página web en la fecha del pedido.
            </p>
            <p>
                MOTO WORLD ENTERPRISE, S.A.C. informará del precio total mediante correo una vez realizado el pedido, con los impuestos correspondientes y gastos de envío. MOTO WORLD ENTERPRISE, S.A.C. se reserva el derecho en cualquier momento a modificar los precios sin previo aviso.
            </p>
            <p>
                Los precios y condiciones de venta son exclusivos para el territorio nacional peruano. MOTO WORLD ENTERPRISE, S.A.C. no realiza pedidos fuera del territorio especificado.
            </p>
        </div>
    </div>
</section>

{{-- Vales y códigos --}}
<section id="descuentos" class="bg-white scroll-mt-36">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16">
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 md:p-10 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-600">Promociones</p>
            <h2 class="mt-2 text-xl md:text-2xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Vales y códigos de descuento
            </h2>
            <p class="mt-4 text-sm md:text-base leading-relaxed text-neutral-700">
                Cuando tengas los artículos que vas a comprar en la cesta de la compra te encontrarás la casilla «Vale de descuento» en la que podrás introducir el código de descuento o de promoción. Una vez introducido se reflejará el desglose de precios con un nuevo campo con el concepto del vale y el descuento realizado en el precio total.
            </p>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-neutral-50 border-t border-neutral-100">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-12 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg md:text-xl font-black uppercase tracking-wide text-neutral-900 font-title">
                ¿Listo para comprar?
            </h2>
            <p class="mt-1 text-sm text-neutral-500">
                Explora nuestro catálogo o contáctanos si tienes dudas sobre el pago.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('shop.catalog', ['section' => 'accesorios']) }}"
                class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
            >
                Ver tienda
            </a>
            <a
                href="{{ route('shop.help') }}"
                class="inline-flex items-center justify-center rounded-xl border border-neutral-200 px-6 py-3 text-sm font-bold uppercase tracking-wider text-neutral-800 hover:border-orange-500 hover:text-orange-600 transition-colors"
            >
                Ayuda
            </a>
        </div>
    </div>
</section>
@endsection

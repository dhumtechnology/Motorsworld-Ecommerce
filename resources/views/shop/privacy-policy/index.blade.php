@extends('layouts.shop')

@section('title', 'Política de privacidad — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/home/portadas/Enllantado.jpg'))
        ? asset('images/home/portadas/Enllantado.jpg')
        : asset('images/services/banner-servicios.png');

    $directCollectionItems = [
        'Cuando compras algo en nuestra tienda, como parte del proceso, recopilaremos tu nombre, tu dirección de correo electrónico y algunos detalles de la transacción, como los correspondientes datos de facturación o entrega.',
        'Si solicitas un reembolso mediante transferencia bancaria directa, es posible que recopilemos los datos bancarios necesarios para procesar el pago. Estos datos se almacenarán de forma segura dentro de nuestro sistema de asistencia, de acuerdo con nuestras políticas de privacidad y conservación de datos, para garantizar el procesamiento preciso y el mantenimiento de registros de los reembolsos. Al proporcionar estos datos, nos das tu consentimiento para su almacenamiento seguro para tramitar el reembolso.',
        'Después de completar una compra, es posible que te ofrezcamos la opción de participar en una encuesta voluntaria. Esta encuesta recopila información anónima para ayudarnos a comprender mejor a nuestros clientes, como dónde escuchaste por primera vez sobre Moto World, cómo piensas utilizar los productos que compraste y si conoces a alguno de nuestros embajadores de marca. La información recopilada a través de estas encuestas es completamente anónima y no se puede utilizar para identificarte personalmente.',
        'Si creas una cuenta en el sitio, obtendremos tu nombre, detalles de contacto e información de inicio de sesión (nombre de usuario y contraseña).',
        'Obtendremos tu dirección de correo electrónico si te registras para recibir ofertas o códigos de descuento en el sitio.',
        'El usuario es responsable de mantener la confidencialidad de sus credenciales de acceso. Cualquier actividad realizada desde su cuenta será responsabilidad exclusiva del titular. En caso de detectar un uso no autorizado, deberá notificarlo de inmediato a info@motoworld.pe.',
        'Si escribes una reseña sobre el sitio después de haber comprado uno de los productos de Moto World.',
        'Si te pones en contacto con nosotros para solicitar información sobre los productos que ofrecemos o si te pones en contacto con nuestro equipo de servicio al cliente (por ejemplo, a través de nuestro formulario de consulta o por correo electrónico), podemos mantener un registro de esa interacción para garantizarte un servicio de calidad al cliente.',
        'Si hablas con nosotros en las redes sociales o utilizas las integraciones de redes sociales en nuestros sitios, recopilaremos información sobre dicha interacción.',
    ];

    $automaticCollectionItems = [
        'Cuando navegas por nuestra tienda, recibimos automáticamente información sobre cómo usas el sitio, como la dirección del protocolo de Internet (IP) de tu ordenador, el tipo de navegador e información sobre el dispositivo. Recopilamos esta información para mejorar el funcionamiento del sitio.',
        'Recopilaremos información automáticamente utilizando cookies y otras tecnologías similares (por ejemplo, qué páginas has visitado y cuál es el contenido con el que has interactuado).',
    ];
@endphp

{{-- Hero --}}
<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[440px] w-full">
        <img
            src="{{ $banner }}"
            alt="Política de privacidad Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            loading="eager"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-black/25"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Información legal</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Política de privacidad
            </h1>
            <p class="mt-2 max-w-2xl text-sm md:text-base text-white/85">
                Cómo recopilamos, usamos y protegemos tus datos personales en Moto World Enterprise S.A.C.
            </p>
        </div>
    </div>
</section>

{{-- Contenido --}}
<section class="bg-neutral-50 border-b border-neutral-100">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-12 md:py-16">
        <div class="rounded-2xl border border-neutral-200 bg-white p-6 md:p-10 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">
                Ley 29733, Ley de Protección de Datos Personales
            </p>
            <h2 class="mt-4 text-lg md:text-xl font-black uppercase tracking-[0.1em] text-neutral-900 font-title">
                Artículo 18. Derecho de información del titular de datos personales
            </h2>

            <div class="mt-8 space-y-6 text-sm md:text-base leading-relaxed text-neutral-700">
                <h3 class="text-base md:text-lg font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                    Política de privacidad
                </h3>

                <p>
                    El propósito de esta Política de privacidad es informarte sobre los tipos de información que podemos recopilar sobre ti cuando visitas nuestro sitio web, cómo podemos usar esa información y si se la revelamos a alguien. Nuestro objetivo es ofrecerte una experiencia en línea satisfactoria y, al mismo tiempo, lograr que compres productos en línea sabiendo que tus datos están protegidos.
                </p>

                <p>
                    Moto World Enterprise S.A.C. se reserva el derecho de actualizar, modificar o reemplazar estos términos en cualquier momento. Las modificaciones serán publicadas en esta misma página y entrarán en vigencia desde su publicación. Se respetarán las condiciones vigentes al momento de una compra ya confirmada por el usuario. Se recomienda al usuario revisar esta sección periódicamente.
                </p>

                <div>
                    <p class="font-semibold text-neutral-900">
                        Recopilamos información personal cuando tú nos la proporcionas directamente a través del uso del sitio. Estos son algunos ejemplos:
                    </p>
                    <ul class="mt-4 list-disc space-y-3 pl-5 marker:text-orange-500">
                        @foreach ($directCollectionItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="font-semibold text-neutral-900">
                        También recopilaremos información sobre cómo usas el sitio e interactúas con este; estos son algunos ejemplos:
                    </p>
                    <ul class="mt-4 list-disc space-y-3 pl-5 marker:text-orange-500">
                        @foreach ($automaticCollectionItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>

                <p>
                    Si creas una cuenta o haces una compra, tendrás que darnos datos personales que sean exactos. Si no quieres proporcionarnos información personal, no crees una cuenta, no hagas ninguna compra y no nos proporciones datos personales de ningún otro modo.
                </p>

                <div class="border-t border-neutral-200 pt-8">
                    <h3 class="text-base md:text-lg font-black uppercase tracking-[0.08em] text-neutral-900 font-title">
                        Pagos
                    </h3>

                    <div class="mt-4 space-y-4">
                        <p>
                            Si eliges una pasarela de pagos directa para completar una compra, Culqi guarda los datos de tu tarjeta de crédito. Estos datos están cifrados de acuerdo con el estándar PCI-DSS (Payment Card Industry Data Security Standard). Los datos de la tarjeta de crédito solo se almacenan durante el tiempo que sea necesario para completar la transacción de tu compra. Tras completar ese proceso, los datos de tu tarjeta de crédito se eliminan.
                        </p>

                        <p>
                            Todas las pasarelas de pago directo se adhieren a los estándares establecidos por PCI-DSS, bajo la administración del PCI Security Standards Council, una iniciativa conjunta de empresas como Visa, MasterCard, American Express y Discover.
                        </p>

                        <p>
                            Los requisitos de PCI-DSS contribuyen a garantizar una gestión segura de la información de la tarjeta de crédito por parte de nuestra tienda y de sus proveedores de servicios.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

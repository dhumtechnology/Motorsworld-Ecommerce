<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="antialiased overflow-x-hidden">
    <header class="border-b border-gray-200">
        <div class="mx-auto px-10 py-4 flex max-w-full items-center justify-between bg-black">
            <div class="logo h-12 w-48 flex items-center">
                <x-logo href="{{ route('shop.home') }}" />
            </div>
            <div class="flex items-center gap-6">
                <nav>
                    <ul class="flex items-center gap-3">
                        <li>
                            <a href="{{ route('shop.home') }}" class="px-3 py-2 text-white hover:text-orange-500">HOME</a>
                        </li>
                        <li>
                            <a href="{{ route('shop.services.index') }}" class="px-3 py-2 text-white hover:text-orange-500">SERVICIOS</a>
                        </li>
                        <x-dropdown title="TIENDA">
                            <a
                                href="{{ route('shop.catalog', ['section' => 'motos']) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600"
                            >
                                Motos
                            </a>
                            <a
                                href="{{ route('shop.catalog', ['section' => 'accesorios']) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600"
                            >
                                Accesorios y más
                            </a>
                        </x-dropdown>
                        <li>
                            <a href="{{ route('shop.about') }}" class="px-3 py-2 text-white hover:text-orange-500">NOSOTROS</a>
                        </li>
                        <li>
                            <a href="{{ route('shop.blog.index') }}" class="px-3 py-2 text-white hover:text-orange-500">BLOG</a>
                        </li>
                        <li>
                            <a href="{{ route('shop.contact') }}" class="px-3 py-2 text-white hover:text-orange-500">CONTÁCTANOS</a>
                        </li>
                    </ul>
                </nav>
                <x-search
                    name="search"
                    placeholder="Producto, marca o categoría…"
                    value="{{ request('search') }}"
                    :action="route('shop.catalog')"
                />
                <div class="flex items-center gap-4">
                    <a href="{{ route('shop.cart.index') }}" data-cart-icon class="relative inline-flex items-center justify-center" title="Ver carrito" aria-label="Ver carrito">
                        <svg width="25" height="23" viewBox="0 0 25 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.33366 22C9.90896 22 10.3753 21.5523 10.3753 21C10.3753 20.4477 9.90896 20 9.33366 20C8.75836 20 8.29199 20.4477 8.29199 21C8.29199 21.5523 8.75836 22 9.33366 22Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20.7917 22C21.367 22 21.8333 21.5523 21.8333 21C21.8333 20.4477 21.367 20 20.7917 20C20.2164 20 19.75 20.4477 19.75 21C19.75 21.5523 20.2164 22 20.7917 22Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M1 1H5.16667L7.95833 14.39C8.05359 14.8504 8.31449 15.264 8.69536 15.5583C9.07623 15.8526 9.55281 16.009 10.0417 16H20.1667C20.6555 16.009 21.1321 15.8526 21.513 15.5583C21.8938 15.264 22.1547 14.8504 22.25 14.39L23.9167 6H6.20833" fill="#121212"/>
                            <path d="M1 1H5.16667L7.95833 14.39C8.05359 14.8504 8.31449 15.264 8.69536 15.5583C9.07623 15.8526 9.55281 16.009 10.0417 16H20.1667C20.6555 16.009 21.1321 15.8526 21.513 15.5583C21.8938 15.264 22.1547 14.8504 22.25 14.39L23.9167 6H6.20833" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @if (($cartItemCount ?? 0) > 0)
                            <span data-cart-badge class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-600 text-white text-[10px] font-black leading-[18px] text-center">
                                {{ $cartItemCount > 99 ? '99+' : $cartItemCount }}
                            </span>
                        @endif
                    </a>
                    <div>
                        @auth
                            <div
                                x-data="{ open: false }"
                                class="relative"
                            >
                                <button
                                    type="button"
                                    @click="open = !open"
                                    @click.away="open = false"
                                    class="inline-flex items-center justify-center text-white hover:text-orange-500 transition"
                                    title="Mi cuenta"
                                    aria-label="Mi cuenta"
                                    aria-haspopup="true"
                                    :aria-expanded="open.toString()"
                                >
                                    <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M8.125 15.0625H8.73633C9.893 15.5592 11.1613 15.8438 12.5 15.8438C13.8372 15.8438 15.1095 15.5595 16.2637 15.0625H16.875C19.9458 15.0625 22.4375 17.5542 22.4375 20.625V22.6562C22.4375 23.3979 21.8354 24 21.0938 24H3.90625C3.16459 24 2.5625 23.3979 2.5625 22.6562V20.625C2.5625 17.5542 5.05424 15.0625 8.125 15.0625ZM12.5 1C15.3999 1 17.75 3.35014 17.75 6.25C17.75 9.14986 15.3999 11.5 12.5 11.5C9.60014 11.5 7.25 9.14986 7.25 6.25C7.25 3.35014 9.60014 1 12.5 1Z" fill="black" stroke="white" stroke-width="2"/>
                                    </svg>
                                </button>

                                <div
                                    x-show="open"
                                    x-transition
                                    class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-100 z-50"
                                    style="display: none;"
                                >
                                    <div class="py-2">
                                        @if (auth()->user()?->hasRole('Administrador'))
                                            <a href="{{ route('admin.profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600">
                                                Mi perfil
                                            </a>
                                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600">
                                                Panel admin
                                            </a>
                                        @else
                                            <a href="{{ route('shop.account.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600">
                                                Mi perfil
                                            </a>
                                        @endif
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600">
                                                Cerrar sesión
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" title="Iniciar sesión" class="block">
                                <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8.125 15.0625H8.73633C9.893 15.5592 11.1613 15.8438 12.5 15.8438C13.8372 15.8438 15.1095 15.5595 16.2637 15.0625H16.875C19.9458 15.0625 22.4375 17.5542 22.4375 20.625V22.6562C22.4375 23.3979 21.8354 24 21.0938 24H3.90625C3.16459 24 2.5625 23.3979 2.5625 22.6562V20.625C2.5625 17.5542 5.05424 15.0625 8.125 15.0625ZM12.5 1C15.3999 1 17.75 3.35014 17.75 6.25C17.75 9.14986 15.3999 11.5 12.5 11.5C9.60014 11.5 7.25 9.14986 7.25 6.25C7.25 3.35014 9.60014 1 12.5 1Z" fill="black" stroke="white" stroke-width="2"/>
                                </svg>
                            </a>
                        @endauth
                    </div>
                    
                </div>
            </div>
        </div>
    </header>

    <main class="">
        @yield('content')
    </main>

    <footer class="text-white py-14 bg-black">
        <div class="mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 max-w-[95%] px-4 md:px-8">
            
            <div class="flex flex-col gap-3 md:col-span-3">
                <x-logo href="{{ route('shop.home') }}" size="lg" />
                <p class="text-gray-400 text-xs leading-relaxed max-w-xs font-medium">
                    Motos, repuestos, accesorios, servicio de mantenimiento y reparación para tu moto, todo en un solo lugar.  
                </p>    
                <div class="flex items-center gap-2 text-white font-bold mt-1 text-xs">
                    <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_76_362)">
                        <path d="M24.2866 17.6656L18.8179 15.3218C18.5843 15.2223 18.3246 15.2013 18.078 15.262C17.8315 15.3228 17.6113 15.462 17.4507 15.6587L15.0288 18.6177C11.2279 16.8256 8.16904 13.7668 6.37695 9.96587L9.33594 7.54399C9.53305 7.38368 9.67256 7.16351 9.73335 6.91682C9.79414 6.67013 9.7729 6.41035 9.67285 6.1768L7.3291 0.708053C7.21929 0.4563 7.02508 0.250751 6.77996 0.126852C6.53483 0.00295173 6.25416 -0.0315334 5.98633 0.0293424L0.908203 1.20122C0.649985 1.26085 0.419602 1.40624 0.254656 1.61366C0.0897096 1.82109 -5.94829e-05 2.07829 2.95713e-08 2.34331C2.95713e-08 14.8677 10.1514 24.9996 22.6562 24.9996C22.9213 24.9997 23.1787 24.91 23.3862 24.7451C23.5937 24.5801 23.7392 24.3497 23.7988 24.0914L24.9707 19.0132C25.0312 18.7441 24.996 18.4623 24.8711 18.2163C24.7463 17.9704 24.5396 17.7756 24.2866 17.6656Z" fill="#EDEDED"/>
                        </g>
                        <defs>
                        <clipPath id="clip0_76_362">
                        <rect width="25" height="25" fill="white"/>
                        </clipPath>
                        </defs>
                    </svg>
                    <span class="tracking-wider">+51 {{ config('shop.contact.mobile') }}</span>
                </div>
            </div>    

            <div class="md:col-span-2">
                <x-footer-column 
                    title="CONÓCENOS" 
                    :links="[
                        'NOSOTROS' => route('shop.about'),
                        'NUESTRO TRABAJO' => '#',
                        'POLÍTICAS DE CALIDAD' => route('shop.about').'#politicas-de-calidad',
                        'CONTÁCTANOS' => route('shop.contact'),
                    ]" 
                />
            </div>

            <div class="md:col-span-2">
                <x-footer-column 
                    title="NUESTROS SERVICIOS" 
                    :links="[
                        'REPARACIÓN' => '#',
                        'INSTALACIÓN DE REPUESTOS' => '#',
                        'MANTENIMIENTO' => '#',
                        'ASESORÍAS' => '#'
                    ]" 
                />
            </div>

            <div class="md:col-span-2">
                <x-footer-column 
                    title="NUESTROS PRODUCTOS" 
                    :links="[
                        'ACCESORIOS' => '#',
                        'REPUESTOS GENERALES' => '#',
                        'BATERÍAS' => '#',
                        'NEUMÁTICOS' => '#'
                    ]" 
                />
            </div>

            <div class="flex flex-col gap-3 md:col-span-3">
                <h3 class="text-white font-extrabold tracking-wider uppercase text-sm font-sans">
                    ¿Quejas o reclamos?
                </h3>

                <a
                    href="{{ route('shop.contact') }}"
                    class="inline-block w-fit"
                    title="Libro de reclamaciones"
                    aria-label="Libro de reclamaciones"
                >
                    <img
                        src="images/home/libro-de-reclamaciones.png"
                        alt="Libro de reclamaciones"
                        class="h-24 w-auto transition-transform hover:scale-105"
                        width="96"
                        height="112"
                    >
                </a>
            </div>

        </div>
    </footer>

    <a
        href="https://wa.me/{{ config('shop.contact.whatsapp') }}"
        target="_blank"
        rel="noopener noreferrer"
        id="whatsapp-float"
        aria-label="Chatear por WhatsApp"
        title="WhatsApp {{ config('shop.contact.mobile') }}"
        style="position:fixed;right:1.25rem;bottom:1.25rem;z-index:9999;display:flex;align-items:center;justify-content:center;width:3.5rem;height:3.5rem;border-radius:9999px;background:#25D366;color:#fff;box-shadow:0 10px 25px rgba(0,0,0,.28);text-decoration:none;"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
    <style>
        #whatsapp-float:hover { background:#1ebe57; transform:scale(1.06); }
        @media (min-width: 768px) {
            #whatsapp-float { right:2rem; bottom:2rem; }
        }
    </style>

    <script src="{{ asset('js/shop-cart.js') }}" defer></script>
</body>
</html>

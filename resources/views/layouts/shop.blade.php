<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200">
        <div class="mx-auto px-10 py-4 flex max-w-full items-center justify-between bg-[#252525]">
            <div class="logo">
                <x-logo href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" />
            </div>
            <div class="flex items-center gap-6">
                <nav>
                    <ul class="flex items-center gap-2">
                        <x-dropdown title="HOME">
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                Desarrollo Web
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                Aplicaciones Móviles
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                Consultoría
                            </a>
                        </x-dropdown>
                        <x-dropdown title="SERVICIO">
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                SERVICIO 1
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                SERVICIO 2
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                SERVICIO 3
                            </a>
                        </x-dropdown>
                        <x-dropdown title="TIENDA">
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                TIENDA 1
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                TIENDA 2
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                TIENDA 3
                            </a>
                        </x-dropdown>
                        <x-dropdown title="NOSOTROS">
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                OPCION 1
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                OPCION 2
                            </a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                                OPCION 3
                            </a>
                        </x-dropdown>   

                        <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" class="px-3 py-2 text-white hover:text-blue-600">CONTÁCTANOS</a></li>
                    </ul>
                </nav>
                <x-search name="search" placeholder="Buscar productos..." value="{{ request('search') }}" /> 
                <div class="flex items-center gap-4">
                    <div class= "color-white">
                        <svg width="25" height="23" viewBox="0 0 25 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.33366 22C9.90896 22 10.3753 21.5523 10.3753 21C10.3753 20.4477 9.90896 20 9.33366 20C8.75836 20 8.29199 20.4477 8.29199 21C8.29199 21.5523 8.75836 22 9.33366 22Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20.7917 22C21.367 22 21.8333 21.5523 21.8333 21C21.8333 20.4477 21.367 20 20.7917 20C20.2164 20 19.75 20.4477 19.75 21C19.75 21.5523 20.2164 22 20.7917 22Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M1 1H5.16667L7.95833 14.39C8.05359 14.8504 8.31449 15.264 8.69536 15.5583C9.07623 15.8526 9.55281 16.009 10.0417 16H20.1667C20.6555 16.009 21.1321 15.8526 21.513 15.5583C21.8938 15.264 22.1547 14.8504 22.25 14.39L23.9167 6H6.20833" fill="#121212"/>
                            <path d="M1 1H5.16667L7.95833 14.39C8.05359 14.8504 8.31449 15.264 8.69536 15.5583C9.07623 15.8526 9.55281 16.009 10.0417 16H20.1667C20.6555 16.009 21.1321 15.8526 21.513 15.5583C21.8938 15.264 22.1547 14.8504 22.25 14.39L23.9167 6H6.20833" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.125 15.0625H8.73633C9.893 15.5592 11.1613 15.8438 12.5 15.8438C13.8372 15.8438 15.1095 15.5595 16.2637 15.0625H16.875C19.9458 15.0625 22.4375 17.5542 22.4375 20.625V22.6562C22.4375 23.3979 21.8354 24 21.0938 24H3.90625C3.16459 24 2.5625 23.3979 2.5625 22.6562V20.625C2.5625 17.5542 5.05424 15.0625 8.125 15.0625ZM12.5 1C15.3999 1 17.75 3.35014 17.75 6.25C17.75 9.14986 15.3999 11.5 12.5 11.5C9.60014 11.5 7.25 9.14986 7.25 6.25C7.25 3.35014 9.60014 1 12.5 1Z" fill="black" stroke="white" stroke-width="2"/>
                        </svg>
                    </div>
                    
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
        @yield('content')
    </main>

    <footer class="text-white py-12 px-4 md:px-12 bg-[#252525]">
        <div mx-auto grid grid-cols-1 md:grid-cols-5 gap-8 max-w-[95%]>
            <div class="flex flex-col gap-4 ">
                <x-logo href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" />
                <p class="text-gray-300 text-sm">
                    Motos, repuestos, accesorios, servicio de mantenimiento y reparación para tu moto, todo en un solo lugar.  
                </p>    
                <div class="flex items-center gap-2">
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
                    <p>+51920883723</p>
                </div>
            </div>    
            <div class="flex gap-4 ">   
                <x-footer-column 
                        title="CONÓCENOS" 
                        :links="[
                            'NOSOTROS' => '#',
                            'NUESTRO TRABAJO' => '#',
                            'POLÍTICAS DE CALIDAD' => '#',
                            'CONTÁCTANOS' => '#'
                        ]" 
                />
                <x-footer-column 
                    title="NUESTROS SERVICIOS" 
                    :links="[
                        'REPARACIÓN' => '#',
                        'INSTALACIÓN DE REPUESTOS' => '#',
                        'MANTENIMIENTO' => '#',
                        'ASESORÍAS' => '#'
                    ]" 
                />
                <x-footer-column 
                    title="NUESTROS PRODUCTOS" 
                    :links="[
                        'ACCESORIOS' => '#',
                        'REPUESTOS GENERALES' => '#',
                        'BATERÍAS' => '#',
                        'NEUMÁTICOS' => '#'
                    ]" 
                />
                <div class="flex flex-col gap-4">
                    <h3 class="text-white font-bold tracking-wider uppercase text-lg font-sans">
                        SUSCRÍBETE
                    </h3>
                    
                    <form action="#" method="POST" class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-gray-300 text-xs font-semibold tracking-wider">TU E-MAIL</label>
                            <input type="email" name="email" placeholder="Ingresa tu E-mail" 
                                class="w-full px-4 py-3 bg-black text-gray-300 rounded-lg border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-blue-600 transition-colors">
                        </div>
                        
                        <button type="submit" 
                            class="w-full py-3 bg-orange-600 text-white font-bold text-sm tracking-wider rounded-lg hover:bg-blue-700 transition-colors uppercase">
                            SUSCRÍBETE
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>

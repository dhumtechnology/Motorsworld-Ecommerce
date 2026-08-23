<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ file_exists(public_path('favicon.ico')) ? filemtime(public_path('favicon.ico')) : '1' }}" type="image/x-icon">
    <title>@yield('title', config('app.name'))</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="antialiased overflow-x-hidden">
    <header
        class="sticky top-0 z-50 bg-black"
        x-data="{ mobileOpen: false, storeOpen: false }"
        @keydown.escape.window="mobileOpen = false"
    >
        <div class="mx-auto flex h-16 max-w-full items-stretch justify-between gap-3 px-4 sm:h-[4.25rem] sm:px-6 lg:h-20 lg:px-10">
            <div class="logo flex h-10 w-36 shrink-0 items-center self-center sm:h-12 sm:w-48">
                <x-logo href="{{ route('shop.home') }}" />
            </div>

            <div class="flex items-stretch gap-2 sm:gap-3 lg:gap-4">
                <nav class="hidden h-full lg:block">
                    @php
                        $shopNavLink = 'inline-flex h-full w-36 shrink-0 items-center justify-center whitespace-nowrap px-2 text-sm font-semibold uppercase tracking-wide text-white transition-colors hover:bg-orange-600';
                    @endphp
                    <ul class="flex h-full items-stretch">
                        <li class="flex h-full">
                            <a href="{{ route('shop.home') }}" class="{{ $shopNavLink }}">HOME</a>
                        </li>
                        <li class="flex h-full">
                            <a href="{{ route('shop.services.index') }}" class="{{ $shopNavLink }}">SERVICIOS</a>
                        </li>
                        <x-dropdown title="TIENDA" :trigger-class="$shopNavLink">
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
                        <li class="flex h-full">
                            <a href="{{ route('shop.about') }}" class="{{ $shopNavLink }}">NOSOTROS</a>
                        </li>
                        <li class="flex h-full">
                            <a href="{{ route('shop.blog.index') }}" class="{{ $shopNavLink }}">BLOG</a>
                        </li>
                        <li class="flex h-full">
                            <a href="{{ route('shop.contact') }}" class="{{ $shopNavLink }}">CONTÁCTANOS</a>
                        </li>
                    </ul>
                </nav>

                <div class="flex h-full items-stretch">
                <x-search
                    :categories="$searchCategories ?? []"
                    :products="$searchRecommendedProducts ?? collect()"
                    value="{{ request('search') }}"
                    :action="route('shop.catalog')"
                />

                <x-cart-drawer
                    :lines="$cartDrawerLines ?? []"
                    :item-count="$cartItemCount ?? 0"
                    :totals="$cartDrawerTotals ?? null"
                />

                <div class="relative h-full">
                    @auth
                        @php
                            $authProfile = auth()->user()?->customerProfile;
                            $nameParts = preg_split(
                                '/\s+/u',
                                trim(($authProfile?->first_name ?? '').' '.($authProfile?->last_name ?? '')),
                                -1,
                                PREG_SPLIT_NO_EMPTY,
                            ) ?: [];
                            $authInitials = '';
                            foreach (array_slice($nameParts, 0, 2) as $namePart) {
                                $authInitials .= mb_strtoupper(mb_substr($namePart, 0, 1));
                            }
                            if ($authInitials === '') {
                                $authInitials = mb_strtoupper(mb_substr((string) auth()->user()?->email, 0, 2));
                            }
                        @endphp
                        <div
                            x-data="{ open: false }"
                            class="relative h-full"
                        >
                            <button
                                type="button"
                                @click="open = !open"
                                @click.away="open = false"
                                class="inline-flex h-full min-w-11 items-center justify-center px-3 text-white transition-colors hover:bg-orange-600"
                                :class="open ? 'bg-orange-600' : ''"
                                title="Mi cuenta"
                                aria-label="Mi cuenta"
                                aria-haspopup="true"
                                :aria-expanded="open.toString()"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold tracking-wide text-orange-600 shadow-sm" aria-hidden="true">
                                    {{ $authInitials }}
                                </span>
                            </button>

                            <div
                                x-show="open"
                                x-transition
                                class="absolute right-0 top-full z-50 mt-0 w-52 rounded-b-lg border border-gray-100 bg-white shadow-lg"
                                style="display: none;"
                            >
                                <div class="py-2">
                                    @if (auth()->user()?->canAccessAdmin())
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
                        <a href="{{ route('login') }}" title="Iniciar sesión" class="inline-flex h-full min-w-11 items-center justify-center px-3 text-white transition-colors hover:bg-orange-600" aria-label="Iniciar sesión">
                            <svg class="h-5 w-5" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M8.125 15.0625H8.73633C9.893 15.5592 11.1613 15.8438 12.5 15.8438C13.8372 15.8438 15.1095 15.5595 16.2637 15.0625H16.875C19.9458 15.0625 22.4375 17.5542 22.4375 20.625V22.6562C22.4375 23.3979 21.8354 24 21.0938 24H3.90625C3.16459 24 2.5625 23.3979 2.5625 22.6562V20.625C2.5625 17.5542 5.05424 15.0625 8.125 15.0625ZM12.5 1C15.3999 1 17.75 3.35014 17.75 6.25C17.75 9.14986 15.3999 11.5 12.5 11.5C9.60014 11.5 7.25 9.14986 7.25 6.25C7.25 3.35014 9.60014 1 12.5 1Z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                    @endauth
                </div>

                <button
                    type="button"
                    class="inline-flex h-full min-w-11 items-center justify-center px-3 text-white transition-colors hover:bg-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-orange-500 lg:hidden"
                    :class="mobileOpen ? 'bg-orange-600' : ''"
                    @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen.toString()"
                    aria-controls="shop-mobile-menu"
                    aria-label="Abrir menú"
                >
                    <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                </div>
            </div>
        </div>

        {{-- Mobile collapsible nav --}}
        <div
            id="shop-mobile-menu"
            x-show="mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="lg:hidden border-t border-white/10 bg-black"
        >
            <nav class="mx-auto max-h-[min(70vh,28rem)] overflow-y-auto px-4 py-3 sm:px-6">
                <ul class="flex flex-col gap-1">
                    <li>
                        <a href="{{ route('shop.home') }}" class="block rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Home</a>
                    </li>
                    <li>
                        <a href="{{ route('shop.services.index') }}" class="block rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Servicios</a>
                    </li>
                    <li>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500"
                            @click="storeOpen = !storeOpen"
                            :aria-expanded="storeOpen.toString()"
                        >
                            <span>Tienda</span>
                            <svg class="h-4 w-4 transition-transform" :class="storeOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="storeOpen" x-cloak class="ml-3 mb-1 space-y-1 border-l border-white/15 pl-3">
                            <a href="{{ route('shop.catalog', ['section' => 'motos']) }}" class="block rounded px-3 py-2.5 text-sm text-neutral-300 hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Motos</a>
                            <a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" class="block rounded px-3 py-2.5 text-sm text-neutral-300 hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Accesorios y más</a>
                        </div>
                    </li>
                    <li>
                        <a href="{{ route('shop.about') }}" class="block rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Nosotros</a>
                    </li>
                    <li>
                        <a href="{{ route('shop.blog.index') }}" class="block rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Blog</a>
                    </li>
                    <li>
                        <a href="{{ route('shop.contact') }}" class="block rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Contáctanos</a>
                    </li>
                    @auth
                        <li class="mt-2 border-t border-white/10 pt-2">
                            @if (auth()->user()?->canAccessAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="block rounded px-3 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500" @click="mobileOpen = false">Panel admin</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full rounded px-3 py-3 text-left text-sm font-bold uppercase tracking-wide text-white hover:bg-white/5 hover:text-orange-500">
                                    Cerrar sesión
                                </button>
                            </form>
                        </li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main class="">
        @yield('content')
    </main>

    <footer class="text-white py-14 bg-black">
        <div class="mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 max-w-[95%] px-4 md:px-8">
            
            <div class="flex flex-col gap-3 md:col-span-3">
                <x-logo href="{{ route('shop.home') }}" size="lg" />
                
                <div class="flex items-center gap-2 py-4">
                    <a href="https://www.facebook.com/motoworld.pe/?ref=PROFILE_EDIT_xav_ig_profile_page_web#" target="_blank">
                        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450">
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
                        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450">
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
                        <svg width="40px" height="40px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450">
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

            <div class="md:col-span-2">
                <x-footer-column
                    title="Clientes"
                    :links="$footerLinks['clientes']"
                />
            </div>

            <div class="md:col-span-2">
                <x-footer-column
                    title="Productos"
                    :links="$footerLinks['productos']"
                />
            </div>

            <div class="md:col-span-2">
                <x-footer-column
                    title="Acerca de"
                    :links="$footerLinks['acerca_de']"
                />
            </div>

            <div class="flex flex-col gap-3 md:col-span-3">
                <h3 class="text-white font-extrabold tracking-wider uppercase text-sm font-sans">
                    ¿Quejas o reclamos?
                </h3>

                <a
                    href="{{ route('shop.claim-book') }}"
                    class="inline-block w-fit"
                    title="Libro de reclamaciones"
                    aria-label="Libro de reclamaciones"
                >
                    <img
                        src="{{ asset('images/home/libro-de-reclamaciones.png') }}"
                        alt="Libro de reclamaciones"
                        class="h-24 w-auto transition-transform hover:scale-105"
                        width="96"
                        height="112"
                    >
                </a>
                <x-payments-methods-cards/>
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
        [x-cloak] { display: none !important; }
    </style>

    <script src="{{ asset('js/shop-cart.js') }}?v={{ @filemtime(public_path('js/shop-cart.js')) ?: time() }}" defer></script>
    @stack('scripts')
</body>
</html>

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
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <div class="logo">
                <a href="{{ route('shop.catalog') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16 w-auto">
                </a>
            </div>
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

                    <li><a href="{{ route('shop.catalog', ['section' => 'motos']) }}" class="px-3 py-2 hover:text-blue-600">SERVICIOS</a></li>
                    <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" class="px-3 py-2 hover:text-blue-600">TIENDA</a></li>
                    <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" class="px-3 py-2 hover:text-blue-600">NOSOTROS</a></li>
                    <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}" class="px-3 py-2 hover:text-blue-600">CONTÁCTANOS</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 bg-white">
        @yield('footer')
    </footer>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
    <body>
        <header>
            <div class="logo">
                <a href="{{ route('shop.catalog') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo">
                </a>
            </div>
            <nav>
                <ul>
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
                    
                    <li><a href="{{ route('shop.catalog', ['section' => 'motos']) }}">SERVICIOS</a></li>
                    <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}">TIENDA</a></li>
                    <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}">NOSOTROS</a></li>
                    <li><a href="{{ route('shop.catalog', ['section' => 'accesorios']) }}">CONTÁCTANOS</a></li>
                </ul>
            </nav>

        </header>
        
        <main>
            @yield('content')
        </main>

        <footer>
            @yield('footer')
        </footer>
    </body>
</html>
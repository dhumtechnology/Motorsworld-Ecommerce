<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <header>
        <nav style="display: flex; justify-content: space-between; align-items: center; background-color: #f0f0f0; padding: 10px;">
            <a href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 100px; height: 100px;">
            </a>
            <ul style="display: flex; gap: 10px;">
                <li>
                    <a href="{{ route('shop.home') }}">Tienda</a>
                </li>
                <li>
                    <a href="{{ route('shop.catalog') }}">Catálogo</a>
                </li>
            </ul>
        </nav>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
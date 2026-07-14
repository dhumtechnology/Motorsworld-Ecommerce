<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin — '.config('app.name'))</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#252525] text-gray-100 antialiased min-h-screen">
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-[#1e1e1e] border-b border-neutral-800 px-6 py-4 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg font-black uppercase tracking-wide text-white">
                        @yield('page-title', 'Panel administrativo')
                    </h1>
                    @hasSection('page-subtitle')
                        <p class="text-xs text-neutral-400 mt-0.5">@yield('page-subtitle')</p>
                    @endif
                </div>

                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('shop.catalog') }}" class="text-neutral-400 hover:text-orange-500 transition-colors font-semibold">
                        Ver tienda
                    </a>
                    <span class="text-neutral-600">|</span>
                    <span class="text-neutral-300 hidden sm:inline">{{ auth()->user()?->email }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-orange-500 hover:text-orange-400 font-bold uppercase text-xs tracking-wider">
                            Salir
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6 overflow-x-auto">
                @if (session('status'))
                    <div class="mb-6 rounded border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

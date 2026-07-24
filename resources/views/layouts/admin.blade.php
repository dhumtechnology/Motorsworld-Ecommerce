<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin — '.config('app.name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell antialiased">
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-secondary">
            <header class="bg-surface border-b border-border px-6 py-4 flex items-center justify-between gap-4 sticky top-0 z-20">
                <div class="min-w-0">
                    <h1 class="admin-page-title text-xl sm:text-2xl truncate">
                        @yield('page-title', 'Panel administrativo')
                    </h1>
                    @hasSection('page-subtitle')
                        <p class="admin-page-subtitle text-xs mt-0.5">@yield('page-subtitle')</p>
                    @endif
                </div>

                <div class="flex items-center gap-3 sm:gap-4 text-sm font-secondary shrink-0">
                    <a href="{{ route('shop.catalog') }}" class="text-muted hover:text-primary transition-colors font-semibold">
                        Ver tienda
                    </a>
                    <span class="text-border-strong hidden sm:inline">|</span>
                    <span class="text-text-soft hidden md:inline font-medium">{{ auth()->user()?->email }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-primary hover:text-primary-hover font-bold uppercase text-xs tracking-wider font-secondary">
                            Salir
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-5 sm:p-6 lg:p-8 overflow-x-auto">
                @if (session('status'))
                    <div class="mb-6 rounded-admin border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 font-secondary">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

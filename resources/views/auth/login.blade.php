@extends('layouts.shop')

@section('title', 'Iniciar sesión — '.config('app.name'))

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-[#1e1e1e] border border-neutral-800 rounded-md p-8 text-white">
            <h1 class="text-2xl font-black uppercase tracking-wide text-center mb-2">
                Iniciar sesión
            </h1>
            <p class="text-sm text-neutral-400 text-center mb-8">
                Accede con tu cuenta de Motosworld
            </p>

            @if (session('status'))
                <div class="mb-6 rounded border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                        Correo electrónico
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                        placeholder="tu@email.com"
                    >
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                        Contraseña
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                        placeholder="••••••••"
                    >
                </div>

                <label class="flex items-center gap-2 text-sm text-neutral-400 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                        class="rounded border-neutral-600 bg-[#151515] text-orange-600 focus:ring-orange-600"
                    >
                    Recordarme
                </label>

                <button
                    type="submit"
                    class="w-full py-3 bg-orange-600 text-white font-extrabold text-xs tracking-widest rounded hover:bg-orange-700 transition-colors uppercase"
                >
                    Entrar
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-neutral-500">
                <a href="{{ route('shop.catalog') }}" class="text-orange-500 hover:text-orange-400 font-semibold">
                    ← Volver al catálogo
                </a>
            </p>

            @if (app()->environment('local'))
                <div class="mt-8 pt-6 border-t border-neutral-800 text-xs text-neutral-500 space-y-2">
                    <p class="font-bold uppercase tracking-wider text-neutral-400">Cuentas de prueba (seeders)</p>
                    <p><span class="text-neutral-300">Cliente:</span> test@example.com / password</p>
                    <p><span class="text-neutral-300">Admin:</span> admin@motosworld.test / password</p>
                </div>
            @endif
        </div>
    </div>
@endsection

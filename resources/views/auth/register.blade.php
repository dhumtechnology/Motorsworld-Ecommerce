@extends('layouts.shop')

@section('title', 'Crear cuenta — '.config('app.name'))

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-lg bg-[#1e1e1e] border border-neutral-800 rounded-md p-8 text-white">
            <h1 class="text-2xl font-black uppercase tracking-wide text-center mb-2">
                Crear cuenta
            </h1>
            <p class="text-sm text-neutral-400 text-center mb-8">
                Regístrate como cliente de Motoworld
            </p>

            @if ($errors->any())
                <div class="mb-6 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('register.store') }}"
                method="POST"
                class="space-y-5"
                x-data="{ submitting: false }"
                @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
            >
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                            Nombres
                        </label>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            required
                            autofocus
                            autocomplete="given-name"
                            class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                            placeholder="Juan"
                        >
                    </div>

                    <div>
                        <label for="last_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                            Apellidos
                        </label>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            required
                            autocomplete="family-name"
                            class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                            placeholder="Pérez"
                        >
                    </div>
                </div>

                <div>
                    <label for="document" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                        Documento (DNI / CE)
                    </label>
                    <input
                        type="text"
                        id="document"
                        name="document"
                        value="{{ old('document') }}"
                        required
                        autocomplete="off"
                        class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                        placeholder="12345678"
                    >
                </div>

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
                        autocomplete="email"
                        class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                        placeholder="tu@email.com"
                    >
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                        Teléfono <span class="normal-case font-normal text-neutral-500">(opcional)</span>
                    </label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        autocomplete="tel"
                        class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                        placeholder="920 883 723"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                            Contraseña
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                            placeholder="••••••••"
                        >
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
                            Confirmar contraseña
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-2.5 bg-[#151515] text-gray-200 rounded border border-neutral-700 placeholder-neutral-500 focus:outline-none focus:border-orange-600 transition-colors text-sm"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full inline-flex items-center justify-center gap-2 py-3 bg-orange-600 text-white font-extrabold text-xs tracking-widest rounded hover:bg-orange-700 transition-colors uppercase disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <svg
                        x-show="submitting"
                        x-cloak
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Registrando…' : 'Crear cuenta'">Crear cuenta</span>
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-neutral-400">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="text-orange-500 hover:text-orange-400 font-semibold">
                    Iniciar sesión
                </a>
            </p>

            <p class="mt-4 text-center text-sm text-neutral-500">
                <a href="{{ route('shop.catalog') }}" class="text-orange-500 hover:text-orange-400 font-semibold">
                    ← Volver al catálogo
                </a>
            </p>
        </div>
    </div>
@endsection

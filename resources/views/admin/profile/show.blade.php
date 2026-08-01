@extends('layouts.admin')

@section('title', 'Mi perfil — Admin')
@section('page-title', 'Mi perfil')
@section('page-subtitle', 'Datos de tu cuenta administrativa')

@section('content')
    @php
        $statusLabels = [
            'active' => 'Activo',
            'pending' => 'Pendiente',
            'disabled' => 'Inactivo',
            'locked' => 'Bloqueado',
        ];
    @endphp

    <div class="grid gap-6 max-w-4xl lg:grid-cols-2">
        <div class="rounded-lg border border-border bg-surface p-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-text mb-4">Mis datos</h2>

            @if ($errors->any() && ! $errors->hasAny(['current_password', 'password', 'password_confirmation']))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <dl class="space-y-3 text-sm mb-6">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Rol</dt>
                    <dd class="font-semibold text-text">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Estado</dt>
                    <dd class="font-semibold text-text">{{ $statusLabels[$user->status?->value ?? ''] ?? ($user->status?->value ?? '—') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Último acceso</dt>
                    <dd class="font-semibold text-text">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                    Guardar datos
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-border bg-surface p-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-text mb-4">Cambiar contraseña</h2>
            <p class="text-xs text-muted mb-4">Solo puedes cambiar tu propia contraseña. No es posible ver ni modificar la de otros usuarios.</p>

            @if ($errors->hasAny(['current_password', 'password', 'password_confirmation']))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Contraseña actual</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nueva contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Confirmar nueva contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                    Actualizar contraseña
                </button>
            </form>
        </div>
    </div>
@endsection

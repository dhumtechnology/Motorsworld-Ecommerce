@extends('layouts.admin')

@section('title', $user->email.' — Admin')
@section('page-title', 'Detalle de usuario')
@section('page-subtitle', '#'.$user->id)

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];
        $statusKey = $user->status instanceof \App\Enums\Auth\UserStatus
            ? $user->status->value
            : (string) $user->status;
        $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a usuarios
        </a>
        <a
            href="{{ route('admin.users.edit', $user) }}"
            class="inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors"
        >
            Editar
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Estado</p>
            <p class="mt-2">
                <span class="inline-flex items-center rounded border px-2.5 py-1 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                    {{ $statusMeta['label'] }}
                </span>
            </p>
            <p class="mt-2 text-xs text-muted">
                {{ $user->isActive() ? 'Puede iniciar sesión' : 'Sin acceso al panel' }}
            </p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Último acceso</p>
            <p class="mt-2 text-lg font-bold text-text">
                {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}
            </p>
            <p class="mt-1 text-xs text-muted">
                {{ $user->last_login_at?->diffForHumans() ?? 'Sin registros de ingreso' }}
            </p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Sesiones</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['sessions_count']) }}</p>
            <p class="mt-1 text-xs text-muted">Registros de sesión asociados</p>
        </div>
        <div class="rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Notificaciones</p>
            <p class="mt-2 text-2xl font-bold text-text">{{ number_format($stats['notifications_count']) }}</p>
            <p class="mt-1 text-xs text-muted">Notificaciones en la app</p>
        </div>
    </div>

    <div class="rounded-lg border border-border bg-surface p-6 max-w-3xl">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div>
                <h2 class="text-sm font-title text-text">Información de la cuenta</h2>
                <p class="text-xs text-muted mt-1">Datos del usuario administrador</p>
            </div>
            @if ($stats['is_current'])
                <span class="inline-flex items-center rounded border border-primary/30 bg-primary-soft px-2.5 py-1 text-xs font-bold uppercase text-primary">
                    Tu cuenta
                </span>
            @endif
        </div>

        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">ID</dt>
                <dd class="mt-1 font-mono font-semibold text-text">#{{ $user->id }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Email</dt>
                <dd class="mt-1 font-semibold text-text">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Rol</dt>
                <dd class="mt-1 text-text-soft">
                    {{ $user->roles->pluck('name')->join(', ') ?: 'Administrador' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Email verificado</dt>
                <dd class="mt-1 text-text-soft">
                    {{ $user->email_verified_at?->format('d/m/Y H:i') ?? 'No verificado' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Registro</dt>
                <dd class="mt-1 text-text-soft">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Actualizado</dt>
                <dd class="mt-1 text-text-soft">{{ $user->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </div>
@endsection

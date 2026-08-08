@extends('layouts.admin')

@section('title', 'Rol — Admin')
@section('page-title', $role->name)
@section('page-subtitle', 'Detalle del rol')

@section('content')
    <div class="mb-4 flex flex-wrap gap-3">
        <a href="{{ route('admin.roles.index') }}" class="rounded border border-border px-4 py-2 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Volver</a>
        @can('roles.update')
            <a href="{{ route('admin.roles.edit', $role) }}" class="rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">Editar</a>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg border border-border bg-surface p-5 lg:col-span-1 space-y-4">
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Slug</dt>
                <dd class="mt-1 font-mono text-sm text-text">{{ $role->slug }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Usuarios</dt>
                <dd class="mt-1 text-text font-semibold">{{ $role->users_count }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Descripción</dt>
                <dd class="mt-1 text-text-soft text-sm">{{ $role->description ?: '—' }}</dd>
            </div>
            @if ($role->isSystem())
                <p class="text-xs font-bold uppercase tracking-wide text-primary">Rol del sistema</p>
            @endif
        </div>

        <div class="rounded-lg border border-border bg-surface p-5 lg:col-span-2">
            <h2 class="text-sm font-bold uppercase tracking-wider text-muted mb-4">Permisos ({{ $role->permissions->count() }})</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @forelse ($role->permissions as $permission)
                    <div class="rounded border border-border bg-secondary/40 px-3 py-2">
                        <p class="text-sm font-semibold text-text">{{ $permission->name }}</p>
                        <p class="font-mono text-[11px] text-muted">{{ $permission->slug }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted">Sin permisos asignados.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

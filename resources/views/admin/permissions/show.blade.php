@extends('layouts.admin')

@section('title', 'Permiso — Admin')
@section('page-title', $permission->name)
@section('page-subtitle', 'Detalle del permiso')

@section('content')
    <div class="mb-4 flex flex-wrap gap-3">
        <a href="{{ route('admin.permissions.index') }}" class="rounded border border-border px-4 py-2 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Volver</a>
        @can('permissions.update')
            <a href="{{ route('admin.permissions.edit', $permission) }}" class="rounded bg-primary px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">Editar</a>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg border border-border bg-surface p-5 lg:col-span-1 space-y-4">
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Slug</dt>
                <dd class="mt-1 font-mono text-sm text-text">{{ $permission->slug }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-muted">Descripción</dt>
                <dd class="mt-1 text-text-soft text-sm">{{ $permission->description ?: '—' }}</dd>
            </div>
        </div>

        <div class="rounded-lg border border-border bg-surface p-5 lg:col-span-2">
            <h2 class="text-sm font-bold uppercase tracking-wider text-muted mb-4">Roles con este permiso ({{ $permission->roles->count() }})</h2>
            <div class="flex flex-wrap gap-2">
                @forelse ($permission->roles as $role)
                    <a href="{{ route('admin.roles.show', $role) }}" class="inline-flex rounded border border-border bg-secondary px-3 py-1.5 text-sm font-semibold text-text hover:border-primary transition-colors">
                        {{ $role->name }}
                    </a>
                @empty
                    <p class="text-sm text-muted">Ningún rol tiene este permiso.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Permisos — Admin')
@section('page-title', 'Permisos')
@section('page-subtitle', 'Permisos disponibles para asignar a roles')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.permissions.index') }}" id="admin-permissions-filters" class="space-y-4">
            <div class="max-w-md">
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Nombre, slug o descripción..."
                       class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            @if ($hasActiveFilters)
                    <a href="{{ route('admin.permissions.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
                @endif
        </form>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        @can('permissions.delete')
            <button type="button" id="bulk-delete-btn" disabled data-open-confirm="bulk-delete-modal"
                    class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold uppercase tracking-wide text-red-600 transition-colors enabled:hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">
                Eliminar seleccionados
                <span id="bulk-delete-count" class="hidden">(0)</span>
            </button>
        @else
            <div></div>
        @endcan
        @can('permissions.create')
            <a href="{{ route('admin.permissions.create') }}"
               class="inline-flex items-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                Agregar permiso
            </a>
        @endcan
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $permissions->total() }}</span>
                {{ $permissions->total() === 1 ? 'permiso' : 'permisos' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtrados)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold w-12">
                            <input type="checkbox" id="select-all-items" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary" @disabled($permissions->isEmpty())>
                        </th>
                        <th class="px-5 py-3 font-bold">ID</th>
                        <th class="px-5 py-3 font-bold">Nombre</th>
                        <th class="px-5 py-3 font-bold">Slug</th>
                        <th class="px-5 py-3 font-bold">Roles</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($permissions as $permission)
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <input type="checkbox" value="{{ $permission->id }}" data-row-checkbox
                                       class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary">
                            </td>
                            <td class="px-5 py-3 font-mono text-muted">
                                <span role="link" tabindex="0" class="text-sky-700 cursor-pointer select-none hover:underline"
                                      ondblclick="window.location.href='{{ route('admin.permissions.show', $permission) }}'">#{{ $permission->id }}</span>
                            </td>
                            <td class="px-5 py-3 font-semibold text-text">{{ $permission->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-muted">{{ $permission->slug }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">{{ $permission->roles_count }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @can('permissions.view')
                                        <a href="{{ route('admin.permissions.show', $permission) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors" title="Ver">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                                        </a>
                                    @endcan
                                    @can('permissions.update')
                                        <a href="{{ route('admin.permissions.edit', $permission) }}" class="inline-flex h-9 w-9 items-center justify-center rounded border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors" title="Editar">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
                                        </a>
                                    @endcan
                                    @can('permissions.delete')
                                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded border border-red-200 bg-red-50/50 text-red-600 hover:bg-red-100 transition-colors" title="Eliminar"
                                                data-open-confirm="single-delete-modal"
                                                data-delete-url="{{ route('admin.permissions.destroy', $permission) }}"
                                                data-delete-message="¿Eliminar el permiso «{{ $permission->name }}»?">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" /></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-muted">No se encontraron permisos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($permissions->hasPages())
            <div class="px-5 py-4 border-t border-border">{{ $permissions->links('vendor.pagination.admin') }}</div>
        @endif
    </div>

    <x-confirm-modal id="single-delete-modal" title="Eliminar permiso" message="¿Seguro que deseas eliminar este permiso?" confirm-label="Eliminar" method="DELETE" :action="route('admin.permissions.index')" />
    <x-confirm-modal id="bulk-delete-modal" title="Eliminar permisos" message="¿Eliminar los permisos seleccionados?" confirm-label="Eliminar seleccionados" method="DELETE" :action="route('admin.permissions.bulk-destroy')" />

    @include('admin.partials.crud-list-scripts', [
        'filterFormId' => 'admin-permissions-filters',
        'entityLabelSingular' => 'permiso',
        'entityLabelPlural' => 'permisos',
    ])
@endsection

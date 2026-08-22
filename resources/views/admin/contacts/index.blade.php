@extends('layouts.admin')

@section('title', 'Contactos — Admin')
@section('page-title', 'Contactos')
@section('page-subtitle', 'Mensajes enviados desde la página de contacto')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.contacts.index') }}" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Código, nombre, documento, correo o mensaje..."
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                    <select id="status" name="status" class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Todos</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                    Filtrar
                </button>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.contacts.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $messages->total() }}</span>
                {{ $messages->total() === 1 ? 'mensaje' : 'mensajes' }}
                @if ($hasActiveFilters)<span class="text-muted">(filtrados)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold">Código</th>
                        <th class="px-5 py-3 font-bold">Cliente</th>
                        <th class="px-5 py-3 font-bold">Mensaje</th>
                        <th class="px-5 py-3 font-bold">Fecha</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($messages as $item)
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3 font-semibold text-text whitespace-nowrap">{{ $item->code }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $item->fullName() }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ $item->email }}</p>
                                <p class="text-xs text-muted">Doc. {{ $item->document }} · {{ $item->phone }}</p>
                                @if ($item->user)
                                    <p class="text-xs text-primary mt-1">Cuenta #{{ $item->user_id }}</p>
                                @else
                                    <p class="text-xs text-muted mt-1">Sin cuenta registrada</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                <p class="line-clamp-2">{{ $item->message }}</p>
                            </td>
                            <td class="px-5 py-3 text-text-soft whitespace-nowrap">
                                {{ $item->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold uppercase text-muted">
                                    {{ $item->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.contacts.show', $item) }}"
                                   class="text-xs font-bold uppercase tracking-wider text-primary hover:text-primary-hover">
                                    Gestionar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-muted">
                                No hay mensajes de contacto registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messages->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
@endsection

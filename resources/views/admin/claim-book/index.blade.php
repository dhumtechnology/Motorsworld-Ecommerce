@extends('layouts.admin')

@section('title', $type->labelPlural().' — Admin')
@section('page-title', 'Libro de reclamaciones')
@section('page-subtitle', $type->labelPlural().' registradas por clientes')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route($indexRoute) }}" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Buscar</label>
                    <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Código, nombre, documento o correo..."
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
                    <a href="{{ route($indexRoute) }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $entries->total() }}</span>
                {{ $entries->total() === 1 ? strtolower($type->label()) : strtolower($type->labelPlural()) }}
                @if ($hasActiveFilters)<span class="text-muted">(filtradas)</span>@endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th class="px-5 py-3 font-bold">Código</th>
                        <th class="px-5 py-3 font-bold">Cliente</th>
                        <th class="px-5 py-3 font-bold">Bien</th>
                        <th class="px-5 py-3 font-bold">Fecha</th>
                        <th class="px-5 py-3 font-bold">Estado</th>
                        <th class="px-5 py-3 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3 font-semibold text-text whitespace-nowrap">{{ $entry->code }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $entry->consumerFullName() }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ $entry->email }}</p>
                                <p class="text-xs text-muted">Doc. {{ $entry->document }}</p>
                                @if ($entry->user)
                                    <p class="text-xs text-primary mt-1">Cuenta #{{ $entry->user_id }}</p>
                                @else
                                    <p class="text-xs text-muted mt-1">Sin cuenta registrada</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                <p>{{ $entry->good_type->label() }}</p>
                                <p class="text-xs text-muted line-clamp-2 mt-0.5">{{ $entry->good_description }}</p>
                            </td>
                            <td class="px-5 py-3 text-text-soft whitespace-nowrap">
                                {{ $entry->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold uppercase text-muted">
                                    {{ $entry->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.claim-book.show', $entry) }}"
                                   class="text-xs font-bold uppercase tracking-wider text-primary hover:text-primary-hover">
                                    Gestionar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-muted">
                                No hay {{ strtolower($type->labelPlural()) }} registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($entries->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
@endsection

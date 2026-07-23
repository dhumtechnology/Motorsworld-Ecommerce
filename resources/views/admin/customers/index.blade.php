@extends('layouts.admin')

@section('title', 'Clientes — Admin')
@section('page-title', 'Clientes')
@section('page-subtitle', 'Listado de clientes registrados (solo consulta)')

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-secondary text-muted border-border'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-50 text-red-600 border-red-200'],
        ];
    @endphp

    <div class="rounded-lg border border-border bg-surface p-5 mb-6">
        <form method="GET" action="{{ route('admin.customers.index') }}" id="admin-customers-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Buscar
                    </label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Nombre, documento, email o teléfono..."
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>

                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                        Estado
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ $statusLabels[$status->value]['label'] ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <p id="filters-live-hint" class="text-xs text-muted">
                    Los filtros se aplican automáticamente
                </p>
                @if ($hasActiveFilters)
                    <a
                        href="{{ route('admin.customers.index') }}"
                        class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $customers->total() }}</span>
                {{ $customers->total() === 1 ? 'cliente' : 'clientes' }}
                @if ($hasActiveFilters)
                    <span class="text-muted">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-secondary text-xs uppercase tracking-wider text-muted border-b border-border">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-bold">Cliente</th>
                        <th scope="col" class="px-5 py-3 font-bold">Documento</th>
                        <th scope="col" class="px-5 py-3 font-bold">Contacto</th>
                        <th scope="col" class="px-5 py-3 font-bold">Órdenes</th>
                        <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                        <th scope="col" class="px-5 py-3 font-bold">Registro</th>
                        <th scope="col" class="px-5 py-3 font-bold">Último acceso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($customers as $customer)
                        @php
                            $profile = $customer->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            $statusKey = $customer->status instanceof \App\Enums\Auth\UserStatus
                                ? $customer->status->value
                                : (string) $customer->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-secondary text-muted border-border'];
                        @endphp
                        <tr class="hover:bg-secondary/60 transition-colors">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-text">{{ $fullName !== '' ? $fullName : 'Sin nombre' }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ $customer->email }}</p>
                            </td>
                            <td class="px-5 py-3 font-mono text-text-soft">
                                {{ $profile?->document ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-text-soft">
                                {{ $profile?->phone ?: '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-border bg-secondary px-2 py-0.5 text-xs font-bold text-text-soft">
                                    {{ $customer->orders_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted whitespace-nowrap">
                                {{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-muted whitespace-nowrap">
                                {{ $customer->last_login_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-muted">
                                No se encontraron clientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $customers->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    <script>
        (function () {
            const form = document.getElementById('admin-customers-filters');
            if (!form) return;

            let submitTimer = null;
            let isSubmitting = false;

            const setHint = (text) => {
                const hint = document.getElementById('filters-live-hint');
                if (hint) hint.textContent = text;
            };

            const submitFilters = () => {
                if (isSubmitting) return;
                isSubmitting = true;
                setHint('Actualizando resultados…');
                form.requestSubmit ? form.requestSubmit() : form.submit();
            };

            const scheduleSubmit = (delay = 250) => {
                clearTimeout(submitTimer);
                setHint('Aplicando filtros…');
                submitTimer = setTimeout(submitFilters, delay);
            };

            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('input', () => scheduleSubmit(450));
                searchInput.addEventListener('search', () => scheduleSubmit(0));
            }

            document.getElementById('status')?.addEventListener('change', () => scheduleSubmit(150));
        })();
    </script>
@endsection

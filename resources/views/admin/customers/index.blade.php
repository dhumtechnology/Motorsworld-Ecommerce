@extends('layouts.admin')

@section('title', 'Clientes — Admin')
@section('page-title', 'Clientes')
@section('page-subtitle', 'Listado de clientes registrados (solo consulta)')

@section('content')
    @php
        $statusLabels = [
            'active' => ['label' => 'Activo', 'class' => 'bg-green-950 text-green-400 border-green-800'],
            'pending' => ['label' => 'Pendiente', 'class' => 'bg-yellow-950 text-yellow-400 border-yellow-800'],
            'disabled' => ['label' => 'Inactivo', 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'],
            'locked' => ['label' => 'Bloqueado', 'class' => 'bg-red-950 text-red-400 border-red-800'],
        ];
    @endphp

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 mb-6">
        <form method="GET" action="{{ route('admin.customers.index') }}" id="admin-customers-filters" class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                        Buscar
                    </label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Nombre, documento, email o teléfono..."
                        class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    >
                </div>

                <div class="lg:col-span-4">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
                        Estado
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
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
                <p id="filters-live-hint" class="text-xs text-neutral-500">
                    Los filtros se aplican automáticamente
                </p>
                @if ($hasActiveFilters)
                    <a
                        href="{{ route('admin.customers.index') }}"
                        class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-800">
            <p class="text-sm text-neutral-400">
                <span class="text-white font-bold">{{ $customers->total() }}</span>
                {{ $customers->total() === 1 ? 'cliente' : 'clientes' }}
                @if ($hasActiveFilters)
                    <span class="text-neutral-500">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#252525] text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
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
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($customers as $customer)
                        @php
                            $profile = $customer->customerProfile;
                            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                            $statusKey = $customer->status instanceof \App\Enums\Auth\UserStatus
                                ? $customer->status->value
                                : (string) $customer->status;
                            $statusMeta = $statusLabels[$statusKey] ?? ['label' => $statusKey, 'class' => 'bg-neutral-800 text-neutral-400 border-neutral-700'];
                        @endphp
                        <tr class="hover:bg-[#252525]/60 transition-colors">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $fullName !== '' ? $fullName : 'Sin nombre' }}</p>
                                <p class="text-xs text-neutral-500 mt-0.5">{{ $customer->email }}</p>
                            </td>
                            <td class="px-5 py-3 font-mono text-neutral-300">
                                {{ $profile?->document ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-neutral-300">
                                {{ $profile?->phone ?: '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border border-neutral-700 bg-[#252525] px-2 py-0.5 text-xs font-bold text-neutral-300">
                                    {{ $customer->orders_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-bold uppercase {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-neutral-400 whitespace-nowrap">
                                {{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-neutral-400 whitespace-nowrap">
                                {{ $customer->last_login_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-neutral-500">
                                No se encontraron clientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="px-5 py-4 border-t border-neutral-800">
                {{ $customers->links('vendor.pagination.tailwind') }}
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

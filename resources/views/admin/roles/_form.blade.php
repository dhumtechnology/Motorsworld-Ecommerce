@php
    /** @var \App\Models\Auth\Role|null $role */
    $role = $role ?? null;
    $isEdit = $role !== null;
    $isSystem = $role?->isSystem() ?? false;
    $selectedIds = collect(old('permission_ids', $role?->permissions?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    $groupLabels = [
        'admin' => 'Panel administrativo',
        'shop' => 'Tienda',
        'orders' => 'Órdenes / pedidos',
        ...\App\Support\Auth\PermissionCatalog::RESOURCES,
    ];
@endphp

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5">
    <div>
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
        <input id="name" name="name" type="text" @required(! $isSystem) value="{{ old('name', $role?->name) }}"
               @disabled($isSystem)
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-70">
        @if ($isSystem)
            <p class="mt-1.5 text-xs text-muted">El nombre de los roles del sistema no se puede cambiar.</p>
        @endif
    </div>

    <div>
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
        <textarea id="description" name="description" rows="3"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('description', $role?->description) }}</textarea>
    </div>

    <div data-permissions-root>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-muted">Permisos</label>
            <div class="flex gap-2">
                <button type="button" data-select-all-permissions class="text-xs font-bold uppercase tracking-wide text-primary hover:underline">Seleccionar todos</button>
                <button type="button" data-clear-permissions class="text-xs font-bold uppercase tracking-wide text-muted hover:text-text">Limpiar</button>
            </div>
        </div>

        <div class="max-h-[28rem] space-y-4 overflow-y-auto rounded border border-border bg-secondary/40 p-4">
            @forelse ($permissionGroups as $group => $permissions)
                @php
                    $groupLabel = $groupLabels[$group] ?? str_replace('_', ' ', $group);
                    $groupPermissionIds = $permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $selectedInGroup = count(array_intersect($groupPermissionIds, $selectedIds));
                    $allGroupSelected = $selectedInGroup === count($groupPermissionIds) && count($groupPermissionIds) > 0;
                    $someGroupSelected = $selectedInGroup > 0 && ! $allGroupSelected;
                @endphp
                <div data-permission-group>
                    <label class="mb-2 flex items-center gap-2 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary"
                            data-permission-group-toggle
                            @checked($allGroupSelected)
                            @if ($someGroupSelected) data-indeterminate="1" @endif
                        >
                        <span class="text-[11px] font-bold uppercase tracking-widest text-text">{{ $groupLabel }}</span>
                        <span class="text-[10px] font-semibold text-muted">({{ count($groupPermissionIds) }})</span>
                    </label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-start gap-2 rounded border border-border bg-surface px-3 py-2 text-sm text-text hover:border-primary/40 cursor-pointer">
                                <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}"
                                       class="mt-0.5 h-4 w-4 rounded border-border-strong text-primary focus:ring-primary"
                                       data-permission-checkbox
                                       @checked(in_array((int) $permission->id, $selectedIds, true))>
                                <span>
                                    <span class="font-semibold block">{{ $permission->name }}</span>
                                    <span class="font-mono text-[11px] text-muted">{{ $permission->slug }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">No hay permisos registrados. Créalos en la sección de permisos.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear rol' }}
    </button>
    <a href="{{ route('admin.roles.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

@push('scripts')
<script>
    (function () {
        const root = document.querySelector('[data-permissions-root]');
        if (!root) return;

        const syncGroup = (group) => {
            const toggle = group.querySelector('[data-permission-group-toggle]');
            const boxes = group.querySelectorAll('[data-permission-checkbox]');
            if (!toggle || boxes.length === 0) return;

            const total = boxes.length;
            const checked = Array.from(boxes).filter((el) => el.checked).length;

            toggle.checked = checked === total;
            toggle.indeterminate = checked > 0 && checked < total;
        };

        const syncAllGroups = () => {
            root.querySelectorAll('[data-permission-group]').forEach(syncGroup);
        };

        root.querySelectorAll('[data-permission-group]').forEach((group) => {
            const toggle = group.querySelector('[data-permission-group-toggle]');
            if (toggle?.dataset.indeterminate === '1') {
                toggle.indeterminate = true;
            }

            toggle?.addEventListener('change', () => {
                group.querySelectorAll('[data-permission-checkbox]').forEach((el) => {
                    el.checked = toggle.checked;
                });
                toggle.indeterminate = false;
            });

            group.querySelectorAll('[data-permission-checkbox]').forEach((el) => {
                el.addEventListener('change', () => syncGroup(group));
            });
        });

        root.querySelector('[data-select-all-permissions]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-permission-checkbox]').forEach((el) => { el.checked = true; });
            syncAllGroups();
        });

        root.querySelector('[data-clear-permissions]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-permission-checkbox]').forEach((el) => { el.checked = false; });
            syncAllGroups();
        });
    })();
</script>
@endpush

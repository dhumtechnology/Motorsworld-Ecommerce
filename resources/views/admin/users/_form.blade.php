@php
    /** @var \App\Models\Auth\User|null $user */
    $user = $user ?? null;
    $isEdit = $user !== null;

    $statusLabels = [
        'active' => 'Activo',
        'pending' => 'Pendiente',
        'disabled' => 'Inactivo',
        'locked' => 'Bloqueado',
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
        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Email *</label>
        <input id="email" name="email" type="email" required value="{{ old('email', $user?->email) }}"
               autocomplete="username"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    @unless ($isEdit)
        <div>
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                Contraseña *
            </label>
            <div class="relative">
                <input id="password" name="password" type="password" required
                       autocomplete="new-password"
                       data-password-field
                       class="w-full rounded border border-border bg-surface px-4 py-2.5 pr-11 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <button
                    type="button"
                    data-toggle-password="password"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-muted hover:text-text transition-colors"
                    title="Mostrar contraseña"
                    aria-label="Mostrar contraseña"
                >
                    <svg data-icon-show class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg data-icon-hide class="h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.12 14.12a3 3 0 01-4.24-4.24" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 1l22 22" />
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                Confirmar contraseña *
            </label>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password"
                       data-password-field
                       class="w-full rounded border border-border bg-surface px-4 py-2.5 pr-11 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <button
                    type="button"
                    data-toggle-password="password_confirmation"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-muted hover:text-text transition-colors"
                    title="Mostrar contraseña"
                    aria-label="Mostrar contraseña"
                >
                    <svg data-icon-show class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg data-icon-hide class="h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.12 14.12a3 3 0 01-4.24-4.24" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 1l22 22" />
                    </svg>
                </button>
            </div>
        </div>
    @else
        <div class="rounded border border-border bg-secondary/40 px-4 py-3 text-xs text-muted">
            Por seguridad no se puede ver ni cambiar la contraseña de otros usuarios. Cada administrador la cambia desde <span class="font-semibold text-text">Mi perfil</span>.
        </div>
    @endunless

    <div>
        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado *</label>
        <select id="status" name="status" required
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $user?->status?->value ?? 'active') === $status->value)>
                    {{ $statusLabels[$status->value] ?? $status->value }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-muted">Solo el estado Activo permite iniciar sesión en el panel.</p>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Roles *</label>
        <div class="grid gap-2 sm:grid-cols-2">
            @forelse ($roles as $role)
                <label class="flex items-start gap-2 rounded border border-border bg-surface px-3 py-2 text-sm text-text hover:border-primary/40 cursor-pointer">
                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                           class="mt-0.5 h-4 w-4 rounded border-border-strong text-primary focus:ring-primary"
                           @checked(in_array((int) $role->id, collect(old('role_ids', $user?->roles?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all(), true))>
                    <span>
                        <span class="font-semibold block">{{ $role->name }}</span>
                        @if ($role->description)
                            <span class="text-xs text-muted">{{ $role->description }}</span>
                        @endif
                    </span>
                </label>
            @empty
                <p class="text-sm text-muted">No hay roles disponibles. Crea roles en la sección correspondiente.</p>
            @endforelse
        </div>
        <p class="mt-1.5 text-xs text-muted">Al menos un rol debe incluir acceso al panel administrativo.</p>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear usuario' }}
    </button>
    <a href="{{ route('admin.users.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!input) return;

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';

            button.querySelector('[data-icon-show]')?.classList.toggle('hidden', !showing);
            button.querySelector('[data-icon-hide]')?.classList.toggle('hidden', showing);

            const label = showing ? 'Mostrar contraseña' : 'Ocultar contraseña';
            button.setAttribute('title', label);
            button.setAttribute('aria-label', label);
        });
    });
</script>
@endpush

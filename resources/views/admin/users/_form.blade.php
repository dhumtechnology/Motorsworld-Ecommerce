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
    <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-300">
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

    <div>
        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
            Contraseña {{ $isEdit ? '(opcional)' : '*' }}
        </label>
        <input id="password" name="password" type="password" @required(! $isEdit)
               autocomplete="new-password"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        @if ($isEdit)
            <p class="mt-1.5 text-xs text-muted">Déjala en blanco para mantener la contraseña actual.</p>
        @endif
    </div>

    <div>
        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
            Confirmar contraseña {{ $isEdit ? '' : '*' }}
        </label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit)
               autocomplete="new-password"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

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
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Rol</label>
        <div class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text-soft">
            Administrador
        </div>
        <p class="mt-1.5 text-xs text-muted">Los usuarios creados aquí siempre tienen acceso al panel admin.</p>
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

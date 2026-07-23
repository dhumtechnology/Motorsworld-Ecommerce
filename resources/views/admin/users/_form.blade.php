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
    <div class="mb-6 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5">
    <div>
        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Email *</label>
        <input id="email" name="email" type="email" required value="{{ old('email', $user?->email) }}"
               autocomplete="username"
               class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
    </div>

    <div>
        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
            Contraseña {{ $isEdit ? '(opcional)' : '*' }}
        </label>
        <input id="password" name="password" type="password" @required(! $isEdit)
               autocomplete="new-password"
               class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
        @if ($isEdit)
            <p class="mt-1.5 text-xs text-neutral-500">Déjala en blanco para mantener la contraseña actual.</p>
        @endif
    </div>

    <div>
        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">
            Confirmar contraseña {{ $isEdit ? '' : '*' }}
        </label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit)
               autocomplete="new-password"
               class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
    </div>

    <div>
        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Estado *</label>
        <select id="status" name="status" required
                class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $user?->status?->value ?? 'active') === $status->value)>
                    {{ $statusLabels[$status->value] ?? $status->value }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-neutral-500">Solo el estado Activo permite iniciar sesión en el panel.</p>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Rol</label>
        <div class="w-full rounded border border-neutral-800 bg-[#1a1a1a] px-4 py-2.5 text-sm text-neutral-300">
            Administrador
        </div>
        <p class="mt-1.5 text-xs text-neutral-500">Los usuarios creados aquí siempre tienen acceso al panel admin.</p>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear usuario' }}
    </button>
    <a href="{{ route('admin.users.index') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">
        Cancelar
    </a>
</div>

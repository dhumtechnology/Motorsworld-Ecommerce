@php
    /** @var \App\Models\Auth\Permission|null $permission */
    $permission = $permission ?? null;
    $isEdit = $permission !== null;
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
        <input id="name" name="name" type="text" required value="{{ old('name', $permission?->name) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
            Slug {{ $isEdit ? '*' : '(opcional)' }}
        </label>
        <input id="slug" name="slug" type="text" @required($isEdit) value="{{ old('slug', $permission?->slug) }}"
               placeholder="ej. products.view"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm font-mono text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        <p class="mt-1.5 text-xs text-muted">Si lo dejas vacío al crear, se genera a partir del nombre.</p>
    </div>

    <div>
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
        <textarea id="description" name="description" rows="3"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('description', $permission?->description) }}</textarea>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear permiso' }}
    </button>
    <a href="{{ route('admin.permissions.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

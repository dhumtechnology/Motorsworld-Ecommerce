@php
    /** @var \App\Models\Appointments\ServicePackage|null $servicePackage */
    $servicePackage = $servicePackage ?? null;
    $isEdit = $servicePackage !== null;
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
        <label for="service_type_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Tipo de servicio *</label>
        <select id="service_type_id" name="service_type_id" required
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">Selecciona un servicio</option>
            @foreach ($serviceTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('service_type_id', $servicePackage?->service_type_id) === (string) $type->id)>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $servicePackage?->name) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
        <textarea id="description" name="description" rows="4"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  placeholder="Detalle del paquete…">{{ old('description', $servicePackage?->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="price" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Precio</label>
            <input id="price" name="price" type="number" min="0" step="0.01"
                   value="{{ old('price', $servicePackage?->price) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label for="currency" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Moneda</label>
            <input id="currency" name="currency" type="text" maxlength="3"
                   value="{{ old('currency', $servicePackage?->currency ?? 'PEN') }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label for="duration_minutes" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Duración (min)</label>
            <input id="duration_minutes" name="duration_minutes" type="number" min="15" max="480" step="15"
                   value="{{ old('duration_minutes', $servicePackage?->duration_minutes ?? 60) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
    </div>

    <div>
        <label class="inline-flex items-center gap-3 rounded border border-border bg-secondary px-4 py-2.5 w-full cursor-pointer">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                   @checked(old('is_active', $servicePackage?->is_active ?? true))>
            <span class="text-sm font-semibold text-text">Activo</span>
        </label>
    </div>

    @if ($isEdit)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Reservas asociadas</label>
            <div class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text-soft">
                {{ $servicePackage->appointments_count ?? 0 }}
            </div>
        </div>
    @endif
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear paquete' }}
    </button>
    <a href="{{ route('admin.service-packages.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

@php
    /** @var \App\Models\Products\VehicleModel|null $vehicleModel */
    $vehicleModel = $vehicleModel ?? null;
    $isEdit = $vehicleModel !== null;
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
        <label for="brand_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Marca *</label>
        <select id="brand_id" name="brand_id" required
                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">Seleccionar marca</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((int) old('brand_id', $vehicleModel?->brand_id) === $brand->id)>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre del modelo *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $vehicleModel?->name) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    @if ($isEdit)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Productos asociados</label>
            <div class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text-soft">
                {{ $vehicleModel->products_count ?? 0 }}
            </div>
        </div>
    @endif
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear modelo' }}
    </button>
    <a href="{{ route('admin.models.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

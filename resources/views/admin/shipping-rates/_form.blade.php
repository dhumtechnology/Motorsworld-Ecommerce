@php
    /** @var \App\Models\Shipping\ShippingRate|null $shippingRate */
    $shippingRate = $shippingRate ?? null;
    $isEdit = $shippingRate !== null;
    $selectedDistricts = old('district_ids', $shippingRate?->districts->pluck('id')->all() ?? []);
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
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre de la zona *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $shippingRate?->name) }}"
               placeholder="ej. Zona Cercana"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción / distritos visibles</label>
        <textarea id="description" name="description" rows="3" placeholder="Texto que se muestra en Envíos y devoluciones"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('description', $shippingRate?->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="price" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Precio *</label>
            <input id="price" name="price" type="number" min="0" max="999999.99" step="0.01" required
                   value="{{ old('price', $shippingRate?->price) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label for="currency" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Moneda *</label>
            <select id="currency" name="currency"
                    class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="PEN" @selected(old('currency', $shippingRate?->currency ?? 'PEN') === 'PEN')>Soles (PEN)</option>
                <option value="USD" @selected(old('currency', $shippingRate?->currency) === 'USD')>Dólares (USD)</option>
            </select>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Orden</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="9999"
                   value="{{ old('sort_order', $shippingRate?->sort_order ?? 0) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-3 rounded border border-border bg-secondary px-4 py-2.5 w-full cursor-pointer">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                       @checked(old('is_active', $shippingRate?->is_active ?? true))>
                <span class="text-sm font-semibold text-text">Activa</span>
            </label>
        </div>
    </div>

    <label class="inline-flex items-start gap-3 rounded border border-border bg-secondary px-4 py-3 cursor-pointer">
        <input type="checkbox" name="is_fallback" value="1" class="mt-0.5 h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
               @checked(old('is_fallback', $shippingRate?->is_fallback ?? false))>
        <span>
            <span class="block text-sm font-semibold text-text">Tarifa para el resto del Perú</span>
            <span class="block text-xs text-muted mt-0.5">Se aplica cuando el distrito no está en ninguna otra zona (provincias).</span>
        </span>
    </label>

    <x-multi-select
        name="district_ids"
        label="Distritos de esta zona"
        placeholder="Ningún distrito asignado"
        :options="$districts ?? []"
        :selected="$selectedDistricts"
        :source="$districtsSource ?? null"
    />
    <p class="text-xs text-muted -mt-3">Vacío si es la tarifa de resto del Perú. El checkout usa el distrito más específico.</p>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear tarifa' }}
    </button>
    <a href="{{ route('admin.shipping-rates.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>


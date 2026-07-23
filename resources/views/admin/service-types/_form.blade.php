@php
    /** @var \App\Models\Appointments\ServiceType|null $serviceType */
    $serviceType = $serviceType ?? null;
    $isEdit = $serviceType !== null;
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
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Nombre *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $serviceType?->name) }}"
               class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
    </div>

    @if ($isEdit)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Reservas asociadas</label>
            <div class="w-full rounded border border-neutral-800 bg-[#1a1a1a] px-4 py-2.5 text-sm text-neutral-300">
                {{ $serviceType->appointments_count ?? 0 }}
            </div>
        </div>
    @endif
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear servicio' }}
    </button>
    <a href="{{ route('admin.service-types.index') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">
        Cancelar
    </a>
</div>

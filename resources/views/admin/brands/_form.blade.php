@php
    /** @var \App\Models\Products\Brand|null $brand */
    $brand = $brand ?? null;
    $isEdit = $brand !== null;
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
        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $brand?->name) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    @if ($isEdit)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Modelos asociados</label>
            <div class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text-soft">
                {{ $brand->vehicle_models_count ?? 0 }}
            </div>
        </div>
    @endif

    <div data-brand-image>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imagen (opcional)</label>

        @if ($brand?->image)
            <div class="mb-3 flex items-center gap-3 rounded border border-border bg-secondary p-3">
                <img src="{{ $brand->image }}" alt="" class="h-16 w-16 rounded object-cover border border-border">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-text font-semibold">Imagen actual</p>
                    <p class="text-xs text-muted">Se reemplazará si subes otra.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500">
                    Eliminar
                </label>
            </div>
        @endif

        <div data-dropzone class="flex min-h-[140px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-secondary px-4 py-6 text-center transition-colors hover:border-primary">
            <input id="image" type="file" name="image" accept="image/*" class="sr-only" data-file-input>
            <button type="button" data-dropzone-trigger class="flex flex-col items-center cursor-pointer">
                <svg class="h-8 w-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V19a2 2 0 002 2h14a2 2 0 002-2v-2.5M16 8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <p class="mt-3 text-sm text-text-soft">Arrastra una imagen o haz clic para seleccionar</p>
                <p class="mt-1 text-xs text-muted">JPG, PNG, WEBP o GIF — máx. 5 MB</p>
            </button>
            <div data-preview class="mt-4 hidden"></div>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear marca' }}
    </button>
    <a href="{{ route('admin.brands.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-brand-image]');
        if (!root) return;

        const zone = root.querySelector('[data-dropzone]');
        const input = root.querySelector('[data-file-input]');
        const preview = root.querySelector('[data-preview]');
        const trigger = root.querySelector('[data-dropzone-trigger]');
        if (!zone || !input || !preview || !trigger) return;

        const highlight = (on) => {
            zone.classList.toggle('border-primary', on);
            zone.classList.toggle('bg-primary-soft/20', on);
        };

        const render = () => {
            preview.innerHTML = '';
            const file = input.files?.[0];
            if (!file) {
                preview.classList.add('hidden');
                return;
            }

            const url = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            preview.innerHTML =
                '<div class="inline-flex items-center gap-3 rounded-lg border border-border bg-surface p-2">' +
                '<img src="' + url + '" alt="" class="h-16 w-16 rounded object-cover">' +
                '<div class="text-left"><p class="text-sm text-text font-semibold truncate max-w-[14rem]">' + file.name + '</p>' +
                '<button type="button" data-clear class="mt-1 text-xs font-bold uppercase tracking-wide text-red-600">Quitar</button></div></div>';

            preview.querySelector('[data-clear]')?.addEventListener('click', (event) => {
                event.preventDefault();
                input.value = '';
                render();
            });
        };

        trigger.addEventListener('click', () => input.click());

        ['dragenter', 'dragover'].forEach((name) => {
            zone.addEventListener(name, (event) => {
                event.preventDefault();
                event.stopPropagation();
                highlight(true);
            });
        });

        ['dragleave', 'drop'].forEach((name) => {
            zone.addEventListener(name, (event) => {
                event.preventDefault();
                event.stopPropagation();
                highlight(false);
            });
        });

        zone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            render();
        });

        input.addEventListener('change', render);
    })();
</script>

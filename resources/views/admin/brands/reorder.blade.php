@extends('layouts.admin')

@section('title', 'Orden de marcas — Admin')
@section('page-title', 'Orden de marcas')
@section('page-subtitle', 'Arrastra para definir el orden del carrusel en el home')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted max-w-2xl">
            Solo las marcas con logo aparecen en el home. El orden que definas aquí se refleja en la franja de marcas autorizadas.
        </p>
        <a href="{{ route('admin.brands.index') }}"
           class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
            Volver al listado
        </a>
    </div>

    <div class="rounded-lg border border-border bg-surface overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted">
                <span class="text-text font-bold">{{ $brands->count() }}</span>
                {{ $brands->count() === 1 ? 'marca' : 'marcas' }}
            </p>
            <p id="brand-reorder-status" class="text-xs font-semibold text-muted" aria-live="polite"></p>
        </div>

        @if ($brands->isEmpty())
            <div class="px-5 py-12 text-center text-muted">
                No hay marcas para ordenar.
            </div>
        @else
            <ul id="brand-reorder-list" class="divide-y divide-border">
                @foreach ($brands as $brand)
                    <li
                        class="brand-reorder-item flex items-center gap-4 px-5 py-4 bg-surface hover:bg-secondary/40 transition-colors cursor-grab active:cursor-grabbing"
                        draggable="true"
                        data-id="{{ $brand->id }}"
                    >
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded border border-border bg-secondary text-muted" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="9" cy="7" r="1.5" /><circle cx="15" cy="7" r="1.5" />
                                <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                <circle cx="9" cy="17" r="1.5" /><circle cx="15" cy="17" r="1.5" />
                            </svg>
                        </span>

                        <span class="w-8 shrink-0 text-center text-xs font-bold uppercase tracking-wider text-muted brand-reorder-position">
                            {{ $loop->iteration }}
                        </span>

                        @if ($brand->hasLogo())
                            <img src="{{ $brand->image }}" alt="" class="h-12 w-12 rounded object-contain border border-border bg-white shrink-0 p-1">
                        @else
                            <div class="h-12 w-12 rounded border border-dashed border-border bg-secondary shrink-0 flex items-center justify-center text-[10px] font-bold uppercase text-muted text-center leading-tight px-1">
                                Sin logo
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-text truncate">{{ $brand->name }}</p>
                            @unless ($brand->hasLogo())
                                <p class="text-xs text-amber-700 mt-0.5">No se muestra en el home hasta que tenga logo.</p>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const list = document.getElementById('brand-reorder-list');
            const status = document.getElementById('brand-reorder-status');
            if (!list) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            let dragged = null;
            let saving = false;

            const positions = () => {
                list.querySelectorAll('.brand-reorder-item').forEach((item, index) => {
                    const badge = item.querySelector('.brand-reorder-position');
                    if (badge) badge.textContent = String(index + 1);
                });
            };

            const ids = () => Array.from(list.querySelectorAll('.brand-reorder-item')).map((item) => Number(item.dataset.id));

            const setStatus = (message, tone = 'muted') => {
                if (!status) return;
                status.textContent = message;
                status.className = 'text-xs font-semibold ' + (
                    tone === 'ok' ? 'text-emerald-700' :
                    tone === 'error' ? 'text-red-600' :
                    'text-muted'
                );
            };

            const saveOrder = async () => {
                if (saving) return;
                saving = true;
                setStatus('Guardando orden…');

                try {
                    const response = await fetch(@json(route('admin.brands.reorder.update')), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ ids: ids() }),
                    });

                    if (!response.ok) {
                        let message = 'No se pudo guardar';
                        try {
                            const data = await response.json();
                            message = data.message
                                || (data.errors?.ids ? data.errors.ids[0] : null)
                                || message;
                        } catch (parseError) {
                            // ignore
                        }
                        throw new Error(message);
                    }

                    setStatus('Orden guardado', 'ok');
                } catch (error) {
                    setStatus(error.message || 'Error al guardar. Recarga e inténtalo de nuevo.', 'error');
                } finally {
                    saving = false;
                }
            };

            list.querySelectorAll('.brand-reorder-item').forEach((item) => {
                item.addEventListener('dragstart', (event) => {
                    dragged = item;
                    item.classList.add('opacity-60', 'ring-2', 'ring-primary/40');
                    event.dataTransfer?.setData('text/plain', item.dataset.id ?? '');
                    event.dataTransfer?.setDragImage(item, 40, 20);
                });

                item.addEventListener('dragend', () => {
                    item.classList.remove('opacity-60', 'ring-2', 'ring-primary/40');
                    dragged = null;
                    list.querySelectorAll('.brand-reorder-item').forEach((row) => {
                        row.classList.remove('border-t-2', 'border-primary');
                    });
                });

                item.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    if (!dragged || dragged === item) return;

                    const rect = item.getBoundingClientRect();
                    const after = (event.clientY - rect.top) > (rect.height / 2);
                    list.insertBefore(dragged, after ? item.nextSibling : item);
                    positions();
                });

                item.addEventListener('drop', (event) => {
                    event.preventDefault();
                    positions();
                    saveOrder();
                });
            });
        })();
    </script>
@endpush

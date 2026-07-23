@extends('layouts.admin')

@section('title', 'Nuevo movimiento — Admin')
@section('page-title', 'Nuevo movimiento')
@section('page-subtitle', 'Registrar entrada o salida de inventario')

@section('content')
    <div class="rounded-lg border border-border bg-surface p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.inventory.store') }}" id="inventory-movement-form">
            @csrf

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
                    <label for="type" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Tipo *</label>
                    <select id="type" name="type" required
                            class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type', 'entry') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="product_id" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Producto *</label>
                    <select id="product_id" name="product_id" required
                            class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Seleccionar producto...</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((int) old('product_id') === $product->id)>
                                {{ $product->sku }} — {{ $product->name }} (stock: {{ (int) ($product->inventory?->available_stock ?? 0) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="quantity" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Cantidad *</label>
                    <input id="quantity" name="quantity" type="number" min="1" required value="{{ old('quantity', 1) }}"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>

                <div>
                    <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Motivo *</label>
                    <select id="reason" name="reason" required
                            class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        @foreach ($entryReasons as $reason)
                            <option value="{{ $reason->value }}" data-type="entry" @selected(old('reason', 'purchase') === $reason->value)>{{ $reason->label() }}</option>
                        @endforeach
                        @foreach ($exitReasons as $reason)
                            <option value="{{ $reason->value }}" data-type="exit" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-muted">Las salidas por venta se generan solas al confirmar el pago de una orden.</p>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Notas</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                    Registrar
                </button>
                <a href="{{ route('admin.inventory.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const typeSelect = document.getElementById('type');
            const reasonSelect = document.getElementById('reason');
            if (!typeSelect || !reasonSelect) return;

            const syncReasons = () => {
                const type = typeSelect.value;
                let firstVisible = null;
                Array.from(reasonSelect.options).forEach((option) => {
                    const match = option.dataset.type === type;
                    option.hidden = !match;
                    option.disabled = !match;
                    if (match && firstVisible === null) firstVisible = option.value;
                });
                const current = reasonSelect.options[reasonSelect.selectedIndex];
                if (!current || current.disabled) {
                    reasonSelect.value = firstVisible || '';
                }
            };

            typeSelect.addEventListener('change', syncReasons);
            syncReasons();
        })();
    </script>
@endsection

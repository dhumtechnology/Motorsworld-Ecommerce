@php
    /** @var \App\Models\Payments\PaymentMethod|null $paymentMethod */
    $paymentMethod = $paymentMethod ?? null;
    $isEdit = $paymentMethod !== null;
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
        <input id="name" name="name" type="text" required value="{{ old('name', $paymentMethod?->name) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div>
        <label for="code" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Código *</label>
        <input id="code" name="code" type="text" required value="{{ old('code', $paymentMethod?->code) }}"
               placeholder="ej. card, yape, plin"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text font-mono focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        <p class="mt-1.5 text-xs text-muted">Solo minúsculas, números, guiones y guiones bajos.</p>
    </div>

    <div>
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
        <textarea id="description" name="description" rows="3"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('description', $paymentMethod?->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Orden</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="9999"
                   value="{{ old('sort_order', $paymentMethod?->sort_order ?? 0) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-3 rounded border border-border bg-secondary px-4 py-2.5 w-full cursor-pointer">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                       @checked(old('is_active', $paymentMethod?->is_active ?? true))>
                <span class="text-sm font-semibold text-text">Activo</span>
            </label>
        </div>
    </div>

    @if ($isEdit)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Pagos asociados</label>
            <div class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text-soft">
                {{ $paymentMethod->payments_count ?? 0 }}
            </div>
        </div>
    @endif
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear medio de pago' }}
    </button>
    <a href="{{ route('admin.payment-methods.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

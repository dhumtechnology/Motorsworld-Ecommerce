@php
    /** @var \App\Models\Products\ProductOffer|null $offer */
    $offer = $offer ?? null;
    $isEdit = $offer !== null;
    $selectedProductId = old('product_id', $offer?->product_id ?? ($preselectedProductId ?? null));
    $selectedProductId = $selectedProductId === null || $selectedProductId === '' ? null : (int) $selectedProductId;
    $redirectTo = old('redirect_to', $redirectTo ?? null);

    $defaultDiscount = old(
        'discount_percent',
        $offer ? $offer->resolvedDiscountPercent((float) ($offer->product?->price_amount ?? 0)) : null
    );

    $productOptions = collect($products)->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->sku.' — '.$product->name.' ('.number_format((float) $product->price_amount, 2).' PEN)',
            'price_amount' => (float) $product->price_amount,
        ];
    });

    $productsMeta = $productOptions->mapWithKeys(fn ($item) => [
        (string) $item['id'] => [
            'price' => $item['price_amount'],
        ],
    ]);
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

@if ($redirectTo)
    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
@endif

<div class="grid gap-5" data-offer-form data-products-meta='@json($productsMeta)'>
    <div>
        <x-searchable-select
            name="product_id"
            label="Producto"
            :options="$productOptions"
            :selected="$selectedProductId"
            placeholder="Selecciona un producto…"
            :required="true"
            data-ss-id="offer-product"
        />
        <p id="product-price-hint" class="mt-1.5 text-xs text-muted">Selecciona un producto para calcular el precio final.</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="discount_percent" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descuento (%) *</label>
            <input id="discount_percent" name="discount_percent" type="number" step="0.01" min="1" max="99.99" required
                   value="{{ $defaultDiscount }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <p class="mt-1.5 text-xs text-muted">Entre 1% y 99.99%.</p>
        </div>
        <div>
            <label for="offer_price_preview" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Precio final (PEN)</label>
            <input id="offer_price_preview" type="text" readonly value="—"
                   class="w-full rounded border border-border bg-secondary px-4 py-2.5 text-sm text-muted cursor-not-allowed">
            <p class="mt-1.5 text-xs text-muted">Se calcula automáticamente desde el descuento.</p>
        </div>
    </div>

    <div>
        <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Motivo de la oferta *</label>
        <textarea id="reason" name="reason" rows="3" required maxlength="500"
                  placeholder="Ej. Liquidación de temporada, promoción fin de semana…"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('reason', $offer?->reason) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="starts_at" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Inicio *</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required
                   value="{{ old('starts_at', $offer?->starts_at?->format('Y-m-d\\TH:i')) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label for="ends_at" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Fin *</label>
            <input id="ends_at" name="ends_at" type="datetime-local" required
                   value="{{ old('ends_at', $offer?->ends_at?->format('Y-m-d\\TH:i')) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear oferta' }}
    </button>
    <a href="{{ $cancelUrl ?? route('admin.offers.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

@push('scripts')
    @include('admin.partials.searchable-select-scripts')
    <script>
        (function () {
            const form = document.querySelector('[data-offer-form]');
            const productInput = document.getElementById('product_id');
            const discountInput = document.getElementById('discount_percent');
            const preview = document.getElementById('offer_price_preview');
            const hint = document.getElementById('product-price-hint');
            if (!form || !productInput || !discountInput || !preview) return;

            let productsMeta = {};
            try {
                productsMeta = JSON.parse(form.dataset.productsMeta || '{}');
            } catch (e) {
                productsMeta = {};
            }

            const syncPreview = () => {
                const meta = productsMeta[String(productInput.value)] || null;
                const discount = Number(discountInput.value);

                if (!meta) {
                    preview.value = '—';
                    if (hint) hint.textContent = 'Selecciona un producto para calcular el precio final.';
                    return;
                }

                const listPrice = Number(meta.price);
                if (hint) {
                    hint.textContent = `Precio de lista: ${listPrice.toFixed(2)} PEN.`;
                }

                if (!Number.isFinite(discount) || discount <= 0 || discount >= 100) {
                    preview.value = '—';
                    return;
                }

                const finalPrice = listPrice * (1 - (discount / 100));
                preview.value = finalPrice.toFixed(2) + ' PEN';
            };

            productInput.addEventListener('change', syncPreview);
            discountInput.addEventListener('input', syncPreview);
            syncPreview();
        })();
    </script>
@endpush

@php
    use App\Enums\Coupons\CouponType;

    /** @var \App\Models\Coupons\Coupon|null $coupon */
    $coupon = $coupon ?? null;
    $isEdit = $coupon !== null;

    $selectedCustomers = old('customer_ids', $coupon?->customers->pluck('id')->all() ?? []);
    $selectedProducts = old('product_ids', $coupon?->products->pluck('id')->all() ?? []);
    $selectedCategories = old('category_ids', $coupon?->categories->pluck('id')->all() ?? []);
    $selectedBrands = old('brand_ids', $coupon?->brands->pluck('id')->all() ?? []);
    $selectedPayments = old('payment_methods', $coupon?->allowedPaymentMethods() ?? []);
    $selectedCities = old('cities', $coupon?->allowedCities() ?? []);
    $defaultStarts = $coupon?->starts_at?->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i');
    $defaultEnds = $coupon?->ends_at?->format('Y-m-d\\TH:i') ?? now()->addMonth()->format('Y-m-d\\TH:i');
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

<div
    class="grid gap-6"
    x-data="{
        type: @js(old('type', $coupon?->type?->value ?? CouponType::Percent->value)),
        code: @js(old('code', $coupon?->code ?? '')),
        cities: @js($selectedCities),
        cityInput: '',
        generating: false,
        generateUrl: @js(route('admin.coupons.generate-code')),
        csrf: @js(csrf_token()),
        addCity() {
            const city = this.cityInput.trim();
            if (!city) return;
            const exists = this.cities.some((item) => item.toLowerCase() === city.toLowerCase());
            if (!exists) this.cities.push(city);
            this.cityInput = '';
        },
        removeCity(index) {
            this.cities.splice(index, 1);
        },
        async generateCode() {
            this.generating = true;
            try {
                const response = await fetch(this.generateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                if (data.code) this.code = data.code;
            } finally {
                this.generating = false;
            }
        },
    }"
    data-coupon-form
>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Título</label>
            <input id="name" name="name" type="text" maxlength="120"
                   value="{{ old('name', $coupon?->name) }}"
                   placeholder="Ej. Descuento de bienvenida"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label for="code" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Código *</label>
            <div class="flex gap-2">
                <input id="code" name="code" type="text" required maxlength="16" x-model="code"
                       @input="code = String(code || '').toUpperCase()"
                       placeholder="VERANO25"
                       class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text font-mono uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <button type="button" @click="generateCode()" :disabled="generating"
                        class="shrink-0 rounded border border-border px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors disabled:opacity-50">
                    <span x-text="generating ? '…' : 'Generar'">Generar</span>
                </button>
            </div>
            <p class="mt-1.5 text-xs text-muted">4 a 16 caracteres. Letras, números y guiones. Debe ser único.</p>
        </div>
    </div>

    <div>
        <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Motivo</label>
        <textarea id="reason" name="reason" rows="3" maxlength="500"
                  placeholder="Ej. Compensación por demora en un pedido"
                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('reason', $coupon?->reason) }}</textarea>
        <p class="mt-1.5 text-xs text-muted">Opcional. Si asignas el cupón a un cliente, el título y el motivo se incluyen en el correo.</p>
    </div>

    <div>
        <p class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Tipo de descuento *</p>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($types as $type)
                <label class="flex cursor-pointer flex-col gap-1 rounded-lg border border-border bg-secondary/40 p-4 has-[:checked]:border-primary has-[:checked]:bg-primary-soft">
                    <input type="radio" name="type" value="{{ $type->value }}" class="sr-only" x-model="type"
                           @checked(old('type', $coupon?->type?->value ?? CouponType::Percent->value) === $type->value)>
                    <span class="text-sm font-bold text-text">{{ $type->label() }}</span>
                    <span class="text-xs text-muted">
                        {{ $type === CouponType::Percent ? 'Un porcentaje sobre los productos aplicables.' : 'Un monto fijo en soles o dólares.' }}
                    </span>
                </label>
            @endforeach
        </div>
        <p class="mt-1.5 text-xs text-muted">Solo un tipo a la vez. En checkout también se aplica un solo cupón.</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="value" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                <span x-text="type === 'percent' ? 'Porcentaje *' : 'Monto *'">Valor *</span>
            </label>
            <input id="value" name="value" type="number" step="0.01" min="0.01" required
                   :max="type === 'percent' ? 100 : 999999.99"
                   value="{{ old('value', $coupon?->value) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div x-show="type === 'amount'" x-cloak>
            <label for="currency" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Moneda *</label>
            <select id="currency" name="currency"
                    class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="PEN" @selected(old('currency', $coupon?->currency ?? 'PEN') === 'PEN')>Soles (PEN)</option>
                <option value="USD" @selected(old('currency', $coupon?->currency) === 'USD')>Dólares (USD)</option>
            </select>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="starts_at" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Inicio *</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required
                   value="{{ old('starts_at', $defaultStarts) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
        <div>
            <label for="ends_at" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Fin *</label>
            <input id="ends_at" name="ends_at" type="datetime-local" required
                   value="{{ old('ends_at', $defaultEnds) }}"
                   class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
    </div>

    <label class="inline-flex items-center gap-3 rounded border border-border bg-secondary px-4 py-2.5 cursor-pointer w-full sm:w-auto">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
               @checked(old('is_active', $coupon?->is_active ?? true))>
        <span class="text-sm font-semibold text-text">Cupón activo</span>
    </label>

    <div class="border-t border-border pt-5 space-y-5">
        <div>
            <h2 class="text-sm font-title text-text">Clientes</h2>
            <p class="text-xs text-muted mt-1">Si no asignas clientes, el cupón es para todos. Si asignas uno o más, deja de ser público y cada cliente nuevo recibe un correo con el código.</p>
        </div>
        <x-multi-select
            name="customer_ids"
            label="Clientes asignados"
            placeholder="Todos los clientes (público)"
            :options="$customers"
            :selected="$selectedCustomers"
        />
    </div>

    <div class="border-t border-border pt-5 space-y-5">
        <div>
            <h2 class="text-sm font-title text-text">Reglas de catálogo</h2>
            <p class="text-xs text-muted mt-1">Vacío = aplica a todo el carrito. Si eliges productos, categorías o marcas, el descuento cubre esos productos (la categoría o marca incluye todos los de ahí).</p>
        </div>
        <div class="grid gap-5 lg:grid-cols-3">
            <x-multi-select name="product_ids" label="Productos" placeholder="Todos los productos" :options="$products" :selected="$selectedProducts" />
            <x-multi-select name="category_ids" label="Categorías" placeholder="Todas las categorías" :options="$categories" :selected="$selectedCategories" />
            <x-multi-select name="brand_ids" label="Marcas" placeholder="Todas las marcas" :options="$brands" :selected="$selectedBrands" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="min_products" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Mínimo de productos</label>
                <input id="min_products" name="min_products" type="number" min="1" max="999"
                       value="{{ old('min_products', $coupon?->min_products) }}"
                       class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            <div>
                <label for="min_subtotal" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Mínimo de precio</label>
                <div class="flex gap-2">
                    <input id="min_subtotal" name="min_subtotal" type="number" step="0.01" min="0.01"
                           value="{{ old('min_subtotal', $coupon?->min_subtotal) }}"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <select name="min_subtotal_currency"
                            class="w-28 rounded border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="PEN" @selected(old('min_subtotal_currency', $coupon?->min_subtotal_currency ?? 'PEN') === 'PEN')>Soles</option>
                        <option value="USD" @selected(old('min_subtotal_currency', $coupon?->min_subtotal_currency) === 'USD')>USD</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-border pt-5 space-y-5">
        <div>
            <h2 class="text-sm font-title text-text">Checkout</h2>
            <p class="text-xs text-muted mt-1">Vacío = todos los métodos o zonas. Las zonas se comparan con la ciudad de delivery.</p>
        </div>
        <div>
            <p class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Métodos de pago</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($paymentMethods as $method)
                    <label class="inline-flex items-center gap-2 rounded border border-border bg-secondary px-4 py-2.5 cursor-pointer">
                        <input type="checkbox" name="payment_methods[]" value="{{ $method->value }}"
                               class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-primary"
                               @checked(in_array($method->value, $selectedPayments, true))>
                        <span class="text-sm font-semibold text-text">{{ $method->label() }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Zonas de entrega (ciudades)</label>
            <div class="rounded border border-border bg-surface px-3 py-2">
                <div class="flex flex-wrap gap-2 mb-2" x-show="cities.length">
                    <template x-for="(city, index) in cities" :key="city + index">
                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary px-3 py-1 text-xs font-semibold text-text">
                            <input type="hidden" name="cities[]" :value="city">
                            <span x-text="city"></span>
                            <button type="button" class="text-muted hover:text-red-600" @click="removeCity(index)" aria-label="Quitar">&times;</button>
                        </span>
                    </template>
                </div>
                <input type="text" x-model="cityInput" @keydown.enter.prevent="addCity()"
                       placeholder="Escribe una ciudad y pulsa Enter (ej. Lima)"
                       class="w-full border-0 bg-transparent px-1 py-1 text-sm text-text placeholder-muted focus:outline-none">
            </div>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear cupón' }}
    </button>
    <a href="{{ route('admin.coupons.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

@push('scripts')
    <script>
        (function () {
            const form = document.querySelector('[data-coupon-form]');
            if (!form) return;

            document.querySelectorAll('[data-multi-select]').forEach((root) => {
                const trigger = root.querySelector('[data-multi-select-trigger]');
                const panel = root.querySelector('[data-multi-select-panel]');
                const summary = root.querySelector('[data-multi-select-summary]');
                const placeholder = root.dataset.placeholder || 'Seleccionar...';
                if (!trigger || !panel || !summary) return;

                const optionsList = root.querySelector('[data-multi-select-options]');
                if (optionsList && !root.querySelector('[data-multi-select-search]')) {
                    const searchWrap = document.createElement('div');
                    searchWrap.className = 'border-b border-border p-2';
                    searchWrap.innerHTML = '<input type="search" data-multi-select-search placeholder="Buscar…" class="w-full rounded border border-border bg-secondary px-3 py-2 text-sm text-text placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">';
                    panel.insertBefore(searchWrap, optionsList);
                }

                const close = () => {
                    panel.classList.add('hidden');
                    trigger.setAttribute('aria-expanded', 'false');
                };

                const updateSummary = () => {
                    const checked = Array.from(root.querySelectorAll('[data-multi-select-option]:checked'));
                    const labels = checked.map((input) => input.dataset.label).filter(Boolean);
                    if (labels.length === 0) {
                        summary.textContent = placeholder;
                        summary.classList.add('text-muted');
                        summary.classList.remove('text-text');
                        return;
                    }
                    summary.classList.remove('text-muted');
                    summary.classList.add('text-text');
                    summary.textContent = labels.length === 1
                        ? labels[0]
                        : (labels.length <= 3 ? labels.join(', ') : labels.length + ' seleccionados');
                };

                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const isOpen = !panel.classList.contains('hidden');
                    document.querySelectorAll('[data-multi-select-panel]').forEach((other) => {
                        if (other !== panel) other.classList.add('hidden');
                    });
                    if (isOpen) close();
                    else {
                        panel.classList.remove('hidden');
                        trigger.setAttribute('aria-expanded', 'true');
                        root.querySelector('[data-multi-select-search]')?.focus();
                    }
                });

                root.querySelectorAll('[data-multi-select-option]').forEach((input) => {
                    input.addEventListener('change', updateSummary);
                    input.addEventListener('click', (event) => event.stopPropagation());
                });

                const search = root.querySelector('[data-multi-select-search]');
                if (search) {
                    search.addEventListener('input', () => {
                        const term = search.value.trim().toLowerCase();
                        root.querySelectorAll('[data-multi-select-item]').forEach((item) => {
                            const label = (item.querySelector('[data-label]')?.dataset.label
                                || item.textContent
                                || '').toLowerCase();
                            item.classList.toggle('hidden', term !== '' && !label.includes(term));
                        });
                    });
                    search.addEventListener('click', (event) => event.stopPropagation());
                }

                panel.addEventListener('click', (event) => event.stopPropagation());
                document.addEventListener('click', () => close());
                updateSummary();
            });
        })();
    </script>
@endpush

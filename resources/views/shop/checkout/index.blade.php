@extends('layouts.shop')

@section('title', 'Checkout — '.config('app.name'))

@section('content')
@php
    $fieldClass = 'w-full rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm transition focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $labelClass = 'mb-1.5 block text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500';
    $chargeCurrencySymbol = \App\Support\Currency::symbol($currency ?? 'PEN');
@endphp

<div class="mx-auto max-w-6xl px-4 py-10 md:py-14 text-neutral-900 font-title">
        <div class="mb-8 md:mb-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-500">Pago seguro</p>
            <h1 class="mt-2 text-3xl md:text-4xl font-black uppercase tracking-wide text-neutral-900">Checkout</h1>
            <p class="mt-2 max-w-xl text-sm text-neutral-500">Tarjeta de crédito/débito o Yape · Culqi</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if (! $culqiFake && ! $culqiPublicKey)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Configura <code class="font-mono">CULQI_PUBLIC_KEY</code> y <code class="font-mono">CULQI_SECRET_KEY</code>,
                o usa <code class="font-mono">CULQI_FAKE=true</code> para probar sin llaves.
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-7">
                <form
                    id="checkout-form"
                    method="POST"
                    action="{{ route('shop.checkout.pay', absolute: false) }}"
                    data-submit-lock="async"
                    class="space-y-5 rounded-3xl border border-neutral-200/80 bg-white/95 p-5 sm:p-7 shadow-[0_20px_60px_-30px_rgba(0,0,0,0.45)] backdrop-blur"
                >
                    @csrf
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                    <input type="hidden" name="culqi_token" id="culqi_token" value="">
                    <input type="hidden" name="device_finger_print_id" id="device_finger_print_id" value="">
                    <input type="hidden" name="coupon_code" id="coupon_code" value="{{ $couponQuote?->coupon->code }}">

                    {{-- Comprador --}}
                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-xs font-black text-white">1</span>
                            <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-900">Tus datos</h2>
                        </div>

                        @guest
                            <p class="text-sm text-neutral-600">
                                No necesitas cuenta. Usa tu correo para asociar la compra.
                            </p>
                        @endguest

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}" for="customer_email">Correo *</label>
                                <input id="customer_email" name="customer_email" type="email" required
                                       value="{{ old('customer_email', $user?->email) }}"
                                       @disabled($user !== null)
                                       class="{{ $fieldClass }} {{ $user ? 'bg-neutral-100 cursor-not-allowed' : '' }}">
                                @if ($user)
                                    <input type="hidden" name="customer_email" value="{{ $user->email }}">
                                @endif
                            </div>
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}" for="customer_document">Documento (DNI) *</label>
                                <input id="customer_document" name="customer_document" required
                                       value="{{ old('customer_document', $profile?->document) }}"
                                       @disabled($profile?->document)
                                       class="{{ $fieldClass }} {{ $profile?->document ? 'bg-neutral-100 cursor-not-allowed' : '' }}">
                                @if ($profile?->document)
                                    <input type="hidden" name="customer_document" value="{{ $profile->document }}">
                                @endif
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="first_name">Nombre *</label>
                                <input id="first_name" name="first_name" required value="{{ old('first_name', $profile?->first_name) }}"
                                       class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="last_name">Apellido *</label>
                                <input id="last_name" name="last_name" required value="{{ old('last_name', $profile?->last_name) }}"
                                       class="{{ $fieldClass }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}" for="phone">Teléfono *</label>
                                <input id="phone" name="phone" required value="{{ old('phone', $profile?->phone) }}" placeholder="999999999"
                                       class="{{ $fieldClass }}">
                            </div>
                        </div>
                    </section>

                    {{-- Entrega --}}
                    <section class="space-y-4 border-t border-neutral-100 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-xs font-black text-white">2</span>
                            <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-900">Entrega</h2>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                'pickup' => ['label' => 'Recojo en tienda', 'hint' => 'Retiras en Motoworld'],
                                'delivery' => ['label' => 'Delivery', 'hint' => 'Enviamos a tu dirección'],
                            ] as $value => $meta)
                                <label class="group relative flex cursor-pointer flex-col gap-1 rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4 transition hover:border-orange-400 has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50 has-[:checked]:shadow-sm">
                                    <input
                                        type="radio"
                                        name="fulfillment_method"
                                        value="{{ $value }}"
                                        class="sr-only"
                                        data-fulfillment-option
                                        @checked(old('fulfillment_method', 'delivery') === $value)
                                    >
                                    <span class="text-sm font-bold text-neutral-900">{{ $meta['label'] }}</span>
                                    <span class="text-xs text-neutral-500">{{ $meta['hint'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div id="delivery-address-fields" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}" for="address_line1">Dirección *</label>
                                <input id="address_line1" name="address_line1" value="{{ old('address_line1') }}"
                                       class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="department_id">Departamento *</label>
                                <select id="department_id" name="department_id" class="{{ $fieldClass }}" data-selected="{{ old('department_id') }}">
                                    <option value="">Selecciona departamento</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="province_id">Provincia *</label>
                                <select id="province_id" name="province_id" class="{{ $fieldClass }}" data-selected="{{ old('province_id') }}">
                                    <option value="">Selecciona provincia</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="district_id">Distrito *</label>
                                <select id="district_id" name="district_id" class="{{ $fieldClass }}" data-selected="{{ old('district_id') }}">
                                    <option value="">Selecciona distrito</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="postal_code">C.P.</label>
                                <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}"
                                       class="{{ $fieldClass }}">
                            </div>
                        </div>
                    </section>

                    {{-- Pago: un solo formulario de tarjeta (crédito+débito) + Yape aparte --}}
                    <section class="space-y-4 border-t border-neutral-100 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-xs font-black text-white">3</span>
                            <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-900">Pago</h2>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2" id="payment-method-tabs">
                            <button type="button" data-pay-method="card"
                                    class="rounded-2xl border border-orange-600 bg-orange-50 px-4 py-3 text-left text-sm font-bold text-neutral-900">
                                Tarjeta
                                <span class="mt-0.5 block text-xs font-normal text-neutral-500">Crédito o débito · Culqi Checkout</span>
                            </button>
                            <button type="button" data-pay-method="yape"
                                    class="rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-left text-sm font-bold text-neutral-900">
                                Yape
                                <span class="mt-0.5 block text-xs font-normal text-neutral-500">App Yape · Culqi Checkout</span>
                            </button>
                        </div>

                        <div id="card-payment-panel" class="space-y-3">
                            <div class="space-y-3 rounded-2xl border border-neutral-200 bg-gradient-to-br from-neutral-50 to-white p-4">
                                <p class="text-xs text-neutral-500">
                                    Completa tus datos de comprador arriba y pulsa Pagar.
                                    @if ($culqiFake)
                                        Modo fake: no se envía a Culqi.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div id="yape-payment-panel" class="hidden space-y-3">
                            <div class="space-y-3 rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-4">
                                <p class="text-xs text-neutral-500">
                                    Completa tus datos de comprador arriba y pulsa Pagar.
                                    @if ($culqiFake)
                                        Modo fake: no se envía a Culqi.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </section>

                    <p id="payment-error" class="hidden rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700"></p>

                    <button
                        type="submit"
                        id="pay-button"
                        class="group relative w-full overflow-hidden rounded-2xl bg-orange-600 px-5 py-4 text-sm font-black uppercase tracking-[0.16em] text-white shadow-lg shadow-orange-600/25 transition hover:bg-orange-500 disabled:opacity-50"
                    >
                        <span class="relative z-10" id="pay-button-label">Pagar {{ $chargeCurrencySymbol }} {{ number_format($total, 2) }}</span>
                    </button>

                    <p class="text-center text-[11px] text-neutral-400">
                        Procesado por Culqi · Datos de tarjeta tokenizados (PCI)
                    </p>
                </form>
            </div>

            <aside class="lg:col-span-5 space-y-4 lg:sticky lg:top-24">
                <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-black uppercase tracking-[0.14em] text-neutral-900">Resumen</h2>
                    <div class="divide-y divide-neutral-100">
                        @foreach ($lines as $line)
                            @php
                                $lineCurrencySymbol = \App\Support\Currency::symbol($line['currency'] ?? 'PEN');
                                $img = $line['product']->catalogImageUrl();
                            @endphp
                            <div class="flex gap-3 py-3 first:pt-0 last:pb-0">
                                @if ($img)
                                    <img src="{{ $img }}" alt="" class="h-14 w-14 rounded-xl object-cover border border-neutral-200">
                                @else
                                    <div class="h-14 w-14 rounded-xl bg-neutral-100 border border-neutral-200"></div>
                                @endif
                                <div class="min-w-0 flex-1 font-secondary">
                                    <p class="truncate text-sm font-bold text-neutral-900">{{ $line['product']->name }}</p>
                                    <p class="text-xs text-neutral-500">× {{ $line['quantity'] }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-bold text-orange-600">
                                    {{ $lineCurrencySymbol }} {{ number_format($line['line_total'], 2) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm" data-checkout-coupon>
                    <h2 class="mb-3 text-sm font-black uppercase tracking-[0.14em] text-neutral-900">Cupón de descuento</h2>
                    <div class="flex gap-2">
                        <input type="text" id="checkout-coupon-input" maxlength="16"
                               value="{{ $couponQuote?->coupon->code }}"
                               placeholder="Código"
                               class="w-full rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-mono uppercase text-neutral-900 placeholder:text-neutral-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20">
                        <button type="button" id="checkout-coupon-apply"
                                class="rounded-xl bg-neutral-900 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white hover:bg-neutral-800">
                            Aplicar
                        </button>
                    </div>
                    <p id="checkout-coupon-message" class="mt-2 hidden text-sm"></p>
                    <div id="checkout-coupon-applied" class="mt-3 {{ $couponQuote ? '' : 'hidden' }} flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                        <span id="checkout-coupon-applied-label">{{ $couponQuote?->appliedLabel() }}</span>
                        <button type="button" id="checkout-coupon-remove" class="text-xs font-bold uppercase text-emerald-700 hover:underline">Quitar</button>
                    </div>
                </div>

                <div class="rounded-3xl border border-neutral-800 bg-neutral-950 p-5 text-white shadow-sm" data-checkout-totals>
                    @if ($totals->hasPen() || $totals->hasUsd())
                        <div class="space-y-2 text-sm text-white/70">
                            @if ($totals->hasPen())
                                <div class="flex justify-between"><span>Subtotal soles</span><span class="text-white">S/ {{ number_format($totals->totalPen, 2) }}</span></div>
                            @endif
                            @if ($totals->hasUsd())
                                <div class="flex justify-between"><span>Subtotal dólares</span><span class="text-white">$ {{ number_format($totals->totalUsd, 2) }}</span></div>
                            @endif
                        </div>
                    @endif

                    <div id="checkout-shipping-row" class="mt-3 {{ ($charge->shipping ?? 0) > 0 ? '' : 'hidden' }}">
                        <div class="flex justify-between gap-3 text-sm text-white/70">
                            <span data-shipping-label>{{ $charge->shippingQuote->label() ?? 'Envío' }}</span>
                            <span data-shipping-amount
                                  data-shipping-pen="{{ number_format($charge->shippingQuote->amountPen ?? 0, 2, '.', '') }}"
                                  data-shipping-usd="{{ number_format($charge->shippingQuote->amountUsd ?? 0, 2, '.', '') }}">
                                @if (($charge->shipping ?? 0) > 0)
                                    {{ \App\Support\Currency::format($charge->shipping, $charge->currency) }}
                                @endif
                            </span>
                        </div>
                    </div>

                    <div id="checkout-discount-row" class="mt-3 {{ $couponQuote ? '' : 'hidden' }}">
                        <div class="flex justify-between gap-3 text-sm text-emerald-300">
                            <span>
                                Cupón <span data-discount-code>{{ $couponQuote?->coupon->code }}</span>
                                <span data-discount-value>{{ $couponQuote ? '· '.$couponQuote->coupon->discountLabel() : '' }}</span>
                            </span>
                            <span data-discount-amount
                                  data-discount-pen="{{ $couponQuote ? number_format($couponQuote->discountPen, 2, '.', '') : '' }}"
                                  data-discount-usd="{{ $couponQuote ? number_format($couponQuote->discountUsd, 2, '.', '') : '' }}">
                                @if ($couponQuote)
                                    − {{ \App\Support\Currency::format($couponQuote->discountAmount, $couponQuote->currency) }}
                                @endif
                            </span>
                        </div>
                        <p id="checkout-discount-note" class="mt-1 text-[11px] text-emerald-300/70 {{ $couponQuote?->conversionNote() ? '' : 'hidden' }}">
                            {{ $couponQuote?->conversionNote() }}
                        </p>
                    </div>

                    @if ($totals->hasRate)
                        <div class="mt-4 border-t border-white/10 pt-4 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-bold uppercase tracking-widest text-white/60">Total</span>
                                <div class="inline-flex rounded-full border border-white/15 p-0.5 text-[11px] font-bold uppercase">
                                    <button type="button" data-total-currency="PEN" class="rounded-full px-2.5 py-1 bg-orange-600 text-white">Soles</button>
                                    <button type="button" data-total-currency="USD" class="rounded-full px-2.5 py-1 text-white/60">USD</button>
                                </div>
                            </div>
                            <p class="text-right text-2xl font-black" data-grand-total
                               data-pen="{{ number_format($charge->grandPen, 2, '.', '') }}"
                               data-usd="{{ number_format($charge->grandUsd, 2, '.', '') }}"
                               data-base-pen="{{ number_format($totals->grandPen, 2, '.', '') }}"
                               data-base-usd="{{ number_format($totals->grandUsd, 2, '.', '') }}">
                                S/ {{ number_format($charge->grandPen, 2) }}
                            </p>
                        </div>
                    @else
                        <div class="mt-4 border-t border-white/10 pt-4 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-widest text-white/60">Total</span>
                            <span class="text-2xl font-black" data-simple-total>{{ $chargeCurrencySymbol }} {{ number_format($total, 2) }}</span>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
</div>

<div id="checkout-loading" class="fixed inset-0 z-40 hidden items-center justify-center bg-neutral-950/75 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-sm rounded-3xl bg-white px-8 py-10 text-center shadow-2xl">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-orange-200 border-t-orange-600"></div>
        <p id="checkout-loading-title" class="mt-5 text-sm font-black uppercase tracking-[0.16em] text-neutral-900">Procesando pago</p>
        <p id="checkout-loading-text" class="mt-2 text-sm text-neutral-500">No cierres esta ventana ni pulses pagar otra vez.</p>
    </div>
</div>

@if ($totals->hasRate)
<script>
(function () {
    const root = document.querySelector('[data-checkout-totals]');
    if (!root) return;
    const totalEl = root.querySelector('[data-grand-total]');
    const buttons = root.querySelectorAll('[data-total-currency]');
    if (!totalEl || !buttons.length) return;
    const format = (value) => Number(value).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const setCurrency = (currency) => {
        const isPen = currency === 'PEN';
        totalEl.textContent = (isPen ? 'S/ ' : '$ ') + format(totalEl.dataset[isPen ? 'pen' : 'usd']);
        const discountAmountEl = root.querySelector('[data-discount-amount]');
        const discountPen = discountAmountEl?.dataset.discountPen;
        const discountUsd = discountAmountEl?.dataset.discountUsd;
        if (discountAmountEl && discountPen && discountUsd) {
            discountAmountEl.textContent = '− ' + (isPen ? 'S/ ' : '$ ') + format(isPen ? discountPen : discountUsd);
        }
        const shippingAmountEl = root.querySelector('[data-shipping-amount]');
        const shippingPen = shippingAmountEl?.dataset.shippingPen;
        const shippingUsd = shippingAmountEl?.dataset.shippingUsd;
        if (shippingAmountEl && shippingPen && shippingUsd) {
            shippingAmountEl.textContent = (isPen ? 'S/ ' : '$ ') + format(isPen ? shippingPen : shippingUsd);
        }
        buttons.forEach((button) => {
            const active = button.dataset.totalCurrency === currency;
            button.classList.toggle('bg-orange-600', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-white/60', !active);
        });
    };
    buttons.forEach((button) => button.addEventListener('click', () => setCurrency(button.dataset.totalCurrency)));
})();
</script>
@endif

<script>
window.MotoworldCheckout = {
    publicKey: @json($culqiPublicKey),
    amount: {{ (float) $amount }},
    amountCents: {{ (int) $amountCents }},
    currency: @json($currency ?? 'PEN'),
    fake: @json((bool) $culqiFake),
    yapeMaxCents: 200000,
    yapeTokenUrl: @json($yapeTokenUrl ?? '/checkout/token-yape'),
    applyCouponUrl: @json($applyCouponUrl ?? '/checkout/cupon'),
    removeCouponUrl: @json($removeCouponUrl ?? '/checkout/cupon'),
    quoteUrl: @json($quoteUrl ?? '/checkout/cotizar'),
    provincesUrl: @json($locationUrls['provinces'] ?? '/ubicaciones/departamentos/__department__/provincias'),
    districtsUrl: @json($locationUrls['districts'] ?? '/ubicaciones/provincias/__province__/distritos'),
};
</script>

@if (! $culqiFake && $culqiPublicKey)
<script src="https://checkout.culqi.com/js/v4"></script>
<script src="https://3ds.culqi.com"></script>
@endif

<script>
(function () {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const cfg = window.MotoworldCheckout;
    const errorEl = document.getElementById('payment-error');
    const payButton = document.getElementById('pay-button');
    const paymentMethodInput = document.getElementById('payment_method');
    const tokenInput = document.getElementById('culqi_token');
    const deviceInput = document.getElementById('device_finger_print_id');
    const cardPanel = document.getElementById('card-payment-panel');
    const yapePanel = document.getElementById('yape-payment-panel');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || form.querySelector('input[name="_token"]')?.value
        || '';
    const loadingEl = document.getElementById('checkout-loading');

    let selectedPayMethod = 'card';
    let pending3DS = null;
    let paying = false;

    const deliveryFields = document.getElementById('delivery-address-fields');
    const addressLine = document.getElementById('address_line1');
    const departmentSelect = document.getElementById('department_id');
    const provinceSelect = document.getElementById('province_id');
    const districtSelect = document.getElementById('district_id');

    function selectedFulfillment() {
        return form.querySelector('input[name="fulfillment_method"]:checked')?.value || 'delivery';
    }

    function toggleFulfillment() {
        const isDelivery = selectedFulfillment() === 'delivery';
        if (deliveryFields) deliveryFields.classList.toggle('hidden', !isDelivery);
        if (addressLine) addressLine.required = isDelivery;
        if (departmentSelect) departmentSelect.required = isDelivery;
        if (provinceSelect) provinceSelect.required = isDelivery;
        if (districtSelect) districtSelect.required = isDelivery;
    }

    form.querySelectorAll('[data-fulfillment-option]').forEach((el) => {
        el.addEventListener('change', toggleFulfillment);
    });
    toggleFulfillment();

    function setPayingLock(locked) {
        paying = locked;
        payButton.disabled = locked;
        if (!locked && typeof window.unlockSubmitLock === 'function') {
            window.unlockSubmitLock(form);
        }
    }

    function setLoading(isLoading, title, detail) {
        setPayingLock(isLoading);
        form.classList.toggle('pointer-events-none', isLoading);
        form.classList.toggle('opacity-40', isLoading);
        if (loadingEl) {
            loadingEl.classList.toggle('hidden', !isLoading);
            loadingEl.classList.toggle('flex', isLoading);
            const titleEl = document.getElementById('checkout-loading-title');
            const textEl = document.getElementById('checkout-loading-text');
            if (titleEl && title) titleEl.textContent = title;
            if (textEl && detail) textEl.textContent = detail;
        }
    }

    function isCulqiCheckoutOpen() {
        const nodes = document.querySelectorAll(
            'iframe[src*="culqi.com"], #culqi-checkout, #culqi_checkout, #culqiModal, .culqi-overlay'
        );
        return Array.from(nodes).some((el) => {
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) {
                return false;
            }
            const rect = el.getBoundingClientRect();
            return rect.width > 80 && rect.height > 80;
        });
    }

    function watchCulqiClosed(onClosed) {
        let seenOpen = false;
        let stopped = false;
        const originalClose = typeof Culqi.close === 'function' ? Culqi.close.bind(Culqi) : null;

        const cleanup = () => {
            clearInterval(timer);
            if (originalClose) {
                Culqi.close = originalClose;
            }
        };

        const fire = () => {
            if (stopped) return;
            stopped = true;
            cleanup();
            onClosed();
        };

        if (originalClose) {
            Culqi.close = function () {
                const result = originalClose.apply(this, arguments);
                fire();
                return result;
            };
        }

        const timer = setInterval(() => {
            if (stopped) return;
            if (isCulqiCheckoutOpen()) {
                seenOpen = true;
                return;
            }
            if (seenOpen) fire();
        }, 250);

        return () => {
            stopped = true;
            cleanup();
        };
    }

    function showError(message) {
        setLoading(false);
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    }

    function setPayMethod(method) {
        selectedPayMethod = method;
        paymentMethodInput.value = method;

        document.querySelectorAll('[data-pay-method]').forEach((btn) => {
            const active = btn.dataset.payMethod === method;
            btn.classList.toggle('border-orange-600', active);
            btn.classList.toggle('bg-orange-50', active);
            btn.classList.toggle('border-neutral-200', !active);
            btn.classList.toggle('bg-white', !active);
        });

        cardPanel.classList.toggle('hidden', method !== 'card');
        yapePanel.classList.toggle('hidden', method !== 'yape');
    }

    document.querySelectorAll('[data-pay-method]').forEach((btn) => {
        btn.addEventListener('click', () => setPayMethod(btn.dataset.payMethod));
    });
    setPayMethod('card');

    const couponInput = document.getElementById('checkout-coupon-input');
    const couponHidden = document.getElementById('coupon_code');
    const couponMessage = document.getElementById('checkout-coupon-message');
    const couponApplied = document.getElementById('checkout-coupon-applied');
    const couponAppliedLabel = document.getElementById('checkout-coupon-applied-label');
    const couponApplyBtn = document.getElementById('checkout-coupon-apply');
    const couponRemoveBtn = document.getElementById('checkout-coupon-remove');
    const discountRow = document.getElementById('checkout-discount-row');
    const discountNote = document.getElementById('checkout-discount-note');
    const payLabel = document.getElementById('pay-button-label');
    const grandTotalEl = document.querySelector('[data-grand-total]');
    const simpleTotalEl = document.querySelector('[data-simple-total]');

    function formatMoney(amount, currency) {
        const symbol = currency === 'USD' ? '$' : 'S/';
        return symbol + ' ' + Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function couponContext() {
        return {
            coupon_code: (couponInput?.value || '').trim(),
            fulfillment_method: selectedFulfillment(),
            department_id: departmentSelect?.value || '',
            province_id: provinceSelect?.value || '',
            district_id: districtSelect?.value || '',
            payment_method: paymentMethodInput?.value || 'card',
        };
    }

    function applyTotals(data, applied) {
        cfg.amount = Number(data.total || 0);
        cfg.amountCents = Number(data.amount_cents || Math.round(cfg.amount * 100));
        cfg.currency = data.currency || cfg.currency;
        if (payLabel) payLabel.textContent = 'Pagar ' + formatMoney(cfg.amount, cfg.currency);
        if (grandTotalEl) {
            if (data.grand_pen != null) grandTotalEl.dataset.pen = Number(data.grand_pen).toFixed(2);
            if (data.grand_usd != null) grandTotalEl.dataset.usd = Number(data.grand_usd).toFixed(2);
            const active = document.querySelector('[data-total-currency].bg-orange-600')?.dataset.totalCurrency || 'PEN';
            const isPen = active === 'PEN';
            grandTotalEl.textContent = (isPen ? 'S/ ' : '$ ') + Number(isPen ? grandTotalEl.dataset.pen : grandTotalEl.dataset.usd).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (simpleTotalEl) simpleTotalEl.textContent = formatMoney(cfg.amount, cfg.currency);
        if (couponHidden) couponHidden.value = applied ? (data.code || '') : '';
        if (discountRow) {
            discountRow.classList.toggle('hidden', !applied);
            const codeEl = discountRow.querySelector('[data-discount-code]');
            const valueEl = discountRow.querySelector('[data-discount-value]');
            const amountEl = discountRow.querySelector('[data-discount-amount]');
            if (codeEl) codeEl.textContent = data.code || '';
            if (valueEl) valueEl.textContent = applied && data.value_label ? '· ' + data.value_label : '';
            if (amountEl) {
                amountEl.dataset.discountPen = applied && data.discount_pen != null ? Number(data.discount_pen).toFixed(2) : '';
                amountEl.dataset.discountUsd = applied && data.discount_usd != null ? Number(data.discount_usd).toFixed(2) : '';
                const currency = document.querySelector('[data-total-currency].bg-orange-600')?.dataset.totalCurrency
                    || data.currency
                    || 'PEN';
                const amount = currency === 'USD'
                    ? (data.discount_usd ?? data.discount_amount)
                    : (data.discount_pen ?? data.discount_amount);
                amountEl.textContent = applied ? '− ' + formatMoney(amount, currency) : '';
            }
        }
        if (discountNote) {
            discountNote.textContent = applied ? (data.conversion_note || '') : '';
            discountNote.classList.toggle('hidden', !(applied && data.conversion_note));
        }
        if (couponApplied) couponApplied.classList.toggle('hidden', !applied);
        if (couponAppliedLabel) couponAppliedLabel.textContent = applied ? (data.applied_label || ('Cupón ' + data.code)) : '';
        const shippingRow = document.getElementById('checkout-shipping-row');
        const shippingLabelEl = document.querySelector('[data-shipping-label]');
        const shippingAmountEl = document.querySelector('[data-shipping-amount]');
        const hasShipping = Number(data.shipping_amount || 0) > 0;
        if (shippingRow) shippingRow.classList.toggle('hidden', !hasShipping);
        if (shippingLabelEl) shippingLabelEl.textContent = data.shipping_label || 'Envío';
        if (shippingAmountEl) {
            shippingAmountEl.dataset.shippingPen = data.shipping_pen != null ? Number(data.shipping_pen).toFixed(2) : '0.00';
            shippingAmountEl.dataset.shippingUsd = data.shipping_usd != null ? Number(data.shipping_usd).toFixed(2) : '0.00';
            const currency = document.querySelector('[data-total-currency].bg-orange-600')?.dataset.totalCurrency
                || data.currency
                || 'PEN';
            const amount = currency === 'USD' ? (data.shipping_usd ?? data.shipping_amount) : (data.shipping_pen ?? data.shipping_amount);
            shippingAmountEl.textContent = hasShipping ? formatMoney(amount, currency) : '';
        }
    }

    function showCouponMessage(text, isError) {
        if (!couponMessage) return;
        couponMessage.textContent = text || '';
        couponMessage.classList.toggle('hidden', !text);
        couponMessage.classList.toggle('text-red-600', !!isError);
        couponMessage.classList.toggle('text-emerald-700', !isError);
    }

    async function applyCoupon() {
        const code = (couponInput?.value || '').trim();
        if (!code) {
            showCouponMessage('Ingresa un código de cupón.', true);
            return;
        }
        showCouponMessage('');
        try {
            const body = new FormData();
            Object.entries(couponContext()).forEach(([key, value]) => body.set(key, String(value || '')));
            const response = await fetch(cfg.applyCouponUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(firstError(data) || data.errors?.coupon_code?.[0] || 'No se pudo aplicar el cupón.');
            }
            applyTotals(data, true);
            showCouponMessage(data.message || 'Cupón aplicado.', false);
        } catch (err) {
            showCouponMessage(err?.message || 'No se pudo aplicar el cupón.', true);
        }
    }

    async function removeCoupon() {
        try {
            const params = new URLSearchParams(couponContext());
            const response = await fetch(cfg.removeCouponUrl + '?' + params.toString(), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(firstError(data));
            if (couponInput) couponInput.value = '';
            applyTotals(data, false);
            showCouponMessage(data.message || 'Cupón quitado.', false);
        } catch (err) {
            showCouponMessage(err?.message || 'No se pudo quitar el cupón.', true);
        }
    }

    couponApplyBtn?.addEventListener('click', applyCoupon);
    couponRemoveBtn?.addEventListener('click', removeCoupon);
    couponInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyCoupon();
        }
    });

    async function fillSelect(select, url, selected, placeholder) {
        if (!select) return;
        const current = selected ?? select.dataset.selected ?? '';
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        if (!url) return;
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json().catch(() => ({ data: [] }));
            (payload.data || []).forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                if (String(item.id) === String(current)) option.selected = true;
                select.appendChild(option);
            });
        } catch (err) {
            console.warn('No se pudieron cargar ubicaciones', err);
        }
        select.dataset.selected = select.value || '';
    }

    async function loadProvinces() {
        const departmentId = departmentSelect?.value;
        if (!provinceSelect) return;
        if (!departmentId) {
            provinceSelect.innerHTML = '<option value="">Selecciona provincia</option>';
            if (districtSelect) districtSelect.innerHTML = '<option value="">Selecciona distrito</option>';
            return;
        }
        const url = String(cfg.provincesUrl || '').replace('__department__', encodeURIComponent(departmentId));
        await fillSelect(provinceSelect, url, provinceSelect.value || provinceSelect.dataset.selected, 'Selecciona provincia');
        await loadDistricts();
    }

    async function loadDistricts() {
        const provinceId = provinceSelect?.value;
        if (!districtSelect) return;
        if (!provinceId) {
            districtSelect.innerHTML = '<option value="">Selecciona distrito</option>';
            return;
        }
        const url = String(cfg.districtsUrl || '').replace('__province__', encodeURIComponent(provinceId));
        await fillSelect(districtSelect, url, districtSelect.value || districtSelect.dataset.selected, 'Selecciona distrito');
    }

    async function refreshQuote() {
        if (!cfg.quoteUrl) return;
        try {
            const body = new FormData();
            Object.entries(couponContext()).forEach(([key, value]) => body.set(key, String(value || '')));
            const response = await fetch(cfg.quoteUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) return;
            applyTotals(data, !!data.valid && !!data.code);
        } catch (err) {
            console.warn('No se pudo cotizar el envío', err);
        }
    }

    departmentSelect?.addEventListener('change', async () => {
        if (provinceSelect) provinceSelect.dataset.selected = '';
        if (districtSelect) districtSelect.dataset.selected = '';
        await loadProvinces();
        refreshQuote();
    });
    provinceSelect?.addEventListener('change', async () => {
        if (districtSelect) districtSelect.dataset.selected = '';
        await loadDistricts();
        refreshQuote();
    });
    districtSelect?.addEventListener('change', refreshQuote);
    form.querySelectorAll('[data-fulfillment-option]').forEach((el) => {
        el.addEventListener('change', refreshQuote);
    });
    document.querySelectorAll('[data-pay-method]').forEach((btn) => {
        btn.addEventListener('click', refreshQuote);
    });

    if (departmentSelect?.value) {
        loadProvinces().then(() => refreshQuote());
    }

    function randomId(prefix) {
        return prefix + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
    }

    function customerEmail() {
        return (document.getElementById('customer_email')?.value || '').trim();
    }

    function isNetworkError(err) {
        return /failed to fetch|networkerror|load failed|network request failed/i.test(String(err?.message || err || ''));
    }

    function sameOriginPath(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            return parsed.pathname + parsed.search;
        } catch (e) {
            return url;
        }
    }

    async function openCulqiCheckout(method) {
        const isYape = method === 'yape';
        if (cfg.fake) return randomId(isYape ? 'ype_test_fake_' : 'tkn_test_fake_');
        if (!cfg.publicKey) throw new Error('Falta CULQI_PUBLIC_KEY.');
        if (typeof Culqi === 'undefined' || typeof Culqi.open !== 'function') {
            throw new Error('No se pudo cargar Culqi Checkout. Recarga la página.');
        }

        const email = customerEmail();
        if (!email) throw new Error('Ingresa tu correo.');
        if (isYape && cfg.currency && cfg.currency !== 'PEN') {
            throw new Error('Yape solo acepta pagos en soles.');
        }
        if (isYape && Number(cfg.amountCents) > Number(cfg.yapeMaxCents)) {
            throw new Error('Yape acepta un máximo de S/ 2,000.00.');
        }

        Culqi.publicKey = String(cfg.publicKey).trim();
        Culqi.settings({
            title: 'Motoworld',
            currency: cfg.currency || 'PEN',
            amount: Number(cfg.amountCents),
            description: 'Compra Motoworld',
        });
        Culqi.options({
            lang: 'es',
            installments: false,
            paymentMethods: {
                tarjeta: !isYape,
                yape: isYape,
                bancaMovil: false,
                agente: false,
                billetera: false,
                cuotealo: false,
            },
            style: {
                logo: window.location.origin + '/images/logo.png',
            },
        });

        return new Promise((resolve, reject) => {
            let settled = false;
            let stopWatching = () => {};
            const finish = (fn, value) => {
                if (settled) return;
                settled = true;
                stopWatching();
                fn(value);
            };

            window.culqi = function () {
                const tokenId = Culqi.token && Culqi.token.id;
                if (tokenId) {
                    finish(resolve, tokenId);
                    try { Culqi.close(); } catch (e) {}
                    return;
                }
                const err = Culqi.error || {};
                const message = err.user_message || err.merchant_message || err.mensaje;
                if (message) {
                    finish(reject, new Error(message));
                    return;
                }
                finish(reject, { cancelled: true });
            };

            stopWatching = watchCulqiClosed(() => finish(reject, { cancelled: true }));

            try {
                Culqi.open();
            } catch (err) {
                finish(reject, err instanceof Error ? err : new Error('No se pudo abrir Culqi Checkout.'));
            }
        });
    }

    async function generateDeviceId() {
        if (cfg.fake || typeof Culqi3DS === 'undefined' || typeof Culqi3DS.generateDevice !== 'function') {
            return '';
        }
        try {
            Culqi3DS.publicKey = cfg.publicKey;
            const id = await Culqi3DS.generateDevice();
            return id || '';
        } catch (err) {
            console.warn('Culqi3DS.generateDevice', err);
            return '';
        }
    }

    function firstError(data) {
        if (!data) return 'No se pudo procesar el pago.';
        if (data.message) return data.message;
        const errors = data.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors)[0];
            if (Array.isArray(first) && first[0]) return first[0];
            if (typeof first === 'string') return first;
        }
        return 'No se pudo procesar el pago.';
    }

    async function postJson(url, extra = {}) {
        const body = new FormData(form);
        Object.entries(extra).forEach(([key, value]) => {
            if (value === undefined || value === null) return;
            if (typeof value === 'object') {
                Object.entries(value).forEach(([nested, nestedValue]) => {
                    if (nestedValue != null && nestedValue !== '') {
                        body.set(key + '[' + nested + ']', String(nestedValue));
                    }
                });
                return;
            }
            body.set(key, String(value));
        });

        let response;
        try {
            response = await fetch(sameOriginPath(url), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body,
            });
        } catch (err) {
            if (isNetworkError(err)) {
                throw new Error('No se pudo contactar al servidor de pago. Revisa tu conexión e inténtalo de nuevo.');
            }
            throw err;
        }

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(firstError(data));
        }
        return data;
    }

    function startThreeDS(token, confirmUrl) {
        return new Promise((resolve, reject) => {
            if (typeof Culqi3DS === 'undefined') {
                reject(new Error('No se pudo cargar Culqi 3DS.'));
                return;
            }

            pending3DS = { resolve, reject, confirmUrl, handled: false };

            Culqi3DS.publicKey = cfg.publicKey;
            Culqi3DS.settings = {
                charge: {
                    totalAmount: Number(cfg.amountCents),
                    returnUrl: window.location.href,
                    currency: cfg.currency || 'PEN',
                },
                card: { email: customerEmail() },
            };
            Culqi3DS.options = {
                showModal: true,
                showLoading: true,
                showIcon: true,
                style: { btnColor: '#ea580c', btnTextColor: '#FFFFFF' },
            };

            Culqi3DS.initAuthentication(token);
        });
    }

    window.addEventListener('message', async (event) => {
        if (event.origin !== window.location.origin || !pending3DS || pending3DS.handled) return;
        const response = event.data || {};
        if (response.parameters3DS) {
            pending3DS.handled = true;
            const confirmUrl = pending3DS.confirmUrl;
            const finish = pending3DS.resolve;
            const fail = pending3DS.reject;
            pending3DS = null;
            try {
                if (typeof Culqi3DS !== 'undefined' && typeof Culqi3DS.reset === 'function') {
                    Culqi3DS.reset();
                }
                const result = await postJson(confirmUrl, { authentication_3DS: response.parameters3DS });
                finish(result);
            } catch (err) {
                fail(err);
            }
        } else if (response.error) {
            pending3DS.handled = true;
            pending3DS.reject(new Error(typeof response.error === 'string' ? response.error : 'No se pudo autenticar la transacción.'));
            pending3DS = null;
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (paying) return;

        errorEl.classList.add('hidden');
        setPayingLock(true);

        try {
            const token = await openCulqiCheckout(selectedPayMethod);
            setLoading(true, 'Procesando pago', 'No cierres esta ventana ni pulses pagar otra vez.');
            const method = String(token).startsWith('ype_') ? 'yape' : 'card';
            paymentMethodInput.value = method;
            tokenInput.value = token;

            let deviceId = '';
            if (method === 'card' && !cfg.fake) {
                deviceId = await generateDeviceId();
                deviceInput.value = deviceId;
            } else {
                deviceInput.value = '';
            }

            const data = await postJson(form.action, {
                culqi_token: token,
                device_finger_print_id: deviceId,
            });

            if (data.needs_3ds && data.confirm_url) {
                const confirmed = await startThreeDS(token, sameOriginPath(data.confirm_url));
                if (confirmed.redirect_url) {
                    window.location.href = confirmed.redirect_url;
                    return;
                }
            }

            if (data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            throw new Error(data.message || 'No se recibió confirmación del pago.');
        } catch (err) {
            if (!err || err.cancelled) {
                setLoading(false);
                return;
            }
            showError(err?.message || 'No se pudo preparar el pago.');
        }
    });
})();
</script>
@endsection

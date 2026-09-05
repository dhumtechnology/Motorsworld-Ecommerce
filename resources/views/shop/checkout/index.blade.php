@extends('layouts.shop')

@section('title', 'Checkout — '.config('app.name'))

@section('content')
@php
    $fieldClass = 'w-full rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm transition focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $labelClass = 'mb-1.5 block text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500';
    $chargeCurrencySymbol = \App\Support\Currency::symbol($currency ?? 'PEN');
@endphp

<div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-neutral-900 via-neutral-900/90 to-transparent"></div>

    <div class="relative mx-auto max-w-6xl px-4 py-10 md:py-14 text-neutral-900 font-title">
        <div class="mb-8 md:mb-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-500">Pago seguro</p>
            <h1 class="mt-2 text-3xl md:text-4xl font-black uppercase tracking-wide text-white">Checkout</h1>
            <p class="mt-2 max-w-xl text-sm text-white/70">Tarjeta de crédito/débito o Yape · Culqi</p>
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
                    action="{{ route('shop.checkout.pay') }}"
                    data-submit-lock="async"
                    class="space-y-5 rounded-3xl border border-neutral-200/80 bg-white/95 p-5 sm:p-7 shadow-[0_20px_60px_-30px_rgba(0,0,0,0.45)] backdrop-blur"
                >
                    @csrf
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                    <input type="hidden" name="culqi_token" id="culqi_token" value="">
                    <input type="hidden" name="device_finger_print_id" id="device_finger_print_id" value="">

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
                                <label class="{{ $labelClass }}" for="address_city">Ciudad *</label>
                                <input id="address_city" name="address_city" value="{{ old('address_city', 'Lima') }}"
                                       class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="postal_code">C.P.</label>
                                <input id="postal_code" name="postal_code" value="{{ old('postal_code', '15001') }}"
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
                                <span class="mt-0.5 block text-xs font-normal text-neutral-500">Crédito o débito · un solo formulario</span>
                            </button>
                            <button type="button" data-pay-method="yape"
                                    class="rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-left text-sm font-bold text-neutral-900">
                                Yape
                                <span class="mt-0.5 block text-xs font-normal text-neutral-500">Celular + código OTP</span>
                            </button>
                        </div>

                        <div id="card-payment-panel" class="space-y-3">
                            <div class="space-y-4 rounded-2xl border border-neutral-200 bg-gradient-to-br from-neutral-50 to-white p-4">
                                <div>
                                    <label class="{{ $labelClass }}" for="card_number">Número de tarjeta</label>
                                    <input id="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="4111 1111 1111 1111" maxlength="19" class="{{ $fieldClass }}">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $labelClass }}" for="card_exp">Vence</label>
                                        <input id="card_exp" inputmode="numeric" autocomplete="cc-exp" placeholder="12/30" maxlength="5" class="{{ $fieldClass }}">
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}" for="card_cvv">CVV</label>
                                        <input id="card_cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" class="{{ $fieldClass }}">
                                    </div>
                                </div>
                                <p class="text-xs text-neutral-500">
                                    Visa, Mastercard y otras (crédito o débito) en el mismo formulario.
                                    @if ($culqiFake)
                                        Modo fake: no se envía a Culqi.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div id="yape-payment-panel" class="hidden space-y-4 rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-4">
                            <p class="text-sm text-neutral-600">
                                Abre Yape, genera el código de aprobación e ingrésalo junto a tu celular. Máximo S/ 2,000.00.
                            </p>
                            <div>
                                <label class="{{ $labelClass }}" for="yape_phone">Celular Yape *</label>
                                <input id="yape_phone" inputmode="tel" placeholder="999999999"
                                       value="{{ old('phone', $profile?->phone) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="yape_otp">Código OTP (6 dígitos) *</label>
                                <input id="yape_otp" inputmode="numeric" placeholder="123456" maxlength="6" class="{{ $fieldClass }}">
                            </div>
                            @if ($culqiFake)
                                <p class="text-xs text-neutral-500">Modo fake: cualquier OTP simula el pago.</p>
                            @endif
                        </div>
                    </section>

                    <p id="payment-error" class="hidden rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700"></p>

                    <button
                        type="submit"
                        id="pay-button"
                        class="group relative w-full overflow-hidden rounded-2xl bg-orange-600 px-5 py-4 text-sm font-black uppercase tracking-[0.16em] text-white shadow-lg shadow-orange-600/25 transition hover:bg-orange-500 disabled:opacity-50"
                    >
                        <span class="relative z-10">Pagar {{ $chargeCurrencySymbol }} {{ number_format($total, 2) }}</span>
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
                               data-pen="{{ number_format($totals->grandPen, 2, '.', '') }}"
                               data-usd="{{ number_format($totals->grandUsd, 2, '.', '') }}">
                                S/ {{ number_format($totals->grandPen, 2) }}
                            </p>
                        </div>
                    @else
                        <div class="mt-4 border-t border-white/10 pt-4 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-widest text-white/60">Total</span>
                            <span class="text-2xl font-black">{{ $chargeCurrencySymbol }} {{ number_format($total, 2) }}</span>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
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
};
</script>

@if (! $culqiFake && $culqiPublicKey)
<script src="https://3ds.culqi.com" defer></script>
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

    let selectedPayMethod = 'card';
    let pending3DS = null;

    const deliveryFields = document.getElementById('delivery-address-fields');
    const addressLine = document.getElementById('address_line1');
    const addressCity = document.getElementById('address_city');

    function selectedFulfillment() {
        return form.querySelector('input[name="fulfillment_method"]:checked')?.value || 'delivery';
    }

    function toggleFulfillment() {
        const isDelivery = selectedFulfillment() === 'delivery';
        if (deliveryFields) deliveryFields.classList.toggle('hidden', !isDelivery);
        if (addressLine) addressLine.required = isDelivery;
        if (addressCity) addressCity.required = isDelivery;
    }

    form.querySelectorAll('[data-fulfillment-option]').forEach((el) => {
        el.addEventListener('change', toggleFulfillment);
    });
    toggleFulfillment();

    function showError(message) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
        payButton.disabled = false;
        if (typeof window.unlockSubmitLock === 'function') {
            window.unlockSubmitLock(form);
        }
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

    const cardNumberEl = document.getElementById('card_number');
    const cardExpEl = document.getElementById('card_exp');
    cardNumberEl?.addEventListener('input', () => {
        const digits = cardNumberEl.value.replace(/\D+/g, '').slice(0, 16);
        cardNumberEl.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    });
    cardExpEl?.addEventListener('input', () => {
        let value = cardExpEl.value.replace(/\D+/g, '').slice(0, 4);
        if (value.length >= 3) value = value.slice(0, 2) + '/' + value.slice(2);
        cardExpEl.value = value;
    });

    function randomId(prefix) {
        return prefix + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
    }

    function customerEmail() {
        return (document.getElementById('customer_email')?.value || '').trim();
    }

    function parseExpiration(value) {
        const match = String(value || '').match(/^(\d{1,2})\s*\/\s*(\d{2}|\d{4})$/);
        if (!match) return null;
        const month = match[1].padStart(2, '0');
        let year = match[2];
        if (year.length === 2) year = '20' + year;
        if (Number(month) < 1 || Number(month) > 12) return null;
        return { month, year };
    }

    async function culqiFetch(path, payload) {
        const response = await fetch('https://secure.culqi.com/v2' + path, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + cfg.publicKey,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.id) {
            throw new Error(data.user_message || data.merchant_message || data.message || 'No se pudo tokenizar el pago.');
        }
        return data.id;
    }

    async function createCardToken() {
        if (cfg.fake) return randomId('tkn_test_fake_');
        if (!cfg.publicKey) throw new Error('Falta CULQI_PUBLIC_KEY.');

        const number = (document.getElementById('card_number')?.value || '').replace(/\D+/g, '');
        const cvv = (document.getElementById('card_cvv')?.value || '').replace(/\D+/g, '');
        const exp = parseExpiration(document.getElementById('card_exp')?.value);
        const email = customerEmail();

        if (number.length < 13) throw new Error('Ingresa un número de tarjeta válido.');
        if (!exp) throw new Error('Ingresa el vencimiento en formato MM/AA.');
        if (cvv.length < 3) throw new Error('Ingresa el CVV.');
        if (!email) throw new Error('Ingresa tu correo.');

        return culqiFetch('/tokens', {
            card_number: number,
            cvv,
            expiration_month: exp.month,
            expiration_year: exp.year,
            email,
            metadata: {
                dni: document.getElementById('customer_document')?.value || '',
            },
        });
    }

    async function createYapeToken() {
        const phone = (document.getElementById('yape_phone')?.value || '').replace(/\D+/g, '').slice(-9);
        const otp = (document.getElementById('yape_otp')?.value || '').replace(/\D+/g, '');

        if (phone.length !== 9) throw new Error('Ingresa un celular Yape válido.');
        if (otp.length !== 6) throw new Error('El OTP de Yape debe tener 6 dígitos.');
        if (cfg.currency && cfg.currency !== 'PEN') throw new Error('Yape solo acepta pagos en soles.');
        if (Number(cfg.amountCents) > Number(cfg.yapeMaxCents)) {
            throw new Error('Yape acepta un máximo de S/ 2,000.00.');
        }

        if (cfg.fake) return randomId('ype_test_fake_');
        if (!cfg.publicKey) throw new Error('Falta CULQI_PUBLIC_KEY.');

        return culqiFetch('/tokens/yape', {
            number_phone: phone,
            otp,
            amount: Number(cfg.amountCents),
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

        const response = await fetch(url, {
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
        errorEl.classList.add('hidden');
        payButton.disabled = true;

        try {
            const token = selectedPayMethod === 'yape'
                ? await createYapeToken()
                : await createCardToken();

            paymentMethodInput.value = selectedPayMethod;
            tokenInput.value = token;

            let deviceId = '';
            if (selectedPayMethod === 'card' && !cfg.fake) {
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
                const confirmed = await startThreeDS(token, data.confirm_url);
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
            showError(err?.message || 'No se pudo preparar el pago.');
        }
    });
})();
</script>
@endsection

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
            <p class="mt-2 max-w-xl text-sm text-white/70">Tarjeta de crédito/débito o Yape · Mercado Pago</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if (! $mpFake && ! $mpPublicKey)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Configura <code class="font-mono">MERCADOPAGO_PUBLIC_KEY</code> y <code class="font-mono">MERCADOPAGO_ACCESS_TOKEN</code>,
                o usa <code class="font-mono">MERCADOPAGO_FAKE=true</code> para probar sin llaves.
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
                    <input type="hidden" name="mp_token" id="mp_token" value="">
                    <input type="hidden" name="mp_payment_method_id" id="mp_payment_method_id" value="">
                    <input type="hidden" name="mp_installments" id="mp_installments" value="1">
                    <input type="hidden" name="mp_issuer_id" id="mp_issuer_id" value="">
                    <input type="hidden" name="mp_form_data" id="mp_form_data" value="">

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
                            @if ($mpFake)
                                <div class="space-y-4 rounded-2xl border border-neutral-200 bg-gradient-to-br from-neutral-50 to-white p-4">
                                    <div>
                                        <label class="{{ $labelClass }}" for="fake_card_number">Número de tarjeta</label>
                                        <input id="fake_card_number" inputmode="numeric" placeholder="5031 7557 3456 0604" class="{{ $fieldClass }}">
                                    </div>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="{{ $labelClass }}" for="fake_exp">Vence</label>
                                            <input id="fake_exp" placeholder="11/25" class="{{ $fieldClass }}">
                                        </div>
                                        <div>
                                            <label class="{{ $labelClass }}" for="fake_cvv">CVV</label>
                                            <input id="fake_cvv" placeholder="123" maxlength="4" class="{{ $fieldClass }}">
                                        </div>
                                        <div>
                                            <label class="{{ $labelClass }}" for="fake_doc">DNI</label>
                                            <input id="fake_doc" placeholder="12345678" class="{{ $fieldClass }}" value="{{ old('customer_document', $profile?->document) }}">
                                        </div>
                                    </div>
                                    <p class="text-xs text-neutral-500">Modo fake: no se envía a Mercado Pago.</p>
                                </div>
                            @else
                                <div id="cardPaymentBrick_container" class="min-h-[200px] rounded-2xl border border-neutral-200 bg-neutral-50/60 p-2 sm:p-3"></div>
                                <p class="text-xs text-neutral-500">Visa, Mastercard, etc. (crédito o débito) en el mismo formulario.</p>
                            @endif
                        </div>

                        <div id="yape-payment-panel" class="hidden space-y-4 rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-4">
                            <p class="text-sm text-neutral-600">
                                Abre Yape, genera el código de aprobación e ingrésalo junto a tu celular.
                            </p>
                            <div>
                                <label class="{{ $labelClass }}" for="yape_phone">Celular Yape *</label>
                                <input id="yape_phone" name="yape_phone" inputmode="tel" placeholder="999999999"
                                       value="{{ old('phone', $profile?->phone) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="yape_otp">Código OTP (6 dígitos) *</label>
                                <input id="yape_otp" name="yape_otp" inputmode="numeric" placeholder="123456" maxlength="6" class="{{ $fieldClass }}">
                            </div>
                            @if ($mpFake)
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
                        Procesado por Mercado Pago · Datos de tarjeta tokenizados (PCI)
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
    publicKey: @json($mpPublicKey),
    amount: {{ (float) $amount }},
    fake: @json((bool) $mpFake),
    locale: 'es-PE',
};
</script>

@if (! $mpFake && $mpPublicKey)
<script src="https://sdk.mercadopago.com/js/v2"></script>
@endif

<script>
(function () {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const cfg = window.MotoworldCheckout;
    const errorEl = document.getElementById('payment-error');
    const payButton = document.getElementById('pay-button');
    const paymentMethodInput = document.getElementById('payment_method');
    const mpToken = document.getElementById('mp_token');
    const mpPaymentMethodId = document.getElementById('mp_payment_method_id');
    const mpInstallments = document.getElementById('mp_installments');
    const mpIssuerId = document.getElementById('mp_issuer_id');
    const mpFormData = document.getElementById('mp_form_data');
    const cardPanel = document.getElementById('card-payment-panel');
    const yapePanel = document.getElementById('yape-payment-panel');

    let selectedPayMethod = 'card';
    let cardBrickController = null;
    let mp = null;

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

    function randomId(prefix) {
        return prefix + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
    }

    function payerPayload() {
        return {
            email: document.getElementById('customer_email')?.value,
            identification: {
                type: 'DNI',
                number: document.getElementById('customer_document')?.value,
            },
        };
    }

    async function mountCardBrick() {
        if (cfg.fake || !cfg.publicKey || typeof MercadoPago === 'undefined') {
            return;
        }

        mp = new MercadoPago(cfg.publicKey, { locale: cfg.locale || 'es-PE' });

        try {
            const bricksBuilder = mp.bricks();
            cardBrickController = await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', {
                initialization: {
                    amount: Number(cfg.amount),
                },
                customization: {
                    visual: {
                        hidePaymentButton: true,
                        style: {
                            theme: 'default',
                            customVariables: {
                                baseColor: '#ff6600',
                                formBackgroundColor: 'transparent',
                            },
                        },
                    },
                },
                callbacks: {
                    onReady: () => {},
                    onError: (error) => {
                        console.error(error);
                        showError(error?.message || 'Error en el formulario de tarjeta.');
                    },
                    onSubmit: () => Promise.resolve(),
                },
            });
            window.cardPaymentBrickController = cardBrickController;
        } catch (err) {
            console.error(err);
            showError('No se pudo cargar el formulario de tarjeta de Mercado Pago.');
        }
    }

    mountCardBrick();

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        errorEl.classList.add('hidden');
        payButton.disabled = true;

        try {
            if (selectedPayMethod === 'yape') {
                const phone = (document.getElementById('yape_phone')?.value || '').replace(/\D+/g, '');
                const otp = (document.getElementById('yape_otp')?.value || '').replace(/\D+/g, '');

                if (phone.length < 9) {
                    throw new Error('Ingresa un celular Yape válido.');
                }
                if (otp.length !== 6) {
                    throw new Error('El OTP de Yape debe tener 6 dígitos.');
                }

                let token;
                if (cfg.fake) {
                    token = randomId('ype_test_fake_');
                } else {
                    if (!mp) {
                        mp = new MercadoPago(cfg.publicKey, { locale: cfg.locale || 'es-PE' });
                    }
                    const yape = mp.yape({ phoneNumber: phone, otp: otp });
                    const yapeResult = await yape.create();
                    token = yapeResult?.id || yapeResult?.token;
                    if (!token) {
                        throw new Error('No se pudo generar el token Yape. Revisa celular y OTP.');
                    }
                }

                paymentMethodInput.value = 'yape';
                mpToken.value = token;
                mpPaymentMethodId.value = 'yape';
                mpInstallments.value = '1';
                mpIssuerId.value = '';
                mpFormData.value = JSON.stringify({
                    token,
                    payment_method_id: 'yape',
                    installments: 1,
                    payer: payerPayload(),
                });
            } else {
                let formData;

                if (cfg.fake) {
                    const token = randomId('tkn_test_fake_');
                    formData = {
                        token,
                        payment_method_id: 'visa',
                        installments: 1,
                        payer: payerPayload(),
                    };
                } else {
                    if (!cardBrickController || typeof cardBrickController.getFormData !== 'function') {
                        throw new Error('El formulario de tarjeta aún no está listo.');
                    }
                    formData = await cardBrickController.getFormData();
                    if (!formData?.token) {
                        throw new Error('Completa los datos de la tarjeta.');
                    }
                }

                paymentMethodInput.value = 'card';
                mpToken.value = formData.token;
                mpPaymentMethodId.value = formData.payment_method_id || '';
                mpInstallments.value = String(formData.installments || 1);
                mpIssuerId.value = formData.issuer_id != null ? String(formData.issuer_id) : '';
                mpFormData.value = JSON.stringify({
                    ...formData,
                    payer: formData.payer || payerPayload(),
                });
            }

            form.submit();
        } catch (err) {
            showError(err?.message || 'No se pudo preparar el pago.');
        }
    });
})();
</script>
@endsection

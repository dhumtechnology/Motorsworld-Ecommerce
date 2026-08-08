@extends('layouts.shop')

@section('title', 'Checkout — '.config('app.name'))

@section('content')
@php
    $fieldClass = 'w-full rounded border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-orange-600 focus:outline-none focus:ring-1 focus:ring-orange-600';
    $labelClass = 'block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-1.5';
    $totalCurrencySymbol = \App\Support\Currency::symbol($currency ?? 'PEN');
@endphp

<div class="mx-auto max-w-6xl px-4 py-10 text-neutral-900 font-title">
    <h1 class="text-3xl font-black uppercase tracking-wide mb-8">Checkout</h1>

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if (! $culqiFake && ! $culqiPublicKey)
        <div class="mb-6 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Falta configurar <code class="font-mono">CULQI_PUBLIC_KEY</code> y <code class="font-mono">CULQI_SECRET_KEY</code>,
            o activa <code class="font-mono">CULQI_FAKE=true</code> para probar sin Culqi.
        </div>
    @endif

    <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <form id="checkout-form" method="POST" action="{{ route('shop.checkout.pay') }}" class="rounded-lg border border-neutral-200 bg-white p-5 sm:p-6 space-y-6 shadow-sm">
                @csrf
                <input type="hidden" name="culqi_token" id="culqi_token" value="">

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-900 mb-4">Datos del comprador</h2>
                    @guest
                        <p class="mb-4 text-sm text-neutral-600">
                            No necesitas iniciar sesión. Usa tu correo para asociar la compra.
                            Si más adelante te registras con el mismo correo, recuperarás tu historial.
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
                </div>

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-900 mb-4">Entrega *</h2>
                    <div class="space-y-2 mb-4">
                        @foreach ([
                            'pickup' => ['label' => 'Recojo en tienda', 'hint' => 'Retiras tu pedido en Motosworld.'],
                            'delivery' => ['label' => 'Delivery', 'hint' => 'Enviamos a la dirección que indiques.'],
                        ] as $value => $meta)
                            <label class="flex items-start gap-3 rounded-md border border-neutral-300 bg-white px-3 py-3 cursor-pointer transition-colors hover:border-orange-500 has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50">
                                <input
                                    type="radio"
                                    name="fulfillment_method"
                                    value="{{ $value }}"
                                    class="mt-1 text-orange-600 focus:ring-orange-600"
                                    data-fulfillment-option
                                    @checked(old('fulfillment_method', 'delivery') === $value)
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-neutral-800">{{ $meta['label'] }}</span>
                                    <span class="block text-xs text-neutral-500 mt-0.5">{{ $meta['hint'] }}</span>
                                </span>
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
                </div>

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-900 mb-4">Método de pago</h2>
                    <div class="space-y-2">
                        @foreach ([
                            'card' => 'Tarjeta de crédito/débito',
                            'yape' => 'Yape',
                        ] as $value => $label)
                            <label class="flex items-center gap-3 rounded-md border border-neutral-300 bg-white px-3 py-3 cursor-pointer transition-colors hover:border-orange-500 has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50">
                                <input type="radio" name="payment_method" value="{{ $value }}" class="text-orange-600 focus:ring-orange-600" @checked(old('payment_method', 'card') === $value)>
                                <span class="text-sm font-semibold text-neutral-800">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="card-fields" class="space-y-4 rounded-md border border-neutral-200 bg-neutral-50 p-4">
                        <div>
                            <label class="{{ $labelClass }}" for="card_email">Email del cargo</label>
                            <input id="card_email" type="email"
                                   value="{{ old('customer_email', $user?->email) }}"
                                   class="{{ $fieldClass }}">
                            <p class="mt-1 text-xs text-neutral-500">Por defecto usa el correo del comprador.</p>
                        </div>
                    <div>
                        <label class="{{ $labelClass }}" for="card_number">Número de tarjeta</label>
                        <input id="card_number" inputmode="numeric" placeholder="4111111111111111" autocomplete="cc-number"
                               class="{{ $fieldClass }}">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="{{ $labelClass }}" for="card_exp_month">Mes</label>
                            <input id="card_exp_month" placeholder="09" maxlength="2"
                                   class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="card_exp_year">Año</label>
                            <input id="card_exp_year" placeholder="2030" maxlength="4"
                                   class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="card_cvv">CVV</label>
                            <input id="card_cvv" placeholder="123" maxlength="4" autocomplete="cc-csc"
                                   class="{{ $fieldClass }}">
                        </div>
                    </div>
                </div>

                <div id="yape-fields" class="hidden space-y-4 rounded-md border border-neutral-200 bg-neutral-50 p-4">
                    <div>
                        <label class="{{ $labelClass }}" for="yape_phone">Celular Yape</label>
                        <input id="yape_phone" placeholder="900000001"
                               class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="yape_otp">OTP</label>
                        <input id="yape_otp" placeholder="123456" maxlength="6"
                               class="{{ $fieldClass }}">
                    </div>
                </div>

                <p id="payment-error" class="hidden text-sm text-red-600"></p>

                <button type="submit" id="pay-button"
                        class="w-full rounded bg-orange-600 px-5 py-3 text-sm font-black uppercase tracking-wide text-white hover:bg-orange-700 transition-colors disabled:opacity-50">
                    Pagar {{ $totalCurrencySymbol }} {{ number_format($total, 2) }}
                </button>
            </form>
        </div>

        <div class="lg:col-span-5 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-900">Tu carrito</h2>

            <div class="rounded-lg border border-neutral-200 bg-white overflow-hidden divide-y divide-neutral-100 shadow-sm">
                @foreach ($lines as $line)
                    @php
                        $lineCurrencySymbol = \App\Support\Currency::symbol($line['currency'] ?? 'PEN');
                        $img = $line['product']->catalogImageUrl();
                    @endphp
                    <div class="flex gap-4 p-4 items-center">
                        @if ($img)
                            <img src="{{ $img }}" alt="" class="h-16 w-16 rounded object-cover border border-neutral-200">
                        @else
                            <div class="h-16 w-16 rounded bg-neutral-100 border border-neutral-200"></div>
                        @endif
                        <div class="flex-1 font-secondary min-w-0">
                            <p class="font-bold truncate text-neutral-900">{{ $line['product']->name }}</p>
                            @if (! empty($line['color_label']))
                                <p class="text-xs text-neutral-600">Color: {{ $line['color_label'] }}</p>
                            @endif
                            <p class="text-xs text-neutral-500">Cantidad: {{ $line['quantity'] }}</p>
                        </div>
                        <div class="text-right shrink-0 font-secondary text-orange-600">
                            <p class="font-bold">
                                {{ $lineCurrencySymbol }} {{ number_format($line['line_total'], 2) }}
                            </p>
                            @if ($line['is_on_sale'])
                                <p class="text-xs text-neutral-400 line-through">
                                    {{ $lineCurrencySymbol }} {{ number_format($line['list_unit_price'] * $line['quantity'], 2) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-5 flex items-center justify-between shadow-sm">
                <span class="uppercase font-bold tracking-widest text-sm text-neutral-700">Total</span>
                <span class="text-xl font-black text-neutral-900">{{ $totalCurrencySymbol }} {{ number_format($total, 2) }}</span>
            </div>
        </div>
    </div>
</div>

@if ($culqiFake || $culqiPublicKey)
<script>
    window.MotosworldCheckout = {
        publicKey: @json($culqiPublicKey),
        amountCents: {{ $amountCents }},
        fake: @json((bool) $culqiFake),
    };
</script>
<script>
(function () {
    const form = document.getElementById('checkout-form');
    const tokenInput = document.getElementById('culqi_token');
    const cardFields = document.getElementById('card-fields');
    const yapeFields = document.getElementById('yape-fields');
    const errorEl = document.getElementById('payment-error');
    const payButton = document.getElementById('pay-button');
    const publicKey = window.MotosworldCheckout.publicKey;
    const amountCents = window.MotosworldCheckout.amountCents;
    const fake = window.MotosworldCheckout.fake;

    function selectedMethod() {
        return form.querySelector('input[name="payment_method"]:checked')?.value || 'card';
    }

    function toggleFields() {
        const method = selectedMethod();
        cardFields.classList.toggle('hidden', method !== 'card');
        yapeFields.classList.toggle('hidden', method !== 'yape');
    }

    form.querySelectorAll('input[name="payment_method"]').forEach((el) => {
        el.addEventListener('change', toggleFields);
    });
    toggleFields();

    const deliveryFields = document.getElementById('delivery-address-fields');
    const addressLine = document.getElementById('address_line1');
    const addressCity = document.getElementById('address_city');

    function selectedFulfillment() {
        return form.querySelector('input[name="fulfillment_method"]:checked')?.value || 'delivery';
    }

    function toggleFulfillment() {
        const isDelivery = selectedFulfillment() === 'delivery';
        if (deliveryFields) {
            deliveryFields.classList.toggle('hidden', !isDelivery);
        }
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
    }

    function randomId(prefix) {
        return prefix + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
    }

    async function createCardToken() {
        if (fake) {
            return randomId('tkn_test_fake_');
        }

        const buyerEmail = document.getElementById('customer_email')?.value
            || document.getElementById('card_email').value;

        if (document.getElementById('card_email') && !document.getElementById('card_email').value) {
            document.getElementById('card_email').value = buyerEmail;
        }

        const response = await fetch('https://secure.culqi.com/v2/tokens', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + publicKey,
            },
            body: JSON.stringify({
                card_number: document.getElementById('card_number').value.replace(/\s+/g, ''),
                cvv: document.getElementById('card_cvv').value,
                expiration_month: document.getElementById('card_exp_month').value.padStart(2, '0'),
                expiration_year: document.getElementById('card_exp_year').value,
                email: document.getElementById('card_email').value || buyerEmail,
            }),
        });

        const data = await response.json();
        if (!response.ok || !data.id) {
            throw new Error(data.user_message || data.merchant_message || 'No se pudo tokenizar la tarjeta.');
        }
        return data.id;
    }

    async function createYapeToken() {
        if (fake) {
            return randomId('ype_test_fake_');
        }

        const response = await fetch('https://secure.culqi.com/v2/tokens/yape', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + publicKey,
            },
            body: JSON.stringify({
                amount: String(amountCents),
                number_phone: document.getElementById('yape_phone').value.replace(/\D+/g, ''),
                otp: document.getElementById('yape_otp').value,
            }),
        });

        const data = await response.json();
        if (!response.ok || !data.id) {
            throw new Error(data.user_message || data.merchant_message || 'No se pudo generar el token Yape.');
        }
        return data.id;
    }

    form.addEventListener('submit', async function (event) {
        const method = selectedMethod();

        if (method !== 'card' && method !== 'yape') {
            return;
        }

        event.preventDefault();
        errorEl.classList.add('hidden');
        payButton.disabled = true;

        try {
            const token = method === 'card'
                ? await createCardToken()
                : await createYapeToken();

            tokenInput.value = token;
            form.submit();
        } catch (err) {
            showError(err.message || 'Error al preparar el pago.');
        }
    });
})();
</script>
@endif
@endsection

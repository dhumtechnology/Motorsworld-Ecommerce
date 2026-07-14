@extends('layouts.shop')

@section('title', 'Checkout — '.config('app.name'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 text-white">
    <h1 class="text-3xl font-black uppercase tracking-wide mb-8">Checkout</h1>

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    @if (! $culqiFake && ! $culqiPublicKey)
        <div class="mb-6 rounded border border-yellow-800 bg-yellow-950/40 px-4 py-3 text-sm text-yellow-300">
            Falta configurar <code class="font-mono">CULQI_PUBLIC_KEY</code> y <code class="font-mono">CULQI_SECRET_KEY</code>,
            o activa <code class="font-mono">CULQI_FAKE=true</code> para probar sin Culqi.
        </div>
    @endif

    <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-7 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-500">Tu carrito</h2>

            <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] overflow-hidden divide-y divide-neutral-800">
                @foreach ($lines as $line)
                    <div class="flex gap-4 p-4 items-center">
                        @php
                            $img = $line['product']->catalogImageUrl();
                        @endphp
                        @if ($img)
                            <img src="{{ $img }}" alt="" class="h-16 w-16 rounded object-cover border border-neutral-700">
                        @else
                            <div class="h-16 w-16 rounded bg-[#252525] border border-neutral-700"></div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold truncate">{{ $line['product']->name }}</p>
                            <p class="text-xs text-neutral-500">{{ $line['product']->sku }} · Cant. {{ $line['quantity'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-orange-500">
                                S/ {{ number_format($line['line_total'], 2) }}
                            </p>
                            @if ($line['is_on_sale'])
                                <p class="text-xs text-neutral-500 line-through">
                                    S/ {{ number_format($line['list_unit_price'] * $line['quantity'], 2) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 flex justify-between items-center">
                <span class="text-neutral-400 uppercase text-xs font-bold tracking-widest">Total</span>
                <span class="text-2xl font-black text-white">S/ {{ number_format($total, 2) }}</span>
            </div>
        </div>

        <div class="lg:col-span-5">
            <form id="checkout-form" method="POST" action="{{ route('shop.checkout.pay') }}" class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-5 space-y-5">
                @csrf
                <input type="hidden" name="culqi_token" id="culqi_token" value="">

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-500 mb-3">Datos del comprador</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="first_name">Nombre</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name', $profile?->first_name) }}"
                                   class="w-full rounded border border-neutral-700 bg-[#252525] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="last_name">Apellido</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name', $profile?->last_name) }}"
                                   class="w-full rounded border border-neutral-700 bg-[#252525] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs text-neutral-500 mb-1" for="phone">Teléfono (requerido Plin/PagoEfectivo)</label>
                            <input id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}" placeholder="999999999"
                                   class="w-full rounded border border-neutral-700 bg-[#252525] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs text-neutral-500 mb-1" for="address_line1">Dirección</label>
                            <input id="address_line1" name="address_line1" value="{{ old('address_line1') }}"
                                   class="w-full rounded border border-neutral-700 bg-[#252525] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="address_city">Ciudad</label>
                            <input id="address_city" name="address_city" value="{{ old('address_city', 'Lima') }}"
                                   class="w-full rounded border border-neutral-700 bg-[#252525] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="postal_code">C.P.</label>
                            <input id="postal_code" name="postal_code" value="{{ old('postal_code', '15001') }}"
                                   class="w-full rounded border border-neutral-700 bg-[#252525] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-widest text-neutral-500 mb-3">Método de pago</h2>
                    <div class="space-y-2">
                        @foreach ([
                            'card' => 'Tarjeta de crédito/débito',
                            'yape' => 'Yape',
                            'plin' => 'Plin (QR billeteras)',
                            'pagoefectivo' => 'PagoEfectivo (CIP)',
                        ] as $value => $label)
                            <label class="flex items-center gap-3 rounded border border-neutral-700 px-3 py-3 cursor-pointer hover:border-orange-500/60 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-500/10">
                                <input type="radio" name="payment_method" value="{{ $value }}" class="text-orange-500" @checked(old('payment_method', 'card') === $value)>
                                <span class="text-sm font-semibold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="card-fields" class="space-y-3 rounded border border-neutral-700 p-4 bg-[#252525]">
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1" for="card_email">Email del cargo</label>
                        <input id="card_email" type="email" value="{{ auth()->user()->email }}"
                               class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1" for="card_number">Número de tarjeta</label>
                        <input id="card_number" inputmode="numeric" placeholder="4111111111111111" autocomplete="cc-number"
                               class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="card_exp_month">Mes</label>
                            <input id="card_exp_month" placeholder="09" maxlength="2"
                                   class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="card_exp_year">Año</label>
                            <input id="card_exp_year" placeholder="2030" maxlength="4"
                                   class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 mb-1" for="card_cvv">CVV</label>
                            <input id="card_cvv" placeholder="123" maxlength="4" autocomplete="cc-csc"
                                   class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div id="yape-fields" class="hidden space-y-3 rounded border border-neutral-700 p-4 bg-[#252525]">
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1" for="yape_phone">Celular Yape</label>
                        <input id="yape_phone" placeholder="900000001"
                               class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1" for="yape_otp">OTP</label>
                        <input id="yape_otp" placeholder="123456" maxlength="6"
                               class="w-full rounded border border-neutral-700 bg-[#1e1e1e] px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                    </div>
                </div>

                <p id="payment-error" class="hidden text-sm text-red-400"></p>

                <button type="submit" id="pay-button"
                        class="w-full rounded bg-orange-600 px-5 py-3 text-sm font-black uppercase tracking-wide text-white hover:bg-orange-500 transition-colors disabled:opacity-50">
                    Pagar S/ {{ number_format($total, 2) }}
                </button>
            </form>
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
                email: document.getElementById('card_email').value,
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

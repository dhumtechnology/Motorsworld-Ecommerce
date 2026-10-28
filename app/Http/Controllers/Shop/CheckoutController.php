<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Orders\CreateOrderFromCartAction;
use App\Actions\Orders\DiscardFailedCheckoutOrderAction;
use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Actions\Payments\ProcessCulqiPaymentAction;
use App\Actions\Payments\RefreshCulqiPaymentStatusAction;
use App\Actions\Shop\ResolveOrCreateCustomerAction;
use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ApplyCheckoutCouponRequest;
use App\Http\Requests\Shop\CheckoutPayRequest;
use App\Http\Requests\Shop\ConfirmCulqi3DSRequest;
use App\Http\Requests\Shop\QuoteCheckoutRequest;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use App\Models\Locations\Department;
use App\Models\Locations\District;
use App\Models\Orders\Address;
use App\Models\Orders\Order;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartTotalsService;
use App\Services\Checkout\CheckoutCharge;
use App\Services\Checkout\CheckoutChargeAssembler;
use App\Services\Coupons\CheckoutCouponService;
use App\Services\Coupons\CouponQuote;
use App\Services\Payments\Culqi\CulqiClient;
use App\Services\Payments\Culqi\Exceptions\CulqiApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    private const ACCESSIBLE_ORDERS_SESSION_KEY = 'checkout.accessible_order_ids';

    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly CartTotalsService $cartTotals,
        private readonly CreateOrderFromCartAction $createOrderFromCart,
        private readonly DiscardFailedCheckoutOrderAction $discardFailedOrder,
        private readonly ProcessCulqiPaymentAction $processPayment,
        private readonly RefreshCulqiPaymentStatusAction $refreshPaymentStatus,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
        private readonly ResolveOrCreateCustomerAction $resolveOrCreateCustomer,
        private readonly CheckoutCouponService $checkoutCoupons,
        private readonly CheckoutChargeAssembler $checkoutCharges,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cartResolver->resolve(
            $request->user(),
            $request->session()->getId(),
        );

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('shop.catalog')
                ->with('status', 'Tu carrito está vacío.');
        }

        $lines = $this->checkoutCoupons->cartLines($cart);
        $totals = $this->cartTotals->summarize($lines);
        $user = $request->user();
        $profile = $user?->customerProfile;
        $quote = $this->sessionCouponQuote($request, $lines, $totals);
        $charge = $this->checkoutCharges->assemble($totals, $quote, FulfillmentMethod::Delivery, null);

        return view('shop.checkout.index', [
            'cart' => $cart,
            'lines' => $lines,
            'totals' => $totals,
            'couponQuote' => $quote,
            'charge' => $charge,
            'total' => $charge->total,
            'currency' => $charge->currency,
            'culqiPublicKey' => config('services.culqi.public_key'),
            'culqiFake' => (bool) config('services.culqi.fake'),
            'profile' => $profile,
            'user' => $user,
            'amount' => $charge->total,
            'amountCents' => $charge->amountCents(),
            'yapeTokenUrl' => route('shop.checkout.yapeToken', absolute: false),
            'applyCouponUrl' => route('shop.checkout.coupon.apply', absolute: false),
            'removeCouponUrl' => route('shop.checkout.coupon.remove', absolute: false),
            'quoteUrl' => route('shop.checkout.quote', absolute: false),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'locationUrls' => [
                'provinces' => url('/ubicaciones/departamentos/__department__/provincias'),
                'districts' => url('/ubicaciones/provincias/__province__/distritos'),
            ],
        ]);
    }

    public function applyCoupon(ApplyCheckoutCouponRequest $request): JsonResponse
    {
        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Tu carrito está vacío.',
            ]);
        }

        $lines = $this->checkoutCoupons->cartLines($cart);
        $totals = $this->cartTotals->summarize($lines);
        $quote = $this->checkoutCoupons->quote(
            $request->couponCode(),
            $lines,
            $totals,
            $request->user(),
            $request->fulfillmentMethod(),
            $request->district(),
            $request->paymentMethod(),
        );

        $request->session()->put(CheckoutCouponService::SESSION_KEY, $quote->coupon->code);

        $charge = $this->checkoutCharges->assemble(
            $totals,
            $quote,
            $request->fulfillmentMethod() ?? FulfillmentMethod::Delivery,
            $request->district(),
        );

        return response()->json($this->totalsPayload($charge, $this->checkoutCoupons->payload($quote)['message'] ?? null));
    }

    public function quote(QuoteCheckoutRequest $request): JsonResponse
    {
        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Tu carrito está vacío.',
            ], 422);
        }

        $lines = $this->checkoutCoupons->cartLines($cart);
        $totals = $this->cartTotals->summarize($lines);
        $fulfillment = $request->fulfillmentMethod() ?? FulfillmentMethod::Delivery;
        $district = $request->district();
        $enforceRules = $fulfillment === FulfillmentMethod::Pickup || $district !== null;
        $quote = $this->sessionCouponQuote(
            $request,
            $lines,
            $totals,
            $fulfillment,
            $district,
            $request->paymentMethodOrNull(),
            $enforceRules,
        );
        $charge = $this->checkoutCharges->assemble($totals, $quote, $fulfillment, $district);

        return response()->json($this->totalsPayload($charge));
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $request->session()->forget(CheckoutCouponService::SESSION_KEY);

        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());
        $lines = $this->checkoutCoupons->cartLines($cart);
        $totals = $this->cartTotals->summarize($lines);

        $district = $this->districtFromRequest($request);
        $fulfillment = FulfillmentMethod::tryFrom((string) $request->input('fulfillment_method', ''))
            ?? FulfillmentMethod::Delivery;
        $charge = $this->checkoutCharges->assemble($totals, null, $fulfillment, $district);

        return response()->json($this->totalsPayload($charge, 'Cupón quitado.'));
    }

    public function yapeToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number_phone' => ['required', 'digits:9'],
            'otp' => ['required', 'digits:6'],
            'dni' => ['nullable', 'string', 'max:20'],
        ]);

        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Tu carrito está vacío.',
            ], 422);
        }

        $lines = $this->checkoutCoupons->cartLines($cart);
        $totals = $this->cartTotals->summarize($lines);
        $district = $this->districtFromRequest($request);
        $fulfillment = FulfillmentMethod::tryFrom((string) $request->input('fulfillment_method', ''))
            ?? FulfillmentMethod::Delivery;
        $quote = $this->sessionCouponQuote($request, $lines, $totals, $fulfillment, $district);
        $charge = $this->checkoutCharges->assemble($totals, $quote, $fulfillment, $district);
        $chargeAmount = $charge->total;
        $chargeCurrency = $charge->currency;
        $amountCents = $charge->amountCents();

        if (strtoupper((string) $chargeCurrency) !== 'PEN') {
            return response()->json([
                'message' => 'Yape solo acepta pagos en soles.',
            ], 422);
        }

        if ($amountCents < 100 || $amountCents > 99999) {
            return response()->json([
                'message' => 'Yape acepta montos de S/ 1.00 a S/ 999.99.',
            ], 422);
        }

        try {
            $token = app(CulqiClient::class)->createYapeToken(
                $validated['number_phone'],
                $validated['otp'],
                $amountCents,
                array_filter([
                    'dni' => $validated['dni'] ?? null,
                ]),
            );
        } catch (CulqiApiException $e) {
            return response()->json([
                'message' => $this->yapeTokenErrorMessage($e),
            ], 422);
        }

        return response()->json(['id' => $token]);
    }

    public function pay(CheckoutPayRequest $request): JsonResponse|RedirectResponse
    {
        $authenticated = $request->user();
        $cart = $this->cartResolver->resolve($authenticated, $request->session()->getId());

        try {
            $customer = $this->resolveOrCreateCustomer->execute(
                $request->customerPayload(),
                $authenticated,
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        if ($authenticated === null && blank($request->customerPayload()['customer_document'])) {
            throw ValidationException::withMessages([
                'customer_document' => 'El documento es obligatorio para comprar como invitado.',
            ]);
        }

        if ($authenticated === null && blank($request->customerPayload()['customer_email'])) {
            throw ValidationException::withMessages([
                'customer_email' => 'El correo es obligatorio para comprar como invitado.',
            ]);
        }

        $shippingAddress = $this->resolveAddress($request, $customer->id, $request->fulfillmentMethod());
        $this->syncCustomerProfile($customer, $request->customerDetails());

        $order = null;

        try {
            // No vaciar carrito hasta confirmar pago (aprobado o en confirmación).
            $order = $this->createOrderFromCart->execute(
                $customer,
                $cart,
                $shippingAddress,
                $shippingAddress,
                $request->fulfillmentMethod(),
                clearCart: false,
                coupon: $this->couponFromRequest($request),
                paymentMethod: $request->paymentMethod(),
            );

            $result = $this->processPayment->execute(
                $order,
                $request->paymentMethod(),
                $request->culqiToken(),
                $request->customerDetails(),
                $request->threeDS(),
            );
        } catch (ValidationException $e) {
            if ($order !== null) {
                $this->discardFailedOrder->execute($order);
            }

            throw $e;
        } catch (CulqiApiException $e) {
            Log::error('Culqi payment failed', [
                'message' => $e->getMessage(),
                'payload' => $e->payload,
                'order_id' => $order?->id,
            ]);

            if ($order !== null) {
                $this->discardFailedOrder->execute($order);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['payment' => [$e->getMessage()]],
                    'culqi' => $e->payload,
                ], 422);
            }

            return redirect()
                ->route('shop.checkout.show')
                ->withInput()
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            if ($order !== null) {
                $this->discardFailedOrder->execute($order);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se pudo completar el pago. Inténtalo de nuevo.',
                    'errors' => ['payment' => ['No se pudo completar el pago. Inténtalo de nuevo.']],
                ], 500);
            }

            throw $e;
        }

        $order = $result['order'];
        $payment = $result['payment'];

        // Pago fallido sin excepción: no conservar la orden.
        if ($payment->status === PaymentRecordStatus::Failed
            || $order->payment_status === PaymentStatus::Failed) {
            $this->discardFailedOrder->execute($order);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'El pago no fue aprobado. No se creó el pedido.',
                    'errors' => ['payment' => ['El pago no fue aprobado. No se creó el pedido.']],
                ], 422);
            }

            return redirect()
                ->route('shop.checkout.show')
                ->withInput()
                ->withErrors(['payment' => 'El pago no fue aprobado. No se creó el pedido.']);
        }

        $this->rememberAccessibleOrder($request, $order);

        if (! empty($result['needs_3ds'])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'needs_3ds' => true,
                    'message' => 'Tu banco requiere autenticación 3D Secure.',
                    'order_id' => $order->id,
                    'confirm_url' => route('shop.checkout.orders.confirm3ds', $order, false),
                    'payment' => [
                        'id' => $payment->id,
                        'method' => $payment->method->value,
                        'status' => $payment->status->value,
                    ],
                ]);
            }

            return redirect()
                ->route('shop.checkout.orders.show', $order)
                ->with('status', 'Tu banco requiere autenticación 3D Secure. Completa el desafío para confirmar el pago.');
        }

        $cart->items()->delete();
        $request->session()->forget(CheckoutCouponService::SESSION_KEY);

        if (! $payment->isPaid()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Estamos confirmando tu pago. El resumen del pedido aparecerá cuando se apruebe.',
                    'order_id' => $order->id,
                    'payment' => [
                        'id' => $payment->id,
                        'method' => $payment->method->value,
                        'status' => $payment->status->value,
                        'culqi_charge_id' => $payment->culqi_charge_id,
                    ],
                    'redirect_url' => route('shop.checkout.orders.show', $order),
                ]);
            }

            return redirect()
                ->route('shop.checkout.orders.show', $order)
                ->with('status', 'Estamos confirmando tu pago con Culqi…');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Pago realizado correctamente.',
                'order_id' => $order->id,
                'payment' => [
                    'id' => $payment->id,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'culqi_charge_id' => $payment->culqi_charge_id,
                ],
                'redirect_url' => route('shop.checkout.orders.show', $order),
            ]);
        }

        return redirect()
            ->route('shop.checkout.orders.show', $order)
            ->with('status', '¡Pago exitoso! Gracias por tu compra.');
    }

    public function confirmThreeDS(ConfirmCulqi3DSRequest $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->assertCanAccessOrder($request, $order);

        try {
            $order->loadMissing(['user.customerProfile', 'shippingAddress']);

            $result = $this->processPayment->confirmThreeDS(
                $order,
                $request->authentication3DS(),
                [
                    'first_name' => $order->user?->customerProfile?->first_name,
                    'last_name' => $order->user?->customerProfile?->last_name,
                    'phone' => $order->user?->customerProfile?->phone,
                    'address' => $order->shippingAddress?->line1,
                    'city' => $order->shippingAddress?->city,
                ],
            );
        } catch (CulqiApiException $e) {
            $this->discardFailedOrder->execute($order);

            Log::error('Culqi 3DS confirmation failed', [
                'message' => $e->getMessage(),
                'payload' => $e->payload,
                'order_id' => $order->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['payment' => [$e->getMessage()]],
                    'culqi' => $e->payload,
                ], 422);
            }

            return redirect()
                ->route('shop.checkout.show')
                ->withErrors(['payment' => $e->getMessage()]);
        }

        $order = $result['order'];
        $payment = $result['payment'];

        if (! $payment->isPaid()) {
            $this->discardFailedOrder->execute($order);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'No se pudo confirmar la autenticación 3DS.',
                    'errors' => ['payment' => ['No se pudo confirmar la autenticación 3DS.']],
                ], 422);
            }

            return redirect()
                ->route('shop.checkout.show')
                ->withErrors(['payment' => 'No se pudo confirmar la autenticación 3DS.']);
        }

        $cart = $this->cartResolver->resolve($request->user(), $request->session()->getId());
        $cart->items()->delete();
        $request->session()->forget(CheckoutCouponService::SESSION_KEY);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Pago realizado correctamente.',
                'order_id' => $order->id,
                'redirect_url' => route('shop.checkout.orders.show', $order),
            ]);
        }

        return redirect()
            ->route('shop.checkout.orders.show', $order)
            ->with('status', '¡Pago exitoso! Gracias por tu compra.');
    }

    public function showOrder(Request $request, Order $order): View|RedirectResponse
    {
        $this->assertCanAccessOrder($request, $order);

        if ($order->payment_status !== PaymentStatus::Paid) {
            $this->refreshPaymentStatus->execute($order);
            $order->refresh();
        }

        if (in_array($order->payment_status, [PaymentStatus::Failed, PaymentStatus::Refunded], true)) {
            $this->discardFailedOrder->execute($order);

            return redirect()
                ->route('shop.checkout.show')
                ->withErrors(['payment' => 'El pago no fue aprobado. No se creó el pedido.']);
        }

        $order->load([
            'items.product.primaryImage',
            'items.variant.colors',
            'payments',
            'user.customerProfile',
            'shippingAddress.district',
            'shippingAddress.province',
            'shippingAddress.department',
            'billingAddress',
            'coupon',
            'shippingRate',
        ]);

        $payment = $order->latestPayment();

        if ($order->payment_status !== PaymentStatus::Paid) {
            return view('shop.checkout.processing', [
                'order' => $order,
                'payment' => $payment,
                'statusUrl' => route('shop.checkout.orders.status', $order),
                'culqiFake' => (bool) config('services.culqi.fake'),
            ]);
        }

        return view('shop.checkout.order', [
            'order' => $order,
            'payment' => $payment,
            'culqiFake' => (bool) config('services.culqi.fake'),
        ]);
    }

    public function paymentStatus(Request $request, Order $order): JsonResponse
    {
        $this->assertCanAccessOrder($request, $order);

        $result = $this->refreshPaymentStatus->execute($order);
        $order->refresh();

        if (in_array($order->payment_status, [PaymentStatus::Failed, PaymentStatus::Refunded], true)
            || in_array($result['status'], ['rejected', 'cancelled', 'failed', 'refunded'], true)) {
            $this->discardFailedOrder->execute($order);

            return response()->json([
                'paid' => false,
                'status' => 'failed',
                'discarded' => true,
                'redirect_url' => route('shop.checkout.show'),
                'message' => 'El pago no fue aprobado. No se creó el pedido.',
            ]);
        }

        return response()->json([
            'paid' => $result['paid'],
            'status' => $result['status'],
            'redirect_url' => $result['paid']
                ? route('shop.checkout.orders.show', $order)
                : null,
        ]);
    }

    /**
     * Solo con CULQI_FAKE=true: simula confirmación de pago pendiente.
     */
    public function simulatePaid(Request $request, Order $order): RedirectResponse
    {
        abort_unless((bool) config('services.culqi.fake'), 404);
        $this->assertCanAccessOrder($request, $order);

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()
                ->route('shop.checkout.orders.show', $order)
                ->with('status', 'Este pedido ya está pagado.');
        }

        $payment = $order->latestPayment();

        if ($payment !== null && $payment->status === PaymentRecordStatus::Pending) {
            $payload = $payment->provider_payload ?? [];
            $payload['outcome'] = ['type' => 'venta_exitosa'];
            $payload['simulated_at'] = now()->toIso8601String();
            $payment->update(['provider_payload' => $payload]);
        }

        $this->markOrderAsPaid->execute(
            $order,
            $payment,
            'Pago simulado (CULQI_FAKE=true)',
        );

        return redirect()
            ->route('shop.checkout.orders.show', $order)
            ->with('status', 'Pago simulado correctamente (modo fake).');
    }

    private function sessionCouponQuote(
        Request $request,
        $lines,
        $totals,
        ?FulfillmentMethod $fulfillment = null,
        ?District $district = null,
        ?PaymentMethod $paymentMethod = null,
        bool $enforceCheckoutRules = false,
    ): ?CouponQuote {
        $code = $request->session()->get(CheckoutCouponService::SESSION_KEY);

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        $quote = $this->checkoutCoupons->quoteOrNull(
            $code,
            $lines,
            $totals,
            $request->user(),
            $fulfillment,
            $district,
            $paymentMethod,
            $enforceCheckoutRules,
        );

        if ($quote === null) {
            $request->session()->forget(CheckoutCouponService::SESSION_KEY);
        }

        return $quote;
    }

    /**
     * @return array<string, mixed>
     */
    private function totalsPayload(CheckoutCharge $charge, ?string $message = null): array
    {
        $payload = $charge->coupon
            ? array_merge($this->checkoutCoupons->payload($charge->coupon), $charge->payload())
            : array_merge([
                'valid' => false,
                'code' => null,
                'name' => null,
                'type' => null,
                'value_label' => null,
                'applied_label' => null,
                'conversion_note' => null,
                'discount_amount' => 0,
                'discount_formatted' => null,
                'discount_pen' => 0,
                'discount_usd' => 0,
            ], $charge->payload());

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return $payload;
    }

    private function districtFromRequest(Request $request): ?District
    {
        $id = (int) $request->input('district_id', 0);

        if ($id <= 0) {
            return null;
        }

        return District::query()->find($id);
    }

    private function couponFromRequest(CheckoutPayRequest $request): ?Coupon
    {
        $code = $request->couponCode()
            ?: $request->session()->get(CheckoutCouponService::SESSION_KEY);

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        $coupon = $this->checkoutCoupons->findByCode($code);

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón no existe.',
            ]);
        }

        return $coupon;
    }

    private function assertCanAccessOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user !== null && (int) $order->user_id === (int) $user->id) {
            return;
        }

        $accessibleIds = collect($request->session()->get(self::ACCESSIBLE_ORDERS_SESSION_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless(in_array((int) $order->id, $accessibleIds, true), 403);
    }

    private function rememberAccessibleOrder(Request $request, Order $order): void
    {
        $ids = collect($request->session()->get(self::ACCESSIBLE_ORDERS_SESSION_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->push((int) $order->id)
            ->unique()
            ->values()
            ->all();

        $request->session()->put(self::ACCESSIBLE_ORDERS_SESSION_KEY, $ids);
    }

    private function syncCustomerProfile(User $user, array $customer): void
    {
        $profile = $user->customerProfile;

        if ($profile === null) {
            return;
        }

        $updates = array_filter([
            'first_name' => $customer['first_name'] ?? null,
            'last_name' => $customer['last_name'] ?? null,
            'phone' => $customer['phone'] ?? null,
        ], fn ($value) => is_string($value) && trim($value) !== '');

        if ($updates !== []) {
            $profile->update($updates);
        }
    }

    private function resolveAddress(
        CheckoutPayRequest $request,
        int $userId,
        FulfillmentMethod $fulfillmentMethod,
    ): ?Address {
        if ($fulfillmentMethod === FulfillmentMethod::Pickup) {
            return null;
        }

        $line1 = trim((string) $request->input('address_line1', ''));
        $district = $request->district();

        if ($line1 === '' || $district === null) {
            throw ValidationException::withMessages([
                'address_line1' => 'La dirección, provincia y distrito son obligatorios para delivery.',
            ]);
        }

        return Address::query()->create([
            'user_id' => $userId,
            'line1' => $line1,
            'city' => $district->name,
            'postal_code' => $request->input('postal_code') ?: null,
            'country' => 'PE',
            'country_id' => $request->peruCountryId() ?? $district->department?->country_id,
            'department_id' => $district->department_id,
            'province_id' => $district->province_id,
            'district_id' => $district->id,
        ]);
    }

    private function yapeTokenErrorMessage(CulqiApiException $e): string
    {
        $message = $e->getMessage();

        if (str_contains(mb_strtolower($message), 'no está autorizado')) {
            return 'Culqi no tiene Yape habilitado para este comercio. Hay que pedirlo a Culqi Soporte. Mientras tanto se puede pagar con tarjeta.';
        }

        return $message !== '' ? $message : 'No se pudo tokenizar Yape.';
    }
}

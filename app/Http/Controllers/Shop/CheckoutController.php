<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Orders\CreateOrderFromCartAction;
use App\Actions\Orders\DiscardFailedCheckoutOrderAction;
use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Actions\Payments\ProcessMercadoPagoPaymentAction;
use App\Actions\Payments\RefreshMercadoPagoPaymentStatusAction;
use App\Actions\Shop\ResolveOrCreateCustomerAction;
use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutPayRequest;
use App\Models\Auth\User;
use App\Models\Orders\Address;
use App\Models\Orders\Order;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartTotalsService;
use App\Services\Orders\ProductPricingService;
use App\Services\Payments\MercadoPago\Exceptions\MercadoPagoApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const ACCESSIBLE_ORDERS_SESSION_KEY = 'checkout.accessible_order_ids';

    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly ProductPricingService $pricing,
        private readonly CartTotalsService $cartTotals,
        private readonly CreateOrderFromCartAction $createOrderFromCart,
        private readonly DiscardFailedCheckoutOrderAction $discardFailedOrder,
        private readonly ProcessMercadoPagoPaymentAction $processPayment,
        private readonly RefreshMercadoPagoPaymentStatusAction $refreshPaymentStatus,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
        private readonly ResolveOrCreateCustomerAction $resolveOrCreateCustomer,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cartResolver->resolve(
            $request->user(),
            $request->session()->getId(),
        );

        $cart->loadMissing([
            'items.product.category',
            'items.product.primaryImage',
            'items.product.activeOffer',
            'items.variant.inventory',
            'items.variant.images',
            'items.variant.colors',
        ]);

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('shop.catalog')
                ->with('status', 'Tu carrito está vacío.');
        }

        $lines = $cart->items
            ->filter(fn ($item) => $item->product !== null)
            ->map(function ($item) {
                $product = $item->product;
                $pricing = $this->pricing->resolve($product);

                return [
                    'product' => $product,
                    'variant' => $item->variant,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $pricing->unitPrice,
                    'list_unit_price' => (float) $pricing->listUnitPrice,
                    'line_total' => (float) $pricing->unitPrice * $item->quantity,
                    'is_on_sale' => $pricing->hasOffer(),
                    'currency' => $pricing->currency,
                    'color_label' => $item->variant?->colorLabel(),
                ];
            });

        $totals = $this->cartTotals->summarize($lines);
        $user = $request->user();
        $profile = $user?->customerProfile;

        return view('shop.checkout.index', [
            'cart' => $cart,
            'lines' => $lines,
            'totals' => $totals,
            'total' => $totals->chargeAmount(),
            'currency' => $totals->chargeCurrency(),
            'mpPublicKey' => config('services.mercadopago.public_key'),
            'mpFake' => (bool) config('services.mercadopago.fake'),
            'profile' => $profile,
            'user' => $user,
            'amount' => round((float) $totals->chargeAmount(), 2),
        ]);
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
            );

            $result = $this->processPayment->execute(
                $order,
                $request->paymentMethod(),
                $request->mercadoPagoFormData(),
                $request->customerDetails(),
            );
        } catch (ValidationException $e) {
            if ($order !== null) {
                $this->discardFailedOrder->execute($order);
            }

            throw $e;
        } catch (MercadoPagoApiException $e) {
            Log::error('Mercado Pago payment failed', [
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
                    'mercadopago' => $e->payload,
                ], 422);
            }

            return redirect()
                ->route('shop.checkout.show')
                ->withInput()
                ->withErrors(['payment' => $e->getMessage()]);
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

        // Aprobado o en confirmación: vaciar carrito y recordar acceso al pedido.
        $cart->items()->delete();
        $this->rememberAccessibleOrder($request, $order);

        if (! $payment->isPaid()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Estamos confirmando tu pago. El resumen del pedido aparecerá cuando se apruebe.',
                    'order_id' => $order->id,
                    'payment' => [
                        'id' => $payment->id,
                        'method' => $payment->method->value,
                        'status' => $payment->status->value,
                        'mp_payment_id' => $payment->mp_payment_id,
                    ],
                    'redirect_url' => route('shop.checkout.orders.show', $order),
                ]);
            }

            return redirect()
                ->route('shop.checkout.orders.show', $order)
                ->with('status', 'Estamos confirmando tu pago con Mercado Pago…');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Pago realizado correctamente.',
                'order_id' => $order->id,
                'payment' => [
                    'id' => $payment->id,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'mp_payment_id' => $payment->mp_payment_id,
                ],
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
            'shippingAddress',
            'billingAddress',
        ]);

        $payment = $order->latestPayment();

        if ($order->payment_status !== PaymentStatus::Paid) {
            return view('shop.checkout.processing', [
                'order' => $order,
                'payment' => $payment,
                'statusUrl' => route('shop.checkout.orders.status', $order),
                'mpFake' => (bool) config('services.mercadopago.fake'),
            ]);
        }

        return view('shop.checkout.order', [
            'order' => $order,
            'payment' => $payment,
            'mpFake' => (bool) config('services.mercadopago.fake'),
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
     * Solo con MERCADOPAGO_FAKE=true: simula confirmación de pago pendiente.
     */
    public function simulatePaid(Request $request, Order $order): RedirectResponse
    {
        abort_unless((bool) config('services.mercadopago.fake'), 404);
        $this->assertCanAccessOrder($request, $order);

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()
                ->route('shop.checkout.orders.show', $order)
                ->with('status', 'Este pedido ya está pagado.');
        }

        $payment = $order->latestPayment();

        if ($payment !== null && $payment->status === PaymentRecordStatus::Pending) {
            $payload = $payment->provider_payload ?? [];
            $payload['status'] = 'approved';
            $payload['simulated_at'] = now()->toIso8601String();
            $payment->update(['provider_payload' => $payload]);
        }

        $this->markOrderAsPaid->execute(
            $order,
            $payment,
            'Pago simulado (MERCADOPAGO_FAKE=true)',
        );

        return redirect()
            ->route('shop.checkout.orders.show', $order)
            ->with('status', 'Pago simulado correctamente (modo fake).');
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
        $city = trim((string) $request->input('address_city', ''));

        if ($line1 === '' || $city === '') {
            throw ValidationException::withMessages([
                'address_line1' => 'La dirección y ciudad son obligatorias para delivery.',
            ]);
        }

        return Address::query()->create([
            'user_id' => $userId,
            'line1' => $line1,
            'city' => $city,
            'postal_code' => $request->input('postal_code') ?: '15001',
            'country' => 'PE',
        ]);
    }
}

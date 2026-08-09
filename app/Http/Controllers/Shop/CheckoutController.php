<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Orders\CreateOrderFromCartAction;
use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Actions\Payments\ProcessCulqiPaymentAction;
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
use App\Services\Payments\Culqi\Exceptions\CulqiApiException;
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
        private readonly ProcessCulqiPaymentAction $processPayment,
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
            'culqiPublicKey' => config('services.culqi.public_key'),
            'culqiFake' => (bool) config('services.culqi.fake'),
            'profile' => $profile,
            'user' => $user,
            'amountCents' => $totals->amountCents(),
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
            $order = $this->createOrderFromCart->execute(
                $customer,
                $cart,
                $shippingAddress,
                $shippingAddress,
                $request->fulfillmentMethod(),
            );

            $this->rememberAccessibleOrder($request, $order);

            $result = $this->processPayment->execute(
                $order,
                $request->paymentMethod(),
                $request->culqiToken(),
                $request->customerDetails(),
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (CulqiApiException $e) {
            Log::error('Culqi payment failed', [
                'message' => $e->getMessage(),
                'payload' => $e->payload,
                'order_id' => $order?->id,
            ]);

            if ($order !== null) {
                $this->rememberAccessibleOrder($request, $order);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['payment' => [$e->getMessage()]],
                    'order_id' => $order?->id,
                    'culqi' => $e->payload,
                ], 422);
            }

            if ($order !== null) {
                return redirect()
                    ->route('shop.checkout.orders.show', $order)
                    ->withErrors(['payment' => $e->getMessage()]);
            }

            return redirect()
                ->route('shop.checkout.show')
                ->withInput()
                ->withErrors(['payment' => $e->getMessage()]);
        }

        $order = $result['order'];
        $payment = $result['payment'];
        $this->rememberAccessibleOrder($request, $order);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $payment->isPaid()
                    ? 'Pago realizado correctamente.'
                    : 'Pedido creado. Completa el pago pendiente.',
                'order_id' => $order->id,
                'payment' => [
                    'id' => $payment->id,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'payment_code' => $payment->payment_code,
                    'qr_url' => $payment->qr_url,
                    'payment_url' => $payment->payment_url,
                    'expires_at' => $payment->expires_at?->toIso8601String(),
                ],
                'redirect_url' => route('shop.checkout.orders.show', $order),
            ]);
        }

        return redirect()
            ->route('shop.checkout.orders.show', $order)
            ->with('status', $payment->isPaid()
                ? '¡Pago exitoso! Gracias por tu compra.'
                : 'Pedido generado. Usa el código o QR para completar el pago.');
    }

    public function showOrder(Request $request, Order $order): View
    {
        $this->assertCanAccessOrder($request, $order);

        $order->load([
            'items.product.primaryImage',
            'items.variant.colors',
            'payments',
            'user.customerProfile',
            'shippingAddress',
            'billingAddress',
        ]);

        return view('shop.checkout.order', [
            'order' => $order,
            'payment' => $order->latestPayment(),
            'culqiFake' => (bool) config('services.culqi.fake'),
        ]);
    }

    /**
     * Solo con CULQI_FAKE=true: simula el webhook de PagoEfectivo/Plin.
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
            $payload['state'] = 'paid';
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

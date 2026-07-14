<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Orders\CreateOrderFromCartAction;
use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Actions\Payments\ProcessCulqiPaymentAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutPayRequest;
use App\Models\Orders\Address;
use App\Models\Orders\Order;
use App\Services\Cart\CartResolver;
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
    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly ProductPricingService $pricing,
        private readonly CreateOrderFromCartAction $createOrderFromCart,
        private readonly ProcessCulqiPaymentAction $processPayment,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cartResolver->resolve(
            $request->user(),
            $request->session()->getId(),
        );

        $cart->loadMissing([
            'items.product.inventory',
            'items.product.category',
            'items.product.primaryImage',
            'items.product.activeOffer',
        ]);

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('shop.catalog')
                ->with('status', 'Tu carrito está vacío.');
        }

        $lines = $cart->items->map(function ($item) {
            $product = $item->product;
            $pricing = $this->pricing->resolve($product);

            return [
                'product' => $product,
                'quantity' => $item->quantity,
                'unit_price' => (float) $pricing->unitPrice,
                'list_unit_price' => (float) $pricing->listUnitPrice,
                'line_total' => (float) $pricing->unitPrice * $item->quantity,
                'is_on_sale' => $pricing->hasOffer(),
                'currency' => $pricing->currency,
            ];
        });

        $total = $lines->sum('line_total');
        $user = $request->user();
        $profile = $user?->customerProfile;

        return view('shop.checkout.index', [
            'cart' => $cart,
            'lines' => $lines,
            'total' => $total,
            'currency' => $lines->first()['currency'] ?? 'PEN',
            'culqiPublicKey' => config('services.culqi.public_key'),
            'culqiFake' => (bool) config('services.culqi.fake'),
            'profile' => $profile,
            'amountCents' => (int) round($total * 100),
        ]);
    }

    public function pay(CheckoutPayRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $cart = $this->cartResolver->resolve($user, $request->session()->getId());

        $shippingAddress = $this->resolveAddress($request, $user->id);
        $this->syncCustomerProfile($user, $request->customerDetails());

        $order = null;

        try {
            $order = $this->createOrderFromCart->execute(
                $user,
                $cart,
                $shippingAddress,
                $shippingAddress,
            );

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
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load([
            'items.product.primaryImage',
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
        abort_unless($order->user_id === $request->user()->id, 403);

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

    private function syncCustomerProfile(\App\Models\Auth\User $user, array $customer): void
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

    private function resolveAddress(CheckoutPayRequest $request, int $userId): ?Address
    {
        $line1 = trim((string) $request->input('address_line1', ''));
        $city = trim((string) $request->input('address_city', ''));

        if ($line1 === '' || $city === '') {
            return $request->user()?->customerProfile?->defaultShippingAddress
                ?? Address::query()->where('user_id', $userId)->latest('id')->first();
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

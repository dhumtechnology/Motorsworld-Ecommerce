<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Cart\AddProductToCartAction;
use App\Actions\Cart\BuildCartLinesAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartItemRequest;
use App\Models\Cart\Cart;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartTotalsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly AddProductToCartAction $addProduct,
        private readonly UpdateCartItemQuantityAction $updateQuantity,
        private readonly CartTotalsService $cartTotals,
        private readonly BuildCartLinesAction $buildCartLines,
    ) {}

    public function index(Request $request): View
    {
        $cart = $this->resolveCart($request);
        $lines = $this->buildCartLines->execute($cart);
        $totals = $this->cartTotals->summarize($lines);

        return view('shop.cart.index', [
            'cart' => $cart,
            'lines' => $lines,
            'totals' => $totals,
            'total' => $totals->chargeAmount(),
            'itemCount' => (int) $lines->sum('quantity'),
            'totalCurrencySymbol' => \App\Support\Currency::symbol($totals->chargeCurrency()),
        ]);
    }

    public function store(AddToCartRequest $request, Product $product): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $variant = $this->resolveVariant($request, $product);

        $this->addProduct->execute($cart, $variant, $request->quantity());

        return $this->respond($cart, $variant->id);
    }

    public function update(UpdateCartItemRequest $request, Product $product): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $variant = $this->resolveVariant($request, $product);

        $this->updateQuantity->execute($cart, $variant, $request->quantity());

        return $this->respond($cart, $variant->id);
    }

    public function increment(Request $request, Product $product): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $variant = $this->resolveVariant($request, $product);

        $currentQuantity = (int) $cart->items()
            ->where('product_variant_id', $variant->id)
            ->value('quantity');

        $this->updateQuantity->execute($cart, $variant, $currentQuantity + 1);

        return $this->respond($cart, $variant->id);
    }

    public function decrement(Request $request, Product $product): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $variant = $this->resolveVariant($request, $product);

        $currentQuantity = (int) $cart->items()
            ->where('product_variant_id', $variant->id)
            ->value('quantity');

        $this->updateQuantity->execute($cart, $variant, max(0, $currentQuantity - 1));

        return $this->respond($cart, $variant->id);
    }

    public function remove(Request $request, Product $product): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $variant = $this->resolveVariant($request, $product);

        $this->updateQuantity->execute($cart, $variant, 0);

        return $this->respond($cart, $variant->id);
    }

    private function resolveCart(Request $request): Cart
    {
        return $this->cartResolver->resolve(
            $request->user(),
            $request->session()->getId(),
        );
    }

    private function resolveVariant(Request $request, Product $product): ProductVariant
    {
        $variantId = (int) $request->input('product_variant_id', 0);

        $variant = ProductVariant::query()
            ->with(['inventory', 'product'])
            ->where('product_id', $product->id)
            ->when(
                $variantId > 0,
                fn ($q) => $q->where('id', $variantId),
                fn ($q) => $q->orderBy('id'),
            )
            ->first();

        if ($variant === null) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Selecciona un color disponible.',
            ]);
        }

        return $variant;
    }

    /**
     * @return array<string, mixed>
     */
    private function cartSummary(Cart $cart, ?int $variantId = null): array
    {
        $cart->unsetRelation('items');

        $lines = $this->buildCartLines->execute($cart);
        $totals = $this->cartTotals->summarize($lines);

        $lineQuantity = 0;

        if ($variantId !== null) {
            $matched = $lines->first(
                fn (array $line) => (int) $line['variant']->id === $variantId,
            );
            $lineQuantity = (int) ($matched['quantity'] ?? 0);
        }

        $chargeCurrency = $totals->chargeCurrency();

        return [
            'item_count' => (int) $lines->sum('quantity'),
            'line_count' => $lines->count(),
            'product_variant_id' => $variantId,
            'line_quantity' => $lineQuantity,
            'charge_amount' => $totals->chargeAmount(),
            'charge_currency' => $chargeCurrency,
            'charge_currency_symbol' => \App\Support\Currency::symbol($chargeCurrency),
            'items' => $lines->map(fn (array $line) => [
                'product_id' => $line['product']->id,
                'product_variant_id' => $line['variant']->id,
                'quantity' => (int) $line['quantity'],
                'sku' => $line['variant']->sku ?? $line['product']->sku,
                'name' => $line['product']->name,
                'color' => $line['color_label'],
                'url' => route('shop.product.show', $line['product']),
                'image' => $line['image'],
                'line_total' => (float) $line['line_total'],
                'list_line_total' => round((float) $line['list_unit_price'] * (int) $line['quantity'], 2),
                'is_on_sale' => (bool) $line['is_on_sale'],
                'currency' => $line['currency'],
                'currency_symbol' => $line['currency_symbol'],
            ])->values()->all(),
        ];
    }

    private function respond(Cart $cart, int $variantId): JsonResponse
    {
        return response()->json($this->cartSummary($cart, $variantId));
    }
}

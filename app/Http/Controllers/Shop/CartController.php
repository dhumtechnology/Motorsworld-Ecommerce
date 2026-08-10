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
        $cart->load(['items.product', 'items.variant.inventory']);

        $lineQuantity = 0;

        if ($variantId !== null) {
            $lineQuantity = (int) $cart->items
                ->firstWhere('product_variant_id', $variantId)
                ?->quantity;
        }

        return [
            'item_count' => (int) $cart->items->sum('quantity'),
            'line_count' => $cart->items->count(),
            'product_variant_id' => $variantId,
            'line_quantity' => $lineQuantity,
            'items' => $cart->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => (int) $item->quantity,
                'sku' => $item->variant?->sku ?? $item->product?->sku,
                'name' => $item->product?->name,
                'color' => $item->variant?->colorLabel(),
            ])->values()->all(),
        ];
    }

    private function respond(Cart $cart, int $variantId): JsonResponse
    {
        return response()->json($this->cartSummary($cart, $variantId));
    }
}

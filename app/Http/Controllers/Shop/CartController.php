<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Cart\AddProductToCartAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartItemRequest;
use App\Models\Cart\Cart;
use App\Models\Products\Product;
use App\Services\Cart\CartResolver;
use App\Services\Orders\ProductPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly AddProductToCartAction $addProduct,
        private readonly UpdateCartItemQuantityAction $updateQuantity,
    ) {}

    /**
     * Vista del carrito con líneas y totales.
     */
    public function index(Request $request): View
    {
        $cart = $this->resolveCart($request);
        $cart->loadMissing([
            'items.product.inventory',
            'items.product.category',
            'items.product.primaryImage',
            'items.product.activeOffer',
        ]);

        $pricing = app(ProductPricingService::class);

        $lines = $cart->items
            ->filter(fn ($item) => $item->product !== null)
            ->map(function ($item) use ($pricing) {
                $product = $item->product;
                $price = $pricing->resolve($product);

                return [
                    'item' => $item,
                    'product' => $product,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $price->unitPrice,
                    'list_unit_price' => (float) $price->listUnitPrice,
                    'line_total' => (float) $price->unitPrice * (int) $item->quantity,
                    'is_on_sale' => $price->hasOffer(),
                    'image' => $product->catalogImageUrl(),
                    'max_quantity' => max(0, (int) ($product->inventory?->available_stock ?? 0)),
                ];
            })
            ->values();

        return view('shop.cart.index', [
            'cart' => $cart,
            'lines' => $lines,
            'total' => $lines->sum('line_total'),
            'itemCount' => (int) $lines->sum('quantity'),
        ]);
    }

    /**
     * Agregar unidades al carrito (detalle de producto). Suma sobre la cantidad existente.
     */
    public function store(AddToCartRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);

        $this->addProduct->execute($cart, $product, $request->quantity());

        return $this->respond($request, $cart, $product->id);
    }

    /**
     * Establecer cantidad absoluta (input numérico del detalle de producto).
     */
    public function update(UpdateCartItemRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);

        $this->updateQuantity->execute($cart, $product, $request->quantity());

        return $this->respond($request, $cart, $product->id);
    }

    /**
     * Botón "+" en detalle de producto.
     */
    public function increment(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);

        $currentQuantity = (int) $cart->items()
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->updateQuantity->execute($cart, $product, $currentQuantity + 1);

        return $this->respond($request, $cart, $product->id);
    }

    /**
     * Botón "−" en detalle de producto.
     */
    public function decrement(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);

        $currentQuantity = (int) $cart->items()
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->updateQuantity->execute($cart, $product, max(0, $currentQuantity - 1));

        return $this->respond($request, $cart, $product->id);
    }

    private function resolveCart(Request $request): Cart
    {
        return $this->cartResolver->resolve(
            $request->user(),
            $request->session()->getId(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function cartSummary(Cart $cart, ?int $productId = null): array
    {
        $cart->loadMissing(['items.product.inventory']);

        $lineQuantity = 0;

        if ($productId !== null) {
            $lineQuantity = (int) $cart->items
                ->firstWhere('product_id', $productId)
                ?->quantity;
        }

        return [
            'item_count' => (int) $cart->items->sum('quantity'),
            'line_count' => $cart->items->count(),
            'product_id' => $productId,
            'line_quantity' => $lineQuantity,
            'items' => $cart->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => (int) $item->quantity,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
            ])->values()->all(),
        ];
    }

    private function respond(Request $request, Cart $cart, int $productId): JsonResponse|RedirectResponse
    {
        $summary = $this->cartSummary($cart, $productId);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($summary);
        }

        return back()->with('cart_summary', $summary);
    }
}

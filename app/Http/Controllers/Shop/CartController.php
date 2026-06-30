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
     * Agregar unidades al carrito (catálogo o detalle). Suma sobre la cantidad existente.
     */
    public function store(AddToCartRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);

        $this->addProduct->execute($cart, $product, $request->quantity());

        return $this->respond($request, $cart, 'Producto agregado al carrito.');
    }

    /**
     * Establecer cantidad absoluta (input numérico del detalle de producto).
     */
    public function update(UpdateCartItemRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);

        $this->updateQuantity->execute($cart, $product, $request->quantity());

        $message = $request->quantity() === 0
            ? 'Producto eliminado del carrito.'
            : 'Cantidad actualizada.';

        return $this->respond($request, $cart, $message);
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

        return $this->respond($request, $cart, 'Cantidad aumentada.');
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

        $message = $currentQuantity <= 1
            ? 'Producto eliminado del carrito.'
            : 'Cantidad disminuida.';

        return $this->respond($request, $cart, $message);
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
    private function cartSummary(Cart $cart): array
    {
        $cart->loadMissing(['items.product.inventory']);

        return [
            'item_count' => (int) $cart->items->sum('quantity'),
            'line_count' => $cart->items->count(),
            'items' => $cart->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
            ])->values()->all(),
        ];
    }

    private function respond(Request $request, Cart $cart, string $message): JsonResponse|RedirectResponse
    {
        $summary = $this->cartSummary($cart);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                ...$summary,
            ]);
        }

        return back()
            ->with('cart_status', $message)
            ->with('cart_summary', $summary);
    }
}

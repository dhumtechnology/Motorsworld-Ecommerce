<?php

namespace App\Actions\Shop;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Products\Product;
use App\Services\Products\ProductOfferPresenter;
use Illuminate\Support\Collection;

class GetPopularProductsAction
{
    public function __construct(
        private readonly ProductOfferPresenter $offerPresenter,
    ) {}

    /**
     * Top productos por ventas (motos y accesorios). Si no hay ventas, últimos activos.
     *
     * @return Collection<int, Product>
     */
    public function execute(int $limit = 10): Collection
    {
        $rankedIds = Product::query()
            ->toBase()
            ->select('products.id')
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('products.status', ProductStatus::Active->value)
            ->whereNotIn('orders.status', [
                OrderStatus::Cancelled->value,
                OrderStatus::Refunded->value,
            ])
            ->groupBy('products.id')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit($limit)
            ->pluck('id');

        if ($rankedIds->isEmpty()) {
            return Product::query()
                ->active()
                ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (Product $product) => $this->offerPresenter->apply($product));
        }

        $orderById = $rankedIds->values()->all();

        return Product::query()
            ->whereIn('id', $orderById)
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->id, $orderById, true))
            ->values()
            ->map(fn (Product $product) => $this->offerPresenter->apply($product));
    }
}

<?php

namespace App\Services\Products;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Orders\OrderItem;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RelatedProductsResolver
{
    private const DEFAULT_LIMIT = 8;

    /**
     * Productos relacionados para ficha de detalle.
     *
     * 1. Co-compra: aparecen en los mismos pedidos (señal más fuerte).
     * 2. Misma categoría + misma marca (modelo de vehículo).
     * 3. Misma categoría, priorizando los que más venden en esa categoría.
     *
     * @return Collection<int, Product>
     */
    public function resolve(Product $product, int $limit = self::DEFAULT_LIMIT): Collection
    {
        $rankedIds = $this->coPurchasedProductIds($product, $limit);

        if ($rankedIds->count() < $limit) {
            $rankedIds = $rankedIds->merge(
                $this->sameCategoryAndBrandProductIds($product, $limit, $rankedIds->all()),
            )->unique()->take($limit);
        }

        if ($rankedIds->count() < $limit) {
            $rankedIds = $rankedIds->merge(
                $this->sameCategoryProductIds($product, $limit, $rankedIds->all()),
            )->unique()->take($limit);
        }

        if ($rankedIds->isEmpty()) {
            return collect();
        }

        $order = $rankedIds->values()->all();

        return Product::query()
            ->active()
            ->whereIn('id', $order)
            ->with(['category', 'vehicleModel.brand', 'inventory', 'activeOffer', 'primaryImage'])
            ->get()
            ->sortBy(fn (Product $related): int => array_search($related->id, $order, true))
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function coPurchasedProductIds(Product $product, int $limit): Collection
    {
        return OrderItem::query()
            ->from('order_items as base_items')
            ->join('order_items as related_items', function ($join) use ($product) {
                $join->on('related_items.order_id', '=', 'base_items.order_id')
                    ->where('related_items.product_id', '!=', $product->id);
            })
            ->join('orders', 'orders.id', '=', 'base_items.order_id')
            ->join('products', 'products.id', '=', 'related_items.product_id')
            ->where('base_items.product_id', $product->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotIn('orders.status', [
                OrderStatus::Cancelled,
                OrderStatus::Refunded,
            ])
            ->groupBy('related_items.product_id')
            ->orderByRaw('COUNT(DISTINCT base_items.order_id) DESC')
            ->orderByRaw('SUM(related_items.quantity) DESC')
            ->limit($limit)
            ->pluck('related_items.product_id');
    }

    /**
     * @param  list<int>  $excludeIds
     * @return Collection<int, int>
     */
    private function sameCategoryAndBrandProductIds(
        Product $product,
        int $limit,
        array $excludeIds,
    ): Collection {
        if ($product->model_id === null) {
            return collect();
        }

        $brandId = $product->vehicleModel?->brand_id;

        if ($brandId === null) {
            return collect();
        }

        return $this->rankedProductIdsInScope(
            Product::query()
                ->active()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $excludeIds)
                ->whereHas(
                    'vehicleModel',
                    fn (Builder $query) => $query->where('brand_id', $brandId),
                ),
            $limit - count($excludeIds),
        );
    }

    /**
     * @param  list<int>  $excludeIds
     * @return Collection<int, int>
     */
    private function sameCategoryProductIds(
        Product $product,
        int $limit,
        array $excludeIds,
    ): Collection {
        return $this->rankedProductIdsInScope(
            Product::query()
                ->active()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $excludeIds),
            $limit - count($excludeIds),
        );
    }

    /**
     * Ordena candidatos por ventas en la categoría y luego por stock disponible.
     *
     * @param  Builder<Product>  $query
     * @return Collection<int, int>
     */
    private function rankedProductIdsInScope(Builder $query, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        return $query
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.id', '=', 'order_items.order_id')
                    ->whereNotIn('orders.status', [
                        OrderStatus::Cancelled->value,
                        OrderStatus::Refunded->value,
                    ]);
            })
            ->leftJoin('inventory', 'inventory.product_id', '=', 'products.id')
            ->groupBy('products.id')
            ->orderByRaw('COUNT(DISTINCT orders.id) DESC')
            ->orderByRaw('CASE WHEN COALESCE(inventory.available_stock, 0) > 0 THEN 0 ELSE 1 END')
            ->orderBy('products.id')
            ->limit($limit)
            ->pluck('products.id');
    }
}

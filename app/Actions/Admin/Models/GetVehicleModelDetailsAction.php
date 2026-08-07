<?php

namespace App\Actions\Admin\Models;

use App\Enums\Orders\OrderStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetVehicleModelDetailsAction
{
    private const PRODUCTS_PER_PAGE = 15;

    /**
     * @return array{
     *     vehicleModel: VehicleModel,
     *     products: LengthAwarePaginator,
     *     stats: array<string, mixed>
     * }
     */
    public function execute(VehicleModel $vehicleModel): array
    {
        $vehicleModel->load('brand');

        $productsQuery = Product::query()
            ->with(['inventory', 'primaryImage', 'category'])
            ->where('model_id', $vehicleModel->id);

        $stats = $this->buildStats($vehicleModel, (clone $productsQuery));

        $products = $productsQuery
            ->orderByDesc('id')
            ->paginate(self::PRODUCTS_PER_PAGE)
            ->withQueryString();

        return [
            'vehicleModel' => $vehicleModel,
            'products' => $products,
            'stats' => $stats,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $productsQuery
     * @return array<string, mixed>
     */
    private function buildStats(VehicleModel $vehicleModel, $productsQuery): array
    {
        $productsCount = (clone $productsQuery)->count();
        $activeCount = (clone $productsQuery)->where('status', ProductStatus::Active)->count();
        $outOfStock = (clone $productsQuery)
            ->whereDoesntHave('inventories', fn ($q) => $q->where('available_stock', '>', 0))
            ->count();

        $stock = (clone $productsQuery)
            ->toBase()
            ->leftJoin('inventory', 'products.id', '=', 'inventory.product_id')
            ->selectRaw('COALESCE(SUM(inventory.available_stock), 0) as available')
            ->selectRaw('COALESCE(SUM(inventory.reserved_stock), 0) as reserved')
            ->first();

        $excluded = [OrderStatus::Cancelled->value, OrderStatus::Refunded->value];

        $sales = Product::query()
            ->where('model_id', $vehicleModel->id)
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotIn('orders.status', $excluded)
            ->toBase()
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as units_sold')
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.unit_price), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->first();

        $appointmentsCount = $vehicleModel->appointments()->count();
        $avgPrice = (clone $productsQuery)->avg('price_amount');

        return [
            'products_count' => $productsCount,
            'active_count' => $activeCount,
            'out_of_stock' => $outOfStock,
            'available_stock' => (int) ($stock->available ?? 0),
            'reserved_stock' => (int) ($stock->reserved ?? 0),
            'units_sold' => (int) ($sales->units_sold ?? 0),
            'revenue' => (float) ($sales->revenue ?? 0),
            'orders_count' => (int) ($sales->orders_count ?? 0),
            'appointments_count' => $appointmentsCount,
            'avg_price' => round((float) ($avgPrice ?? 0), 2),
        ];
    }
}

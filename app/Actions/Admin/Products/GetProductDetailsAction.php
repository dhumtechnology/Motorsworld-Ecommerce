<?php

namespace App\Actions\Admin\Products;

use App\Enums\Inventory\InventoryMovementType;
use App\Enums\Orders\OrderStatus;
use App\Models\Products\InventoryMovement;
use App\Models\Products\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetProductDetailsAction
{
    private const MOVEMENTS_PER_PAGE = 15;

    /**
     * @return array{
     *     product: Product,
     *     movements: LengthAwarePaginator,
     *     stats: array<string, mixed>
     * }
     */
    public function execute(Product $product): array
    {
        $product->load([
            'category',
            'vehicleModel.brand',
            'inventory',
            'images',
            'activeOffer',
        ]);

        $movements = InventoryMovement::query()
            ->with(['creator:id,email', 'order:id'])
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->paginate(self::MOVEMENTS_PER_PAGE)
            ->withQueryString();

        $movementTotals = InventoryMovement::query()
            ->where('product_id', $product->id)
            ->toBase()
            ->selectRaw('type, COALESCE(SUM(quantity), 0) as total_qty, COUNT(*) as total_count')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $entriesQty = (int) ($movementTotals->get(InventoryMovementType::Entry->value)?->total_qty ?? 0);
        $exitsQty = (int) ($movementTotals->get(InventoryMovementType::Exit->value)?->total_qty ?? 0);
        $entriesCount = (int) ($movementTotals->get(InventoryMovementType::Entry->value)?->total_count ?? 0);
        $exitsCount = (int) ($movementTotals->get(InventoryMovementType::Exit->value)?->total_count ?? 0);

        $excludedOrderStatuses = [
            OrderStatus::Cancelled->value,
            OrderStatus::Refunded->value,
        ];

        $sales = $product->orderItems()
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', $excludedOrderStatuses))
            ->selectRaw('COALESCE(SUM(quantity), 0) as units_sold')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as revenue')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('COUNT(DISTINCT order_id) as orders_count')
            ->first();

        $reviewStats = $product->reviews()
            ->selectRaw('COUNT(*) as reviews_count')
            ->selectRaw('COALESCE(AVG(stars), 0) as avg_stars')
            ->first();

        $lastMovementAt = InventoryMovement::query()
            ->where('product_id', $product->id)
            ->max('created_at');

        $available = (int) ($product->inventory?->available_stock ?? 0);
        $reserved = (int) ($product->inventory?->reserved_stock ?? 0);
        $totalStock = (int) ($product->inventory?->total_stock ?? ($available + $reserved));

        return [
            'product' => $product,
            'movements' => $movements,
            'stats' => [
                'available_stock' => $available,
                'reserved_stock' => $reserved,
                'total_stock' => $totalStock,
                'entries_qty' => $entriesQty,
                'exits_qty' => $exitsQty,
                'entries_count' => $entriesCount,
                'exits_count' => $exitsCount,
                'net_movement' => $entriesQty - $exitsQty,
                'units_sold' => (int) ($sales->units_sold ?? 0),
                'revenue' => (float) ($sales->revenue ?? 0),
                'orders_count' => (int) ($sales->orders_count ?? 0),
                'line_count' => (int) ($sales->line_count ?? 0),
                'reviews_count' => (int) ($reviewStats->reviews_count ?? 0),
                'avg_stars' => round((float) ($reviewStats->avg_stars ?? 0), 1),
                'cart_count' => $product->cartItems()->count(),
                'images_count' => $product->images->count(),
                'last_movement_at' => $lastMovementAt,
            ],
        ];
    }
}

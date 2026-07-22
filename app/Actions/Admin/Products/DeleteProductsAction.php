<?php

namespace App\Actions\Admin\Products;

use App\Models\Cart\CartItem;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteProductsAction
{
    /**
     * @param  list<int>  $productIds
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if ($productIds === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($productIds) {
            $products = Product::query()
                ->withCount('orderItems')
                ->whereIn('id', $productIds)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($products as $product) {
                if ($product->order_items_count > 0) {
                    $blocked[] = $product->name ?: $product->sku;

                    continue;
                }

                $deletableIds[] = $product->id;
            }

            if ($deletableIds !== []) {
                CartItem::query()->whereIn('product_id', $deletableIds)->delete();
                Product::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar productos vinculados a pedidos: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

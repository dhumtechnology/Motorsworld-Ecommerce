<?php

namespace App\Actions\Admin\Products;

use App\Models\Cart\CartItem;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductsAction
{
    /**
     * @param  list<int>  $productIds
     * @return array{deleted: int}
     */
    public function execute(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if ($productIds === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($productIds) {
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->get();

            foreach ($products as $product) {
                CartItem::query()->where('product_id', $product->id)->delete();
                $product->delete();
            }

            return [
                'deleted' => $products->count(),
            ];
        });
    }
}

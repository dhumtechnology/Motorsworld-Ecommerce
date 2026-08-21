<?php

namespace App\Actions\Admin\Products;

use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestoreProductsAction
{
    /**
     * @param  list<int>  $productIds
     * @return array{restored: int}
     */
    public function execute(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if ($productIds === []) {
            return ['restored' => 0];
        }

        return DB::transaction(function () use ($productIds) {
            $products = Product::query()
                ->onlyTrashed()
                ->whereIn('id', $productIds)
                ->get();

            if ($products->isEmpty()) {
                throw ValidationException::withMessages([
                    'ids' => 'No se encontraron productos archivados para restaurar.',
                ]);
            }

            foreach ($products as $product) {
                $product->restore();
            }

            return [
                'restored' => $products->count(),
            ];
        });
    }
}

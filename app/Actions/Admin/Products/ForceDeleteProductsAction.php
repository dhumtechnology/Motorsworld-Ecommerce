<?php

namespace App\Actions\Admin\Products;

use App\Models\Cart\CartItem;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ForceDeleteProductsAction
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
                ->onlyTrashed()
                ->with(['images'])
                ->withCount('orderItems')
                ->whereIn('id', $productIds)
                ->get();

            $blocked = [];
            $deleted = 0;

            foreach ($products as $product) {
                if ($product->order_items_count > 0) {
                    $blocked[] = $product->name ?: $product->sku;

                    continue;
                }

                foreach ($product->images as $image) {
                    $this->deleteStoredFile($image->path);
                }

                if ($product->technical_sheet) {
                    $this->deleteStoredFile($product->technical_sheet);
                }

                Storage::disk('public')->deleteDirectory("products/{$product->id}");

                CartItem::query()->where('product_id', $product->id)->delete();
                $product->forceDelete();
                $deleted++;
            }

            if ($deleted === 0 && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar permanentemente productos vinculados a pedidos: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => $deleted,
                'blocked' => $blocked,
            ];
        });
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relative = str_starts_with($path, '/storage/')
            ? substr($path, strlen('/storage/'))
            : (str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : null);

        if ($relative === null || str_contains($relative, '://')) {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}

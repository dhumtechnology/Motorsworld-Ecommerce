<?php

namespace App\Actions\Admin\Brands;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Models\Products\Brand;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteBrandsAction
{
    public function __construct(
        private readonly DeleteProductsAction $deleteProducts,
    ) {}

    /**
     * @param  list<int>  $brandIds
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $brandIds): array
    {
        $brandIds = array_values(array_unique(array_map('intval', $brandIds)));

        if ($brandIds === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($brandIds) {
            $brands = Brand::query()
                ->with('vehicleModels:id,brand_id')
                ->whereIn('id', $brandIds)
                ->get();

            $blocked = [];
            $deletable = [];

            foreach ($brands as $brand) {
                $modelIds = $brand->vehicleModels->pluck('id')->all();
                $products = $modelIds === []
                    ? collect()
                    : Product::query()
                        ->withCount('orderItems')
                        ->whereIn('model_id', $modelIds)
                        ->get();

                if ($products->contains(fn (Product $product) => $product->order_items_count > 0)) {
                    $blocked[] = $brand->name;

                    continue;
                }

                if ($products->isNotEmpty()) {
                    $this->deleteProducts->execute($products->pluck('id')->all());
                }

                $deletable[] = $brand;
            }

            foreach ($deletable as $brand) {
                if ($brand->image) {
                    $this->deleteStoredFile($brand->image);
                }

                // models.brand_id tiene cascadeOnDelete → elimina modelos asociados.
                $brand->delete();
            }

            if ($deletable === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar marcas con productos vinculados a pedidos: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletable),
                'blocked' => $blocked,
            ];
        });
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '' || str_contains($path, '://')) {
            return;
        }

        $relative = str_starts_with($path, '/storage/')
            ? substr($path, strlen('/storage/'))
            : (str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : null);

        if ($relative === null) {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}

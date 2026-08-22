<?php

namespace App\Actions\Admin\Brands;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Models\Products\Brand;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteBrandsAction
{
    public function __construct(
        private readonly DeleteProductsAction $deleteProducts,
        private readonly NormalizeBrandSortOrderAction $normalizeBrandSortOrder,
    ) {}

    /**
     * @param  list<int>  $brandIds
     * @return array{deleted: int}
     */
    public function execute(array $brandIds): array
    {
        $brandIds = array_values(array_unique(array_map('intval', $brandIds)));

        if ($brandIds === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($brandIds) {
            $brands = Brand::query()
                ->with('vehicleModels:id,brand_id')
                ->whereIn('id', $brandIds)
                ->get();

            foreach ($brands as $brand) {
                $modelIds = $brand->vehicleModels->pluck('id')->all();

                if ($modelIds !== []) {
                    $productIds = Product::query()
                        ->whereIn('model_id', $modelIds)
                        ->pluck('id')
                        ->all();

                    if ($productIds !== []) {
                        $this->deleteProducts->execute($productIds);
                    }
                }

                if ($brand->image) {
                    $this->deleteStoredFile($brand->image);
                }

                // models.brand_id tiene cascadeOnDelete → elimina modelos asociados.
                $brand->delete();
            }

            if ($brands->isNotEmpty()) {
                $this->normalizeBrandSortOrder->execute();
            }

            return [
                'deleted' => $brands->count(),
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

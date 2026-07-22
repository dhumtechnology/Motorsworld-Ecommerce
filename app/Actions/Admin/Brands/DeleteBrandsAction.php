<?php

namespace App\Actions\Admin\Brands;

use App\Models\Products\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteBrandsAction
{
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
                ->withCount('vehicleModels')
                ->whereIn('id', $brandIds)
                ->get();

            $blocked = [];
            $deletable = [];

            foreach ($brands as $brand) {
                if ($brand->vehicle_models_count > 0) {
                    $blocked[] = $brand->name;

                    continue;
                }

                $deletable[] = $brand;
            }

            foreach ($deletable as $brand) {
                if ($brand->image) {
                    $this->deleteStoredFile($brand->image);
                }

                $brand->delete();
            }

            if ($deletable === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar marcas con modelos asociados: '.implode(', ', $blocked).'.',
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

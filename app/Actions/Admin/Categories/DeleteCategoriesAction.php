<?php

namespace App\Actions\Admin\Categories;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Models\Products\Category;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteCategoriesAction
{
    public function __construct(
        private readonly DeleteProductsAction $deleteProducts,
    ) {}

    /**
     * @param  list<int>  $categoryIds
     * @return array{deleted: int}
     */
    public function execute(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));

        if ($categoryIds === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($categoryIds) {
            $categories = Category::query()
                ->whereIn('id', $categoryIds)
                ->get();

            foreach ($categories as $category) {
                $productIds = Product::query()
                    ->where('category_id', $category->id)
                    ->pluck('id')
                    ->all();

                if ($productIds !== []) {
                    $this->deleteProducts->execute($productIds);
                }

                if ($category->image) {
                    $this->deleteStoredFile($category->image);
                }

                $category->delete();
            }

            return [
                'deleted' => $categories->count(),
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

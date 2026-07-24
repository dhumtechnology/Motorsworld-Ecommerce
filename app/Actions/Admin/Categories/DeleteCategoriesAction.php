<?php

namespace App\Actions\Admin\Categories;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Models\Products\Category;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCategoriesAction
{
    public function __construct(
        private readonly DeleteProductsAction $deleteProducts,
    ) {}

    /**
     * @param  list<int>  $categoryIds
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));

        if ($categoryIds === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($categoryIds) {
            $categories = Category::query()
                ->whereIn('id', $categoryIds)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($categories as $category) {
                $products = Product::query()
                    ->withCount('orderItems')
                    ->where('category_id', $category->id)
                    ->get();

                if ($products->contains(fn (Product $product) => $product->order_items_count > 0)) {
                    $blocked[] = $category->name;

                    continue;
                }

                if ($products->isNotEmpty()) {
                    $this->deleteProducts->execute($products->pluck('id')->all());
                }

                $deletableIds[] = $category->id;
            }

            if ($deletableIds !== []) {
                Category::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar categorías con productos vinculados a pedidos: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

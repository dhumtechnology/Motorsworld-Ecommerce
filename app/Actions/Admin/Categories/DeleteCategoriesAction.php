<?php

namespace App\Actions\Admin\Categories;

use App\Models\Products\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCategoriesAction
{
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
                ->withCount('products')
                ->whereIn('id', $categoryIds)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($categories as $category) {
                if ($category->products_count > 0) {
                    $blocked[] = $category->name;

                    continue;
                }

                $deletableIds[] = $category->id;
            }

            if ($deletableIds !== []) {
                Category::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar categorías con productos asociados: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

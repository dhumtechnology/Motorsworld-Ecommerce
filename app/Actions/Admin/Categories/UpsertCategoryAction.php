<?php

namespace App\Actions\Admin\Categories;

use App\Models\Products\Category;
use Illuminate\Support\Facades\DB;

class UpsertCategoryAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?Category $category = null): Category
    {
        return DB::transaction(function () use ($attributes, $category) {
            if ($category === null) {
                $category = Category::query()->create($attributes);
            } else {
                $category->update($attributes);
            }

            return $category->fresh();
        });
    }
}

<?php

namespace App\Actions\Admin\Categories;

use App\Models\Products\Category;
use Illuminate\Support\Facades\DB;

class ReorderCategoriesAction
{
    /**
     * @param  list<int>  $ids
     */
    public function execute(array $ids): void
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => is_int($id) && $id > 0)));

        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids) {
            $tempOffset = (int) (Category::query()->max('sort_order') ?? 0) + count($ids) + 1;

            foreach ($ids as $index => $id) {
                Category::query()->whereKey($id)->update(['sort_order' => $tempOffset + $index]);
            }

            foreach ($ids as $index => $id) {
                Category::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
    }
}

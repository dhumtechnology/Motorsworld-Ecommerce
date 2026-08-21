<?php

namespace App\Actions\Admin\Brands;

use App\Models\Products\Brand;
use Illuminate\Support\Facades\DB;

class ReorderBrandsAction
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
            $tempOffset = (int) (Brand::query()->max('sort_order') ?? 0) + count($ids) + 1;

            foreach ($ids as $index => $id) {
                Brand::query()->whereKey($id)->update(['sort_order' => $tempOffset + $index]);
            }

            foreach ($ids as $index => $id) {
                Brand::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
    }
}

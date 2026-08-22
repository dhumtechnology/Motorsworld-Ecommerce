<?php

namespace App\Actions\Admin\Brands;

use App\Models\Products\Brand;

class NormalizeBrandSortOrderAction
{
    public function __construct(
        private readonly ReorderBrandsAction $reorderBrands,
    ) {}

    public function execute(): void
    {
        $ids = Brand::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->reorderBrands->execute($ids);
    }
}

<?php

namespace App\Actions\Shop;

use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use Illuminate\Support\Collection;

class GetHomePageDataAction
{
    private const MOTOS_CATEGORY = 'MOTOS';

    private const POPULAR_LIMIT = 4;

    public function __construct(
        private readonly GetPopularProductsAction $popularProducts,
    ) {}

    /**
     * @return array{
     *     popularProducts: Collection<int, Product>,
     *     brands: Collection<int, Brand>,
     *     categories: Collection<int, Category>
     * }
     */
    public function execute(): array
    {
        return [
            'popularProducts' => $this->popularProducts->execute(self::POPULAR_LIMIT),
            'brands' => $this->brands(),
            'categories' => $this->categories(),
        ];
    }

    /**
     * @return Collection<int, Brand>
     */
    private function brands(): Collection
    {
        return Brand::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'image']);
    }

    /**
     * Categorías con imagen para el home (MOTOS primero).
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderByRaw('CASE WHEN UPPER(name) = ? THEN 0 ELSE 1 END', [self::MOTOS_CATEGORY])
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'image']);
    }
}

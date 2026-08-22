<?php

namespace App\Actions\Shop;

use App\Models\Content\HomeBanner;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use Illuminate\Support\Collection;

class GetHomePageDataAction
{
    private const POPULAR_LIMIT = 10;

    public function __construct(
        private readonly GetPopularProductsAction $popularProducts,
    ) {}

    /**
     * @return array{
     *     popularProducts: Collection<int, Product>,
     *     brands: Collection<int, Brand>,
     *     categories: Collection<int, Category>,
     *     heroSlides: list<string>
     * }
     */
    public function execute(): array
    {
        return [
            'popularProducts' => $this->popularProducts->execute(self::POPULAR_LIMIT),
            'brands' => $this->brands(),
            'categories' => $this->categories(),
            'heroSlides' => $this->heroSlides(),
        ];
    }

    /**
     * @return list<string>
     */
    private function heroSlides(): array
    {
        $slides = HomeBanner::query()
            ->visibleOnHome()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('image')
            ->filter(fn (?string $image) => filled($image))
            ->values()
            ->all();

        if ($slides === []) {
            return HomeBanner::defaultSlideUrls();
        }

        return $slides;
    }

    /**
     * @return Collection<int, Brand>
     */
    private function brands(): Collection
    {
        return Brand::query()
            ->withLogo()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'image']);
    }

    /**
     * Categorías con imagen para el home, ordenadas desde el admin.
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'image']);
    }
}

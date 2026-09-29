<?php

namespace App\Models\Products;

use App\Support\QueryResultCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'description', 'image', 'sort_order'])]
class Category extends Model
{
    private const MOTOCICLETAS = 'MOTOCICLETAS';

    /**
     * Claves de QueryResultCache que dependen del listado de categorías.
     */
    private const CACHE_KEYS = [
        'shop.header.search_categories.v5',
        'catalog.motocicletas_category_id',
        'catalog.filter_options.categories.all.motos',
        'catalog.filter_options.categories.all.accesorios',
        'catalog.filter_options.categories.all.all',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCachedLists());
        static::deleted(fn () => static::forgetCachedLists());
    }

    public static function forgetCachedLists(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public static function motocicletasId(): ?int
    {
        return QueryResultCache::remember(
            'catalog.motocicletas_category_id',
            fn (): ?int => static::query()
                ->whereRaw('UPPER(name) = ?', [self::MOTOCICLETAS])
                ->value('id'),
        );
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

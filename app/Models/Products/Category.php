<?php

namespace App\Models\Products;

use App\Support\QueryResultCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'image'])]
class Category extends Model
{
    private const MOTOCICLETAS = 'MOTOCICLETAS';

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

<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['name', 'image', 'sort_order'])]
class Brand extends Model
{
    /**
     * @param  Builder<Brand>  $query
     */
    public function scopeWithLogo(Builder $query): void
    {
        $query
            ->whereNotNull('image')
            ->where('image', '!=', '');
    }

    /**
     * Marcas con al menos un producto en la categoría Motocicletas.
     *
     * @param  Builder<Brand>  $query
     */
    public function scopeWithMotorcycleProducts(Builder $query): void
    {
        $categoryId = Category::motocicletasId();

        if ($categoryId === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereHas(
            'products',
            fn (Builder $productQuery) => $productQuery->where('category_id', $categoryId),
        );
    }

    public function hasLogo(): bool
    {
        return filled($this->image);
    }
    /**
     * @return HasMany<VehicleModel, $this>
     */
    public function vehicleModels(): HasMany
    {
        return $this->hasMany(VehicleModel::class);
    }

    /**
     * @return HasManyThrough<Product, VehicleModel, $this>
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            VehicleModel::class,
            'brand_id',
            'model_id',
            'id',
            'id',
        );
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
}

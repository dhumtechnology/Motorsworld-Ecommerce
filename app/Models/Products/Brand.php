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

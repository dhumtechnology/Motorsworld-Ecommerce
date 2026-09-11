<?php

namespace App\Models\Products;

use App\Models\Appointments\Appointment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'brand_id'])]
class VehicleModel extends Model
{
    protected $table = 'models';

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'model_id');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'vehicle_model_id');
    }

    /**
     * El producto guarda la marca vía model_id. Si hay marca y ningún modelo válido,
     * usa el primero de esa marca o crea uno con el nombre de la marca.
     */
    public static function idForBrandSelection(?int $brandId, ?int $modelId): ?int
    {
        if ($brandId === null || $brandId <= 0) {
            return null;
        }

        if ($modelId !== null && $modelId > 0) {
            $belongs = static::query()
                ->whereKey($modelId)
                ->where('brand_id', $brandId)
                ->exists();

            if ($belongs) {
                return $modelId;
            }
        }

        $existingId = static::query()
            ->where('brand_id', $brandId)
            ->orderBy('name')
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $brandName = Brand::query()->whereKey($brandId)->value('name');
        $name = filled($brandName) ? (string) $brandName : 'General';

        return (int) static::query()->create([
            'brand_id' => $brandId,
            'name' => $name,
        ])->id;
    }

    /**
     * Modelos con al menos un producto en la categoría Motocicletas.
     *
     * @param  Builder<VehicleModel>  $query
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
}

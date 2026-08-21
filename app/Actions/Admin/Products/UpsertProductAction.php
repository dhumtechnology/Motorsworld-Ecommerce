<?php

namespace App\Actions\Admin\Products;

use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Models\Products\Color;
use App\Models\Products\Inventory;
use App\Models\Products\InventoryMovement;
use App\Models\Products\Product;
use App\Models\Products\ProductImage;
use App\Models\Products\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpsertProductAction
{
    public function __construct(
        private readonly GenerateProductSkuAction $generateSku,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{
     *     id?: int|null,
     *     sku?: string|null,
     *     color_ids?: list<int>,
     *     new_colors?: list<array{name: string, hex?: string|null}>,
     *     available_stock: int,
     *     primary_image?: UploadedFile|null,
     *     secondary_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }>  $variants
     * @param  list<int>  $removeVariantIds
     * @param  array{
     *     available_stock?: int,
     *     primary_image?: UploadedFile|null,
     *     secondary_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }  $defaultGallery
     */
    public function execute(
        array $attributes,
        ?Product $product = null,
        ?UploadedFile $technicalSheet = null,
        bool $removeTechnicalSheet = false,
        array $variants = [],
        array $removeVariantIds = [],
        array $defaultGallery = [],
    ): Product {
        return DB::transaction(function () use (
            $attributes,
            $product,
            $technicalSheet,
            $removeTechnicalSheet,
            $variants,
            $removeVariantIds,
            $defaultGallery,
        ) {
            unset($attributes['image'], $attributes['technical_sheet']);

            if ($product === null) {
                $product = Product::query()->create($attributes);
            } else {
                $product->update($attributes);
            }

            $willHaveColoredVariants = $this->willHaveColoredVariants(
                $product,
                $variants,
                $removeVariantIds,
            );

            $this->removeVariants($product, $removeVariantIds, $willHaveColoredVariants);

            $hadDefaultImages = $this->defaultImageQuery($product)->exists();

            $coloredVariants = $this->syncColoredVariants($product, $variants);

            if ($coloredVariants === []) {
                $this->syncDefaultGallery($product, $defaultGallery);
                $this->syncStandardVariant(
                    $product,
                    max(0, (int) ($defaultGallery['available_stock'] ?? 0)),
                );
            } else {
                if ($hadDefaultImages) {
                    $this->migrateDefaultImagesToVariant($product, $coloredVariants[0]);
                }

                $this->removeColorlessVariants($product, array_map(
                    static fn (ProductVariant $variant): int => $variant->id,
                    $coloredVariants,
                ));
            }

            $this->syncTechnicalSheet($product, $technicalSheet, $removeTechnicalSheet);
            $this->syncLegacyImageColumn($product);

            return $product->fresh([
                'inventories',
                'category',
                'vehicleModel',
                'images',
                'variants.colors',
                'variants.inventory',
                'variants.images',
            ]);
        });
    }

    /**
     * @param  list<array{color_ids?: list<int>, new_colors?: list<array{name: string, hex?: string|null}>}>  $variants
     * @param  list<int>  $removeVariantIds
     */
    private function willHaveColoredVariants(Product $product, array $variants, array $removeVariantIds): bool
    {
        foreach ($variants as $payload) {
            $colorIds = array_values(array_filter(array_map(
                'intval',
                is_array($payload['color_ids'] ?? null) ? $payload['color_ids'] : [],
            )));

            if ($colorIds !== []) {
                return true;
            }

            foreach ($payload['new_colors'] ?? [] as $newColor) {
                if (trim((string) ($newColor['name'] ?? '')) !== '') {
                    return true;
                }
            }
        }

        return $product->variants()
            ->whereNotIn('id', $removeVariantIds === [] ? [0] : $removeVariantIds)
            ->whereHas('colors')
            ->exists();
    }

    /**
     * @param  list<int>  $variantIds
     */
    private function removeVariants(Product $product, array $variantIds, bool $willHaveColoredVariants): void
    {
        if ($variantIds === []) {
            return;
        }

        $variants = $product->variants()
            ->with(['images', 'orderItems', 'colors'])
            ->whereIn('id', $variantIds)
            ->get();

        foreach ($variants as $variant) {
            if ($variant->orderItems()->exists()) {
                throw ValidationException::withMessages([
                    'remove_variant_ids' => "No se puede eliminar la combinación «{$variant->colorLabel()}» porque tiene pedidos.",
                ]);
            }

            foreach ($variant->images as $image) {
                if ($willHaveColoredVariants) {
                    $this->deleteStoredFile($image->path);
                    $image->delete();
                } else {
                    $image->forceFill(['product_variant_id' => null])->save();
                }
            }

            $variant->colors()->detach();
            $variant->inventory()?->delete();
            $variant->delete();
        }
    }

    /**
     * @param  list<array{
     *     id?: int|null,
     *     sku?: string|null,
     *     color_ids?: list<int>,
     *     new_colors?: list<array{name: string, hex?: string|null}>,
     *     available_stock: int,
     *     primary_image?: UploadedFile|null,
     *     secondary_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }>  $variants
     * @return list<ProductVariant>
     */
    private function syncColoredVariants(Product $product, array $variants): array
    {
        $synced = [];

        foreach ($variants as $index => $payload) {
            $colorIds = $this->resolveColorIds(
                $payload['color_ids'] ?? [],
                $payload['new_colors'] ?? [],
            );

            if ($colorIds === []) {
                throw ValidationException::withMessages([
                    "variants.{$index}.color_ids" => 'Cada combinación debe tener al menos un color. Quita la fila si ya no la necesitas.',
                ]);
            }

            $synced[] = $this->upsertVariant($product, $payload, $colorIds);
        }

        return $synced;
    }

    /**
     * @param  array{
     *     id?: int|null,
     *     available_stock: int,
     *     primary_image?: UploadedFile|null,
     *     secondary_images?: list<UploadedFile>,
     *     image_order?: list<string>,
     *     new_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }  $payload
     * @param  list<int>  $colorIds
     */
    private function upsertVariant(Product $product, array $payload, array $colorIds): ProductVariant
    {
        $colors = Color::query()->whereIn('id', $colorIds)->get()->sortBy(
            fn (Color $color) => array_search($color->id, $colorIds, true),
        )->values();

        $colorNames = $colors->pluck('name')->all();
        $label = $colors->isEmpty()
            ? 'Estándar'
            : $colors->pluck('name')->implode(' / ');

        $variantId = isset($payload['id']) ? (int) $payload['id'] : null;
        $variant = null;

        if ($variantId) {
            $variant = $product->variants()->with('inventory')->where('id', $variantId)->first();
        }

        $preferredSku = trim((string) ($payload['sku'] ?? ''));
        $sku = $preferredSku !== ''
            ? ($this->generateSku)->resolveVariantSku($preferredSku, $variant)
            : ($this->generateSku)->forVariant($product, $colorNames, $variant);

        if ($variant === null) {
            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => $sku,
                'name' => $label,
                'is_active' => true,
            ]);
        } else {
            $variant->update([
                'sku' => $sku,
                'name' => $label,
            ]);
        }

        $sync = [];
        foreach ($colorIds as $index => $colorId) {
            $sync[$colorId] = ['sort_order' => $index];
        }
        $variant->colors()->sync($sync);

        $this->syncVariantStock($product, $variant, max(0, (int) ($payload['available_stock'] ?? 0)));
        $this->syncOrderedGalleryImages(
            $product,
            $variant,
            $payload['image_order'] ?? [],
            $payload['new_images'] ?? [],
            $payload['remove_image_ids'] ?? [],
        );

        return $variant->fresh(['colors', 'inventory', 'images']) ?? $variant;
    }

    private function syncStandardVariant(Product $product, int $availableStock): void
    {
        $product->load(['variants.colors', 'variants.inventory', 'variants.orderItems']);

        $standard = $product->variants->first(
            fn (ProductVariant $variant) => $variant->colors->isEmpty(),
        );

        if ($standard === null) {
            $standard = ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => 'Estándar',
                'is_active' => true,
            ]);
            $standard->setRelation('inventory', null);
        } else {
            $standard->update([
                'sku' => $product->sku,
                'name' => 'Estándar',
                'is_active' => true,
            ]);
        }

        $standard->colors()->sync([]);
        $this->syncVariantStock($product, $standard->fresh(['inventory']) ?? $standard, $availableStock);

        // Las imágenes del producto sin colores viven a nivel producto (variant_id null).
        $standard->load('images');
        foreach ($standard->images as $image) {
            $image->forceFill(['product_variant_id' => null])->save();
        }

        foreach ($product->variants as $variant) {
            if ($variant->id === $standard->id || $variant->colors->isNotEmpty()) {
                continue;
            }

            if ($variant->orderItems()->exists()) {
                continue;
            }

            foreach ($variant->images as $image) {
                $image->forceFill(['product_variant_id' => null])->save();
            }

            $variant->colors()->detach();
            $variant->inventory()?->delete();
            $variant->delete();
        }
    }

    /**
     * @param  list<int>  $keepVariantIds
     */
    private function removeColorlessVariants(Product $product, array $keepVariantIds): void
    {
        $product->load(['variants.colors', 'variants.images', 'variants.orderItems']);

        foreach ($product->variants as $variant) {
            if (in_array($variant->id, $keepVariantIds, true)) {
                continue;
            }

            if ($variant->colors->isNotEmpty()) {
                continue;
            }

            if ($variant->orderItems()->exists()) {
                throw ValidationException::withMessages([
                    'variants' => 'No se puede quitar la variante estándar porque tiene pedidos. Asigna colores a esa variante o contacta soporte.',
                ]);
            }

            foreach ($variant->images as $image) {
                $this->deleteStoredFile($image->path);
                $image->delete();
            }

            $variant->colors()->detach();
            $variant->inventory()?->delete();
            $variant->delete();
        }
    }

    private function migrateDefaultImagesToVariant(Product $product, ProductVariant $variant): void
    {
        $defaultImages = $this->defaultImageQuery($product)
            ->orderBy('sort_order')
            ->get();

        if ($defaultImages->isEmpty()) {
            return;
        }

        $hasPrimaryOnVariant = $variant->images()->where('is_primary', true)->exists();

        foreach ($defaultImages as $index => $image) {
            if ((int) $image->product_variant_id === (int) $variant->id) {
                continue;
            }

            $isPrimary = (bool) $image->is_primary;

            if ($hasPrimaryOnVariant) {
                $isPrimary = false;
            } elseif ($isPrimary) {
                $hasPrimaryOnVariant = true;
            } elseif ($index === 0 && ! $hasPrimaryOnVariant) {
                $isPrimary = true;
                $hasPrimaryOnVariant = true;
            }

            $image->forceFill([
                'product_variant_id' => $variant->id,
                'is_primary' => $isPrimary,
            ])->save();
        }

        if ($hasPrimaryOnVariant) {
            $primary = $variant->images()->where('is_primary', true)->orderBy('sort_order')->first();
            if ($primary) {
                $variant->images()
                    ->where('id', '!=', $primary->id)
                    ->update(['is_primary' => false]);
            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Products\ProductImage, Product>
     */
    private function defaultImageQuery(Product $product)
    {
        return $product->images()
            ->where(function ($query) use ($product) {
                $query->whereNull('product_variant_id')
                    ->orWhereIn(
                        'product_variant_id',
                        $product->variants()->whereDoesntHave('colors')->select('id'),
                    );
            });
    }

    /**
     * @param  array{
     *     available_stock?: int,
     *     image_order?: list<string>,
     *     new_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }  $gallery
     */
    private function syncDefaultGallery(Product $product, array $gallery): void
    {
        $this->syncOrderedGalleryImages(
            $product,
            null,
            $gallery['image_order'] ?? [],
            $gallery['new_images'] ?? [],
            $gallery['remove_image_ids'] ?? [],
        );
    }

    /**
     * @param  list<string>  $imageOrder
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removeImageIds
     */
    private function syncOrderedGalleryImages(
        Product $product,
        ?ProductVariant $variant,
        array $imageOrder,
        array $newImages,
        array $removeImageIds,
    ): void {
        if ($removeImageIds !== []) {
            $images = $variant === null
                ? $this->defaultImageQuery($product)->whereIn('id', $removeImageIds)->get()
                : $variant->images()->whereIn('id', $removeImageIds)->get();

            foreach ($images as $image) {
                $this->deleteStoredFile($image->path);
                $image->delete();
            }
        }

        if ($imageOrder === []) {
            return;
        }

        $newIndex = 0;

        foreach ($imageOrder as $position => $token) {
            if (str_starts_with($token, 'existing:')) {
                $imageId = (int) substr($token, strlen('existing:'));

                $image = $variant === null
                    ? $this->defaultImageQuery($product)->whereKey($imageId)->first()
                    : $variant->images()->whereKey($imageId)->first();

                if ($image === null) {
                    continue;
                }

                $image->forceFill([
                    'sort_order' => $position,
                    'is_primary' => $position === 0,
                ])->save();

                continue;
            }

            if (! str_starts_with($token, 'new:')) {
                continue;
            }

            $file = $newImages[$newIndex] ?? null;
            $newIndex++;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'path' => $this->storeUploadedFile($product, $file),
                'sort_order' => $position,
                'is_primary' => $position === 0,
            ]);
        }

        $imageQuery = $variant === null
            ? $this->defaultImageQuery($product)
            : $variant->images();

        $imageQuery
            ->where('is_primary', true)
            ->where('sort_order', '!=', 0)
            ->update(['is_primary' => false]);

        $primary = $imageQuery->orderBy('sort_order')->first();
        if ($primary !== null) {
            $imageQuery
                ->where('id', '!=', $primary->id)
                ->update(['is_primary' => false]);
            $primary->forceFill(['is_primary' => true])->save();
        }
    }

    private function syncVariantStock(Product $product, ProductVariant $variant, int $availableStock): void
    {
        $variant->loadMissing('inventory');

        $previousAvailable = (int) ($variant->inventory?->available_stock ?? 0);
        $reservedStock = (int) ($variant->inventory?->reserved_stock ?? 0);

        Inventory::query()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'product_id' => $product->id,
                'available_stock' => $availableStock,
                'reserved_stock' => $reservedStock,
                'total_stock' => $availableStock + $reservedStock,
            ],
        );

        $this->recordStockAdjustment($product, $variant, $previousAvailable, $availableStock);
    }

    /**
     * @param  list<int>  $colorIds
     * @param  list<array{name: string, hex?: string|null}>  $newColors
     * @return list<int>
     */
    private function resolveColorIds(array $colorIds, array $newColors): array
    {
        $ids = array_values(array_unique(array_map('intval', $colorIds)));

        foreach ($newColors as $newColor) {
            $name = trim((string) ($newColor['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $hex = isset($newColor['hex']) ? trim((string) $newColor['hex']) : null;
            if ($hex === '') {
                $hex = null;
            }

            $color = Color::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

            if ($color === null) {
                $color = Color::query()->create([
                    'name' => $name,
                    'hex' => $hex,
                ]);
            } elseif ($hex !== null && $color->hex !== $hex) {
                $color->update(['hex' => $hex]);
            }

            $ids[] = $color->id;
        }

        return array_values(array_unique($ids));
    }

    private function recordStockAdjustment(
        Product $product,
        ProductVariant $variant,
        int $previousAvailable,
        int $newAvailable,
    ): void {
        $delta = $newAvailable - $previousAvailable;

        if ($delta === 0) {
            return;
        }

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'type' => $delta > 0 ? InventoryMovementType::Entry : InventoryMovementType::Exit,
            'reason' => InventoryMovementReason::Adjustment,
            'quantity' => abs($delta),
            'notes' => $previousAvailable === 0 && $delta > 0 && $variant->wasRecentlyCreated
                ? 'Stock inicial ('.$variant->colorLabel().')'
                : 'Ajuste desde ficha de producto ('.$variant->colorLabel().')',
            'created_by' => auth()->id(),
        ]);
    }

    private function syncTechnicalSheet(
        Product $product,
        ?UploadedFile $technicalSheet,
        bool $removeTechnicalSheet,
    ): void {
        if ($removeTechnicalSheet && $product->technical_sheet) {
            $this->deleteStoredFile($product->technical_sheet);
            $product->forceFill(['technical_sheet' => null])->save();
        }

        if ($technicalSheet === null) {
            return;
        }

        if ($product->technical_sheet) {
            $this->deleteStoredFile($product->technical_sheet);
        }

        $product->forceFill([
            'technical_sheet' => $this->storeUploadedFile($product, $technicalSheet),
        ])->save();
    }

    private function storeUploadedFile(Product $product, UploadedFile $file): string
    {
        $storedPath = $file->store("products/{$product->id}", 'public');

        return '/storage/'.$storedPath;
    }

    private function syncLegacyImageColumn(Product $product): void
    {
        $primaryPath = $product->images()
            ->where('is_primary', true)
            ->value('path')
            ?? $product->images()->orderBy('sort_order')->value('path');

        $product->forceFill(['image' => $primaryPath])->save();
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relative = str_starts_with($path, '/storage/')
            ? substr($path, strlen('/storage/'))
            : (str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : null);

        if ($relative === null || str_contains($relative, '://')) {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}

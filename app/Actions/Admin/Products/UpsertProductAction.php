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
     *     color_ids?: list<int>,
     *     new_colors?: list<array{name: string, hex?: string|null}>,
     *     available_stock: int,
     *     primary_image?: UploadedFile|null,
     *     secondary_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }>  $variants
     * @param  list<int>  $removeVariantIds
     */
    public function execute(
        array $attributes,
        ?Product $product = null,
        ?UploadedFile $technicalSheet = null,
        bool $removeTechnicalSheet = false,
        array $variants = [],
        array $removeVariantIds = [],
    ): Product {
        return DB::transaction(function () use (
            $attributes,
            $product,
            $technicalSheet,
            $removeTechnicalSheet,
            $variants,
            $removeVariantIds,
        ) {
            unset($attributes['image'], $attributes['technical_sheet']);

            if ($product === null) {
                $product = Product::query()->create($attributes);
            } else {
                $product->update($attributes);
            }

            $this->removeVariants($product, $removeVariantIds);
            $this->syncVariants($product, $variants);
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
     * @param  list<int>  $variantIds
     */
    private function removeVariants(Product $product, array $variantIds): void
    {
        if ($variantIds === []) {
            return;
        }

        $variants = $product->variants()
            ->with(['images', 'orderItems'])
            ->whereIn('id', $variantIds)
            ->get();

        foreach ($variants as $variant) {
            if ($variant->orderItems()->exists()) {
                throw ValidationException::withMessages([
                    'remove_variant_ids' => "No se puede eliminar el color «{$variant->colorLabel()}» porque tiene pedidos.",
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

    /**
     * @param  list<array{
     *     id?: int|null,
     *     color_ids?: list<int>,
     *     new_colors?: list<array{name: string, hex?: string|null}>,
     *     available_stock: int,
     *     primary_image?: UploadedFile|null,
     *     secondary_images?: list<UploadedFile>,
     *     remove_image_ids?: list<int>
     * }>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $payload) {
            $colorIds = $this->resolveColorIds(
                $payload['color_ids'] ?? [],
                $payload['new_colors'] ?? [],
            );

            if ($colorIds === []) {
                throw ValidationException::withMessages([
                    'variants' => 'Cada combinación debe tener al menos un color.',
                ]);
            }

            $colors = Color::query()->whereIn('id', $colorIds)->get()->sortBy(
                fn (Color $color) => array_search($color->id, $colorIds, true),
            )->values();

            $colorNames = $colors->pluck('name')->all();
            $label = $colors->pluck('name')->implode(' / ');

            $variantId = isset($payload['id']) ? (int) $payload['id'] : null;
            $variant = null;

            if ($variantId) {
                $variant = $product->variants()->with('inventory')->where('id', $variantId)->first();
            }

            $sku = ($this->generateSku)->forVariant($product, $colorNames, $variant);

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

            $availableStock = max(0, (int) ($payload['available_stock'] ?? 0));
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

            $this->removeImages($variant, $payload['remove_image_ids'] ?? []);

            if (($payload['primary_image'] ?? null) instanceof UploadedFile) {
                $this->storePrimaryImage($product, $variant, $payload['primary_image']);
            }

            foreach ($payload['secondary_images'] ?? [] as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $sortOrder = (int) $variant->images()->max('sort_order');

                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'path' => $this->storeUploadedFile($product, $file),
                    'sort_order' => max($sortOrder + 1, 1),
                    'is_primary' => false,
                ]);
            }
        }
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
                ? 'Stock inicial al crear color '.$variant->colorLabel()
                : 'Ajuste desde ficha de producto ('.$variant->colorLabel().')',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * @param  list<int>  $imageIds
     */
    private function removeImages(ProductVariant $variant, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $variant->images()
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            $this->deleteStoredFile($image->path);
            $image->delete();
        }
    }

    private function storePrimaryImage(Product $product, ProductVariant $variant, UploadedFile $file): void
    {
        $previousPrimary = $variant->images()
            ->where('is_primary', true)
            ->get();

        foreach ($previousPrimary as $image) {
            $this->deleteStoredFile($image->path);
            $image->delete();
        }

        $path = $this->storeUploadedFile($product, $file);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'path' => $path,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $variant->images()
            ->where('path', '!=', $path)
            ->update(['is_primary' => false]);
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

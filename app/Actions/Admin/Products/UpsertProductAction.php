<?php

namespace App\Actions\Admin\Products;

use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Models\Products\Inventory;
use App\Models\Products\InventoryMovement;
use App\Models\Products\Product;
use App\Models\Products\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertProductAction
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<UploadedFile>  $secondaryImages
     * @param  list<int>  $removeImageIds
     */
    public function execute(
        array $attributes,
        int $availableStock,
        ?Product $product = null,
        ?UploadedFile $primaryImage = null,
        array $secondaryImages = [],
        array $removeImageIds = [],
    ): Product {
        return DB::transaction(function () use (
            $attributes,
            $availableStock,
            $product,
            $primaryImage,
            $secondaryImages,
            $removeImageIds,
        ) {
            unset($attributes['image']);

            $previousAvailable = $product !== null
                ? (int) ($product->inventory?->available_stock ?? 0)
                : 0;

            if ($product === null) {
                $product = Product::query()->create($attributes);
            } else {
                $product->update($attributes);
            }

            $reservedStock = (int) ($product->inventory?->reserved_stock ?? 0);

            Inventory::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'available_stock' => $availableStock,
                    'reserved_stock' => $reservedStock,
                    'total_stock' => $availableStock + $reservedStock,
                ],
            );

            $this->recordStockAdjustment($product, $previousAvailable, $availableStock);

            $this->removeImages($product, $removeImageIds);

            if ($primaryImage !== null) {
                $this->storePrimaryImage($product, $primaryImage);
            }

            if ($secondaryImages !== []) {
                $sortOrder = (int) $product->images()->max('sort_order');

                foreach ($secondaryImages as $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $sortOrder++;

                    ProductImage::query()->create([
                        'product_id' => $product->id,
                        'path' => $this->storeUploadedFile($product, $file),
                        'sort_order' => max($sortOrder, 1),
                        'is_primary' => false,
                    ]);
                }
            }

            $this->syncLegacyImageColumn($product);

            return $product->fresh(['inventory', 'category', 'vehicleModel', 'images']);
        });
    }

    private function recordStockAdjustment(Product $product, int $previousAvailable, int $newAvailable): void
    {
        $delta = $newAvailable - $previousAvailable;

        if ($delta === 0) {
            return;
        }

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'type' => $delta > 0 ? InventoryMovementType::Entry : InventoryMovementType::Exit,
            'reason' => InventoryMovementReason::Adjustment,
            'quantity' => abs($delta),
            'notes' => $previousAvailable === 0 && $delta > 0 && $product->wasRecentlyCreated
                ? 'Stock inicial al crear el producto'
                : 'Ajuste desde ficha de producto',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * @param  list<int>  $imageIds
     */
    private function removeImages(Product $product, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $product->images()
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            $this->deleteStoredFile($image->path);
            $image->delete();
        }
    }

    private function storePrimaryImage(Product $product, UploadedFile $file): void
    {
        $previousPrimary = $product->images()
            ->where('is_primary', true)
            ->get();

        foreach ($previousPrimary as $image) {
            $this->deleteStoredFile($image->path);
            $image->delete();
        }

        $path = $this->storeUploadedFile($product, $file);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $path,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $product->images()
            ->where('path', '!=', $path)
            ->update(['is_primary' => false]);
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

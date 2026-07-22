<?php

namespace App\Actions\Admin\Brands;

use App\Models\Products\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertBrandAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        array $attributes,
        ?Brand $brand = null,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): Brand {
        return DB::transaction(function () use ($attributes, $brand, $image, $removeImage) {
            if ($brand === null) {
                $brand = Brand::query()->create($attributes);
            } else {
                $brand->update($attributes);
            }

            if ($removeImage && $brand->image) {
                $this->deleteStoredFile($brand->image);
                $brand->forceFill(['image' => null])->save();
            }

            if ($image !== null) {
                if ($brand->image) {
                    $this->deleteStoredFile($brand->image);
                }

                $storedPath = $image->store("brands/{$brand->id}", 'public');
                $brand->forceFill(['image' => '/storage/'.$storedPath])->save();
            }

            return $brand->fresh();
        });
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '' || str_contains($path, '://')) {
            return;
        }

        $relative = str_starts_with($path, '/storage/')
            ? substr($path, strlen('/storage/'))
            : (str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : null);

        if ($relative === null) {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}

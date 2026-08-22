<?php

namespace App\Actions\Admin\Categories;

use App\Models\Products\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertCategoryAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        array $attributes,
        ?Category $category = null,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): Category {
        return DB::transaction(function () use ($attributes, $category, $image, $removeImage) {
            if ($category === null) {
                $attributes['sort_order'] = (int) (Category::query()->max('sort_order') ?? 0) + 1;
                $category = Category::query()->create($attributes);
            } else {
                $category->update($attributes);
            }

            if ($removeImage && $category->image) {
                $this->deleteStoredFile($category->image);
                $category->forceFill(['image' => null])->save();
            }

            if ($image !== null) {
                if ($category->image) {
                    $this->deleteStoredFile($category->image);
                }

                $storedPath = $image->store("categories/{$category->id}", 'public');
                $category->forceFill(['image' => '/storage/'.$storedPath])->save();
            }

            return $category->fresh();
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

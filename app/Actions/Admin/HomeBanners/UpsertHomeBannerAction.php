<?php

namespace App\Actions\Admin\HomeBanners;

use App\Models\Content\HomeBanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertHomeBannerAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        array $attributes,
        ?HomeBanner $homeBanner = null,
        ?UploadedFile $image = null,
    ): HomeBanner {
        return DB::transaction(function () use ($attributes, $homeBanner, $image) {
            if ($homeBanner === null) {
                $attributes['sort_order'] = (int) (HomeBanner::query()->max('sort_order') ?? 0) + 1;
                $homeBanner = HomeBanner::query()->create($attributes);
            } else {
                $homeBanner->update($attributes);
            }

            if ($image !== null) {
                if ($homeBanner->image) {
                    $this->deleteStoredFile($homeBanner->image);
                }

                $storedPath = $image->store("home-banners/{$homeBanner->id}", 'public');
                $homeBanner->forceFill(['image' => '/storage/'.$storedPath])->save();
            }

            return $homeBanner->fresh();
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

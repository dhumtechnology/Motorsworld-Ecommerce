<?php

namespace App\Actions\Admin\PageBanners;

use App\Models\Content\PageBanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdatePageBannerAction
{
    public function execute(
        PageBanner $pageBanner,
        ?UploadedFile $image = null,
        bool $restoreDefault = false,
    ): PageBanner {
        return DB::transaction(function () use ($pageBanner, $image, $restoreDefault) {
            if ($restoreDefault) {
                $this->deleteStoredFile($pageBanner->image);
                $pageBanner->forceFill(['image' => null])->save();

                return $pageBanner->fresh();
            }

            if ($image !== null) {
                $this->deleteStoredFile($pageBanner->image);

                $storedPath = $image->store('page-banners/'.$pageBanner->page_key->value, 'public');
                $pageBanner->forceFill(['image' => '/storage/'.$storedPath])->save();
            }

            return $pageBanner->fresh();
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

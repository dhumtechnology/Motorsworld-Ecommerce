<?php

namespace App\Actions\Admin\HomeBanners;

use App\Models\Content\HomeBanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteHomeBannersAction
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: int}
     */
    public function execute(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => is_int($id) && $id > 0)));

        if ($ids === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($ids) {
            $banners = HomeBanner::query()->whereIn('id', $ids)->get();
            $deleted = 0;

            foreach ($banners as $banner) {
                $this->deleteStoredFile($banner->image);
                $banner->delete();
                $deleted++;
            }

            return ['deleted' => $deleted];
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

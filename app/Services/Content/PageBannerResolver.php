<?php

namespace App\Services\Content;

use App\Enums\Content\PageBannerKey;
use App\Models\Content\PageBanner;

class PageBannerResolver
{
    public const FALLBACK_PUBLIC_PATH = 'images/services/banner-servicios.png';

    public function imageUrl(PageBannerKey $key): string
    {
        $banner = PageBanner::query()->where('page_key', $key)->first();

        if ($banner?->hasCustomImage()) {
            return $banner->image;
        }

        return $this->defaultImageUrl($key);
    }

    public function defaultImageUrl(PageBannerKey $key): string
    {
        $relative = $key->defaultPublicPath();

        if (is_file(public_path($relative))) {
            return asset($relative);
        }

        return asset(self::FALLBACK_PUBLIC_PATH);
    }
}

<?php

namespace App\Models\Content;

use App\Enums\Content\PageBannerKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'page_key',
    'image',
])]
class PageBanner extends Model
{
    protected function casts(): array
    {
        return [
            'page_key' => PageBannerKey::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'page_key';
    }

    public function getRouteKey(): string
    {
        return $this->page_key->value;
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();

        if ($field === 'page_key') {
            $key = PageBannerKey::tryFrom((string) $value);

            if ($key === null) {
                return null;
            }

            return static::query()->where('page_key', $key)->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function hasCustomImage(): bool
    {
        return filled($this->image);
    }

    public static function ensureRows(): void
    {
        foreach (PageBannerKey::cases() as $key) {
            static::query()->firstOrCreate(
                ['page_key' => $key->value],
                ['image' => null],
            );
        }
    }
}

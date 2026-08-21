<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable([
    'title',
    'image',
    'is_active',
    'sort_order',
    'starts_at',
    'ends_at',
])]
class HomeBanner extends Model
{
    /**
     * Imágenes estáticas usadas cuando no hay banners vigentes en el admin.
     *
     * @return list<string>
     */
    public static function defaultSlideUrls(): array
    {
        return [
            asset('images/home/banner-hero.png'),
            asset('images/home/portadas/1 HOME - bienvenidos a mw 2.jpg'),
        ];
    }

    public function isVisibleOnHome(?Carbon $at = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $at ??= now();

        if ($this->starts_at->gt($at)) {
            return false;
        }

        if ($this->ends_at === null) {
            return true;
        }

        return $this->ends_at->gte($at);
    }

    /**
     * @return 'active'|'scheduled'|'expired'|'inactive'
     */
    public function lifecycleStatus(?Carbon $at = null): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $at ??= now();

        if ($this->starts_at->gt($at)) {
            return 'scheduled';
        }

        if ($this->ends_at !== null && $this->ends_at->lt($at)) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * @return array{label: string, class: string}
     */
    public function lifecycleMeta(?Carbon $at = null): array
    {
        return match ($this->lifecycleStatus($at)) {
            'active' => [
                'label' => 'Vigente',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'scheduled' => [
                'label' => 'Programado',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200',
            ],
            'expired' => [
                'label' => 'Expirado',
                'class' => 'bg-secondary text-muted border-border',
            ],
            'inactive' => [
                'label' => 'Inactivo',
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
        };
    }

    /**
     * @param  Builder<HomeBanner>  $query
     */
    public function scopeVisibleOnHome(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();

        $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where(function (Builder $query) use ($at) {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $at);
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models\Coupons;

use App\Enums\Coupons\CouponType;
use App\Enums\Payments\PaymentMethod;
use App\Models\Auth\User;
use App\Models\Locations\Department;
use App\Models\Locations\District;
use App\Models\Locations\Province;
use App\Models\Orders\Order;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Support\Currency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'code',
    'name',
    'reason',
    'type',
    'value',
    'currency',
    'starts_at',
    'ends_at',
    'min_products',
    'min_subtotal',
    'min_subtotal_currency',
    'payment_methods',
    'is_active',
])]
class Coupon extends Model
{
    /**
     * @return BelongsToMany<User, $this>
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')->with('customerProfile');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    /**
     * @return BelongsToMany<Brand, $this>
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'coupon_brand');
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'coupon_department');
    }

    /**
     * @return BelongsToMany<Province, $this>
     */
    public function provinces(): BelongsToMany
    {
        return $this->belongsToMany(Province::class, 'coupon_province');
    }

    /**
     * @return BelongsToMany<District, $this>
     */
    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'coupon_district');
    }

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isPublic(): bool
    {
        if ($this->relationLoaded('customers')) {
            return $this->customers->isEmpty();
        }

        return ! $this->customers()->exists();
    }

    public function hasCatalogRestriction(): bool
    {
        if ($this->relationLoaded('products') && $this->relationLoaded('categories') && $this->relationLoaded('brands')) {
            return $this->products->isNotEmpty()
                || $this->categories->isNotEmpty()
                || $this->brands->isNotEmpty();
        }

        return $this->products()->exists()
            || $this->categories()->exists()
            || $this->brands()->exists();
    }

    /**
     * @return list<string>
     */
    public function allowedPaymentMethods(): array
    {
        $methods = $this->payment_methods ?? [];

        return array_values(array_filter(array_map(
            fn ($method) => is_string($method) ? strtolower(trim($method)) : '',
            is_array($methods) ? $methods : [],
        )));
    }

    public function hasZoneRestriction(): bool
    {
        if ($this->relationLoaded('departments') && $this->relationLoaded('provinces') && $this->relationLoaded('districts')) {
            return $this->departments->isNotEmpty()
                || $this->provinces->isNotEmpty()
                || $this->districts->isNotEmpty();
        }

        return $this->departments()->exists()
            || $this->provinces()->exists()
            || $this->districts()->exists();
    }

    /**
     * @return list<string>
     */
    public function zoneLabels(): array
    {
        $labels = [];

        if ($this->relationLoaded('departments')) {
            $labels = array_merge($labels, $this->departments->pluck('name')->all());
        }

        if ($this->relationLoaded('provinces')) {
            $labels = array_merge($labels, $this->provinces->map(
                fn (Province $province) => $province->name.($province->department ? ' / '.$province->department->name : ''),
            )->all());
        }

        if ($this->relationLoaded('districts')) {
            $labels = array_merge($labels, $this->districts->map(
                fn (District $district) => $district->name.($district->province ? ' / '.$district->province->name : ''),
            )->all());
        }

        return array_values(array_filter(array_map('strval', $labels)));
    }

    public function allowsPaymentMethod(?PaymentMethod $method): bool
    {
        $allowed = $this->allowedPaymentMethods();

        if ($allowed === []) {
            return true;
        }

        if ($method === null) {
            return false;
        }

        return in_array($method->value, $allowed, true);
    }

    public function allowsDistrict(?District $district): bool
    {
        if (! $this->hasZoneRestriction()) {
            return true;
        }

        if ($district === null) {
            return false;
        }

        $districtIds = $this->relationLoaded('districts')
            ? $this->districts->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $this->districts()->pluck('districts.id')->map(fn ($id) => (int) $id)->all();

        if (in_array((int) $district->id, $districtIds, true)) {
            return true;
        }

        $provinceIds = $this->relationLoaded('provinces')
            ? $this->provinces->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $this->provinces()->pluck('provinces.id')->map(fn ($id) => (int) $id)->all();

        if (in_array((int) $district->province_id, $provinceIds, true)) {
            return true;
        }

        $departmentIds = $this->relationLoaded('departments')
            ? $this->departments->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $this->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->all();

        return in_array((int) $district->department_id, $departmentIds, true);
    }

    public function wasUsedBy(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if ($this->relationLoaded('usages')) {
            return $this->usages->contains('user_id', $userId);
        }

        return $this->usages()->where('user_id', $userId)->exists();
    }

    public function isActiveAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->is_active
            && $this->starts_at->lte($at)
            && $this->ends_at->gte($at);
    }

    /**
     * @return 'active'|'scheduled'|'expired'|'inactive'
     */
    public function lifecycleStatus(?Carbon $at = null): string
    {
        $at ??= now();

        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at->gt($at)) {
            return 'scheduled';
        }

        if ($this->ends_at->lt($at)) {
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
            default => [
                'label' => 'Inactivo',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
        };
    }

    public function hasTitle(): bool
    {
        return trim((string) ($this->name ?? '')) !== '';
    }

    public function displayTitle(): string
    {
        return $this->hasTitle() ? trim((string) $this->name) : $this->code;
    }

    public function discountLabel(): string
    {
        if ($this->type === CouponType::Percent) {
            return rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.').'%';
        }

        return Currency::format((float) $this->value, $this->currency);
    }

    public function normalizeCity(?string $city): string
    {
        $city = trim((string) $city);

        if ($city === '') {
            return '';
        }

        return mb_strtolower($city);
    }

    /**
     * @param  Builder<Coupon>  $query
     */
    public function scopeActiveAt(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();

        $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'min_products' => 'integer',
            'min_subtotal' => 'decimal:2',
            'payment_methods' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}

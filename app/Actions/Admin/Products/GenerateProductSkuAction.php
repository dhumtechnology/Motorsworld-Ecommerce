<?php

namespace App\Actions\Admin\Products;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Support\Str;

class GenerateProductSkuAction
{
    public function __invoke(?string $preferred = null): string
    {
        $preferred = trim((string) $preferred);

        if ($preferred !== '' && ! Product::query()->where('sku', $preferred)->exists()) {
            return $preferred;
        }

        do {
            $sku = 'MW-'.strtoupper(Str::random(8));
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * @param  list<string>  $colorNames
     */
    public function forVariant(Product $product, array $colorNames, ?ProductVariant $ignore = null): string
    {
        $suffix = collect($colorNames)
            ->map(fn (string $name) => Str::upper(Str::slug($name, '')))
            ->filter()
            ->unique()
            ->values()
            ->implode('-');

        if ($suffix === '') {
            $suffix = 'STD';
        }

        $base = $product->sku.'-'.$suffix;

        return $this->ensureUniqueVariantSku($base, $ignore);
    }

    public function resolveVariantSku(string $preferred, ?ProductVariant $ignore = null): string
    {
        return $this->ensureUniqueVariantSku(trim($preferred), $ignore);
    }

    private function ensureUniqueVariantSku(string $sku, ?ProductVariant $ignore = null): string
    {
        if ($sku === '') {
            return $sku;
        }

        $candidate = $sku;
        $i = 2;

        while (
            ProductVariant::query()
                ->where('sku', $candidate)
                ->when($ignore !== null, fn ($query) => $query->where('id', '!=', $ignore->id))
                ->exists()
        ) {
            $candidate = $sku.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}

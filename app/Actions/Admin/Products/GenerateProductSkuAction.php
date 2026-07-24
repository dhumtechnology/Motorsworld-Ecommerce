<?php

namespace App\Actions\Admin\Products;

use App\Models\Products\Product;
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
}

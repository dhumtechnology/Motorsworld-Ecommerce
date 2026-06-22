<?php

namespace Database\Seeders;

use App\Enums\Products\ProductStatus;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Inventory;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CatalogSeeder extends Seeder
{
    /**
     * Seed catalog data (categories, brands, models, products, inventory).
     * Uses updateOrCreate — safe to run on every container start.
     */
    public function run(): void
    {
        $categories = $this->seedCategories();
        $brands = $this->seedBrands();
        $models = $this->seedVehicleModels($brands);
        $this->seedProducts($categories, $models);
    }

    /**
     * @return Collection<string, Category>
     */
    private function seedCategories(): Collection
    {
        $definitions = [
            'MOTOS' => 'Motocicletas nuevas y usadas',
            'Accesorios' => 'Cascos, guantes, chaquetas y equipamiento',
            'Repuestos' => 'Filtros, pastillas de freno, transmisión y consumibles',
            'Baterías' => 'Baterías para motocicletas',
            'Lubricantes' => 'Aceites de motor y lubricantes de cadena',
            'Neumáticos' => 'Neumáticos delanteros y traseros',
        ];

        return collect($definitions)->map(
            fn (string $description, string $name) => Category::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            ),
        );
    }

    /**
     * @return Collection<string, Brand>
     */
    private function seedBrands(): Collection
    {
        $names = [
            'Yamaha',
            'Honda',
            'Kawasaki',
            'Suzuki',
            'BMW Motorrad',
            'KTM',
            'Ducati',
            'Bajaj',
            'Universal',
        ];

        return collect($names)->mapWithKeys(
            fn (string $name) => [
                $name => Brand::query()->updateOrCreate(['name' => $name], ['image' => null]),
            ],
        );
    }

    /**
     * @param  Collection<string, Brand>  $brands
     * @return Collection<string, VehicleModel>
     */
    private function seedVehicleModels(Collection $brands): Collection
    {
        $definitions = [
            'Yamaha' => ['YZF-R3', 'MT-07', 'NMAX 155', 'XTZ 250'],
            'Honda' => ['CBR500R', 'CB650R', 'XR190L', 'PCX 160'],
            'Kawasaki' => ['Ninja 400', 'Z650', 'Versys-X 300'],
            'Suzuki' => ['GSX-R750', 'V-Strom 650', 'GN125'],
            'BMW Motorrad' => ['G 310 R', 'F 850 GS'],
            'KTM' => ['390 Duke', '790 Adventure'],
            'Ducati' => ['Monster', 'Scrambler Icon'],
            'Bajaj' => ['Pulsar NS200', 'Dominar 400'],
            'Universal' => ['Genérico'],
        ];

        $models = collect();

        foreach ($definitions as $brandName => $modelNames) {
            $brand = $brands->get($brandName);

            if ($brand === null) {
                continue;
            }

            foreach ($modelNames as $modelName) {
                $key = "{$brandName}::{$modelName}";

                $models->put(
                    $key,
                    VehicleModel::query()->updateOrCreate(
                        ['brand_id' => $brand->id, 'name' => $modelName],
                        [],
                    ),
                );
            }
        }

        return $models;
    }

    /**
     * @param  Collection<string, Category>  $categories
     * @param  Collection<string, VehicleModel>  $models
     */
    private function seedProducts(
        Collection $categories,
        Collection $models,
    ): void {
        $products = [
            // Motos
            ['sku' => 'MW-MOTO-001', 'category' => 'MOTOS', 'brand' => 'Yamaha', 'model' => 'YZF-R3', 'price' => 18999.00, 'stock' => 3, 'status' => ProductStatus::Active],
            ['sku' => 'MW-MOTO-002', 'category' => 'MOTOS', 'brand' => 'Honda', 'model' => 'CB650R', 'price' => 24999.00, 'stock' => 2, 'status' => ProductStatus::Active],
            ['sku' => 'MW-MOTO-003', 'category' => 'MOTOS', 'brand' => 'Kawasaki', 'model' => 'Ninja 400', 'price' => 21999.00, 'stock' => 0, 'status' => ProductStatus::Active],
            ['sku' => 'MW-MOTO-004', 'category' => 'MOTOS', 'brand' => 'Bajaj', 'model' => 'Pulsar NS200', 'price' => 12999.00, 'stock' => 5, 'status' => ProductStatus::Active],
            ['sku' => 'MW-MOTO-005', 'category' => 'MOTOS', 'brand' => 'KTM', 'model' => '390 Duke', 'price' => 19999.00, 'stock' => 1, 'status' => ProductStatus::Active],

            // Accesorios
            ['sku' => 'MW-ACC-001', 'category' => 'Accesorios', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 459.90, 'stock' => 25, 'status' => ProductStatus::Active],
            ['sku' => 'MW-ACC-002', 'category' => 'Accesorios', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 89.90, 'stock' => 40, 'status' => ProductStatus::Active],
            ['sku' => 'MW-ACC-003', 'category' => 'Accesorios', 'brand' => 'Yamaha', 'model' => 'YZF-R3', 'price' => 320.00, 'stock' => 12, 'status' => ProductStatus::Active],
            ['sku' => 'MW-ACC-004', 'category' => 'Accesorios', 'brand' => 'Honda', 'model' => 'PCX 160', 'price' => 275.50, 'stock' => 0, 'status' => ProductStatus::Active],

            // Repuestos
            ['sku' => 'MW-REP-001', 'category' => 'Repuestos', 'brand' => 'Yamaha', 'model' => 'MT-07', 'price' => 45.00, 'stock' => 30, 'status' => ProductStatus::Active],
            ['sku' => 'MW-REP-002', 'category' => 'Repuestos', 'brand' => 'Honda', 'model' => 'XR190L', 'price' => 38.50, 'stock' => 18, 'status' => ProductStatus::Active],
            ['sku' => 'MW-REP-003', 'category' => 'Repuestos', 'brand' => 'Suzuki', 'model' => 'GN125', 'price' => 22.00, 'stock' => 50, 'status' => ProductStatus::Active],
            ['sku' => 'MW-REP-004', 'category' => 'Repuestos', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 15.00, 'stock' => 0, 'status' => ProductStatus::Active],

            // Baterías
            ['sku' => 'MW-BAT-001', 'category' => 'Baterías', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 120.00, 'stock' => 15, 'status' => ProductStatus::Active],
            ['sku' => 'MW-BAT-002', 'category' => 'Baterías', 'brand' => 'Yamaha', 'model' => 'NMAX 155', 'price' => 145.00, 'stock' => 8, 'status' => ProductStatus::Active],
            ['sku' => 'MW-BAT-003', 'category' => 'Baterías', 'brand' => 'Honda', 'model' => 'CBR500R', 'price' => 155.00, 'stock' => 6, 'status' => ProductStatus::Active],

            // Lubricantes
            ['sku' => 'MW-LUB-001', 'category' => 'Lubricantes', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 55.00, 'stock' => 60, 'status' => ProductStatus::Active],
            ['sku' => 'MW-LUB-002', 'category' => 'Lubricantes', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 32.00, 'stock' => 45, 'status' => ProductStatus::Active],

            // Neumáticos
            ['sku' => 'MW-NEU-001', 'category' => 'Neumáticos', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 280.00, 'stock' => 10, 'status' => ProductStatus::Active],
            ['sku' => 'MW-NEU-002', 'category' => 'Neumáticos', 'brand' => 'BMW Motorrad', 'model' => 'F 850 GS', 'price' => 410.00, 'stock' => 4, 'status' => ProductStatus::Active],

            // Producto pendiente (no aparece en catálogo público)
            ['sku' => 'MW-ACC-999', 'category' => 'Accesorios', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 99.00, 'stock' => 5, 'status' => ProductStatus::Pending],
        ];

        foreach ($products as $data) {
            $category = $categories->get($data['category']);
            $modelKey = "{$data['brand']}::{$data['model']}";
            $vehicleModel = $models->get($modelKey);

            $product = Product::query()->updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'price_amount' => $data['price'],
                    'currency' => 'PEN',
                    'status' => $data['status'],
                    'image' => null,
                    'category_id' => $category->id,
                    'model_id' => $vehicleModel?->id,
                ],
            );

            $available = (int) $data['stock'];
            $reserved = $available > 0 ? min(1, $available) : 0;

            Inventory::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'total_stock' => $available + $reserved,
                    'available_stock' => $available,
                    'reserved_stock' => $reserved,
                ],
            );
        }
    }
}

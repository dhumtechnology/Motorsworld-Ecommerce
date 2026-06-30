<?php

namespace Database\Seeders;

use App\Enums\Products\ProductStatus;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Inventory;
use App\Models\Products\Product;
use App\Models\Products\ProductImage;
use App\Models\Products\ProductOffer;
use App\Models\Products\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CatalogSeeder extends Seeder
{
    private const GALLERY_MOTO = [
        'https://images.ctfassets.net/8zlbnewncp6f/2fH3mKeHaSrfHQlEsm2xxt/c9c0202dc9333bd05422552a3a14e34b/Galeria2_Galgo_Chile.jpg?w=600&fm=webp&q=90',
        'https://images.ctfassets.net/8zlbnewncp6f/2fH3mKeHaSrfHQlEsm2xxt/c9c0202dc9333bd05422552a3a14e34b/Galeria2_Galgo_Chile.jpg?w=900&fm=webp&q=85',
        'https://images.ctfassets.net/8zlbnewncp6f/2fH3mKeHaSrfHQlEsm2xxt/c9c0202dc9333bd05422552a3a14e34b/Galeria2_Galgo_Chile.jpg?w=1200&fm=webp&q=80',
    ];

    private const GALLERY_ACCESSORY = [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTtOqavomJGW5tPA-FEUHJy-57xrYacL0x5RqPBFsifow&s=10',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8p_qQfIUQUlm2qAOovGykSvsXTaKsEjzMJQ&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ_533bKzwPa7HlNapio0XiMTbztLYxhewZ1Q&s',
    ];

    private const GALLERY_SPARE = [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRODvf0CYLoOonNG8SA-LEqIY2HLPy9-Jpk2opBwAXXDQ&s=10',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS2NwAQzchCoeuJHdyeSlaH82Q5WZtGtXiJHQ&s',
    ];

    private const GALLERY_BATTERY = [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSo1Apuh8MtRO8dQi07ibQQryI7bM7P1bN7xg&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrjAmxb39fbWahEuWIzOOJXyDVl7zO9323Ug&s',
    ];

    private const GALLERY_LUBRICANT = [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRjAAuJMs0Qgkdfsd86Lq-TPQEhx3BYINCcoCvlQmnt3Q&s=10',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRyNrTtAE5c5A0PLSX5Iim6adv4kfPqhWSTqw&s',
    ];

    private const GALLERY_TIRE = [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-PJZtzjBBWGxTe1K6DLtEn06h8xfYn8x1FFivSn4HAw&s=10',
        'https://casalopez.com.co/wp-content/uploads/2025/04/1806-LLANTA_PARA_MOTO_MICHELIN_90-80-17_PILOT_STREET_2-5.jpg',
        'https://mantallanta.com/cdn/shop/products/4512332146455_1.jpg?v=1674888224&width=800',
    ];

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
        $this->seedProductOffers();
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
            ['sku' => 'MW-MOTO-001', 'name' => 'Yamaha YZF-R3 2024', 'description' => 'Yamaha YZF-R3 2024. Deportiva ligera ideal para ciudad y pista, motor bicilíndrico 321 cc.', 'category' => 'MOTOS', 'brand' => 'Yamaha', 'model' => 'YZF-R3', 'price' => 18999.00, 'stock' => 3, 'status' => ProductStatus::Active, 'images' => 'moto'],
            ['sku' => 'MW-MOTO-002', 'name' => 'Honda CB650R', 'description' => 'Honda CB650R naked streetfighter. Cuatro cilindros, 649 cc, acabado premium y manejo ágil.', 'category' => 'MOTOS', 'brand' => 'Honda', 'model' => 'CB650R', 'price' => 24999.00, 'stock' => 2, 'status' => ProductStatus::Active, 'images' => 'moto'],
            ['sku' => 'MW-MOTO-003', 'name' => 'Kawasaki Ninja 400', 'description' => 'Kawasaki Ninja 400. Carenado deportivo, ideal para iniciarse en motos de alta cilindrada.', 'category' => 'MOTOS', 'brand' => 'Kawasaki', 'model' => 'Ninja 400', 'price' => 21999.00, 'stock' => 0, 'status' => ProductStatus::Active, 'images' => 'moto'],
            ['sku' => 'MW-MOTO-004', 'name' => 'Bajaj Pulsar NS200', 'description' => 'Bajaj Pulsar NS200. Urbana versátil con excelente relación potencia-consumo.', 'category' => 'MOTOS', 'brand' => 'Bajaj', 'model' => 'Pulsar NS200', 'price' => 12999.00, 'stock' => 5, 'status' => ProductStatus::Active, 'images' => 'moto'],
            ['sku' => 'MW-MOTO-005', 'name' => 'KTM 390 Duke', 'description' => 'KTM 390 Duke. Naked ágil con tecnología ABS y pantalla TFT a color.', 'category' => 'MOTOS', 'brand' => 'KTM', 'model' => '390 Duke', 'price' => 19999.00, 'stock' => 1, 'status' => ProductStatus::Active, 'images' => 'moto'],

            // Accesorios
            ['sku' => 'MW-ACC-001', 'name' => 'Casco integral DOT/ECE', 'description' => 'Casco integral certificado DOT/ECE. Visera anti-rayas y ventilación superior.', 'category' => 'Accesorios', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 459.90, 'stock' => 25, 'status' => ProductStatus::Active, 'images' => 'accessory'],
            ['sku' => 'MW-ACC-002', 'name' => 'Guantes de verano', 'description' => 'Guantes de verano con protección en nudillos y palma reforzada.', 'category' => 'Accesorios', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 89.90, 'stock' => 40, 'status' => ProductStatus::Active, 'images' => 'accessory'],
            ['sku' => 'MW-ACC-003', 'name' => 'Chaqueta textil Yamaha YZF-R3', 'description' => 'Chaquetas textil compatible con Yamaha YZF-R3. Incluye protecciones CE en hombros y codos.', 'category' => 'Accesorios', 'brand' => 'Yamaha', 'model' => 'YZF-R3', 'price' => 320.00, 'stock'  => 12, 'status' => ProductStatus::Active, 'images' => 'accessory'],
            ['sku' => 'MW-ACC-004', 'name' => 'Baúl trasero 35 L PCX 160', 'description' => 'Baúl trasero 35 L para Honda PCX 160. Incluye soporte y kit de montaje.', 'category' => 'Accesorios', 'brand' => 'Honda', 'model' => 'PCX 160', 'price' => 275.50, 'stock' => 0, 'status' => ProductStatus::Active, 'images' => 'accessory'],

            // Repuestos
            ['sku' => 'MW-REP-001', 'name' => 'Filtro de aceite Yamaha MT-07', 'description' => 'Filtro de aceite original compatible con Yamaha MT-07.', 'category' => 'Repuestos', 'brand' => 'Yamaha', 'model' => 'MT-07', 'price' => 45.00, 'stock' => 30, 'status' => ProductStatus::Active, 'images' => 'spare'],
            ['sku' => 'MW-REP-002', 'name' => 'Pastillas de freno Honda XR190L', 'description' => 'Kit de pastillas de freno delanteras para Honda XR190L.', 'category' => 'Repuestos', 'brand' => 'Honda', 'model' => 'XR190L', 'price' => 38.50, 'stock' => 18, 'status' => ProductStatus::Active, 'images' => 'spare'],
            ['sku' => 'MW-REP-003', 'name' => 'Kit cadena y piñones GN125', 'description' => 'Kit de cadena y piñones 428 para Suzuki GN125.', 'category' => 'Repuestos', 'brand' => 'Suzuki', 'model' => 'GN125', 'price' => 22.00, 'stock' => 50, 'status' => ProductStatus::Active, 'images' => 'spare'],
            ['sku' => 'MW-REP-004', 'name' => 'Bujía NGK estándar', 'description' => 'Bujía NGK estándar compatible con motos de baja cilindrada.', 'category' => 'Repuestos', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 15.00, 'stock' => 0, 'status' => ProductStatus::Active, 'images' => 'spare'],

            // Baterías
            ['sku' => 'MW-BAT-001', 'name' => 'Batería sellada 12V 5Ah', 'description' => 'Batería sellada 12 V 5 Ah. Uso universal para scooters y motos ligeras.', 'category' => 'Baterías', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 120.00, 'stock' => 15, 'status' => ProductStatus::Active, 'images' => 'battery'],
            ['sku' => 'MW-BAT-002', 'name' => 'Batería gel Yamaha NMAX 155', 'description' => 'Batería de gel compatible con Yamaha NMAX 155.', 'category' => 'Baterías', 'brand' => 'Yamaha', 'model' => 'NMAX 155', 'price' => 145.00, 'stock' => 8, 'status' => ProductStatus::Active, 'images' => 'battery'],
            ['sku' => 'MW-BAT-003', 'name' => 'Batería Honda CBR500R', 'description' => 'Batería de alto rendimiento para Honda CBR500R.', 'category' => 'Baterías', 'brand' => 'Honda', 'model' => 'CBR500R', 'price' => 155.00, 'stock' => 6, 'status' => ProductStatus::Active, 'images' => 'battery'],

            // Lubricantes
            ['sku' => 'MW-LUB-001', 'name' => 'Aceite sintético 10W-40 1L', 'description' => 'Aceite sintético 10W-40 1 L. Formulación para motores 4T de alto rendimiento.', 'category' => 'Lubricantes', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 55.00, 'stock' => 60, 'status' => ProductStatus::Active, 'images' => 'lubricant'],
            ['sku' => 'MW-LUB-002', 'name' => 'Lubricante de cadena 400ml', 'description' => 'Lubricante de cadena en aerosol 400 ml. Resistente al agua y polvo.', 'category' => 'Lubricantes', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 32.00, 'stock' => 45, 'status' => ProductStatus::Active, 'images' => 'lubricant'],

            // Neumáticos
            ['sku' => 'MW-NEU-001', 'name' => 'Neumático trasero 120/70-17', 'description' => 'Neumático trasero 120/70-17. Compuesto mixto para uso urbano y carretera.', 'category' => 'Neumáticos', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 280.00, 'stock' => 10, 'status' => ProductStatus::Active, 'images' => 'tire'],
            ['sku' => 'MW-NEU-002', 'name' => 'Neumático trail BMW F 850 GS', 'description' => 'Neumático trail 110/80-19 compatible con BMW F 850 GS.', 'category' => 'Neumáticos', 'brand' => 'BMW Motorrad', 'model' => 'F 850 GS', 'price' => 410.00, 'stock' => 4, 'status' => ProductStatus::Active, 'images' => 'tire'],

            // Producto pendiente (no aparece en catálogo público)
            ['sku' => 'MW-ACC-999', 'name' => 'Porta celular magnético', 'description' => 'Porta celular magnético para manillar. Próximamente disponible.', 'category' => 'Accesorios', 'brand' => 'Universal', 'model' => 'Genérico', 'price' => 99.00, 'stock' => 5, 'status' => ProductStatus::Pending, 'images' => 'accessory'],
        ];

        foreach ($products as $data) {
            $category = $categories->get($data['category']);
            $modelKey = "{$data['brand']}::{$data['model']}";
            $vehicleModel = $models->get($modelKey);

            $product = Product::query()->updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price_amount' => $data['price'],
                    'currency' => 'PEN',
                    'status' => $data['status'],
                    'image' => $this->resolveImages($data)[0] ?? null,
                    'category_id' => $category->id,
                    'model_id' => $vehicleModel?->id,
                ],
            );

            $this->syncProductImages($product, $this->resolveImages($data));

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

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function resolveImages(array $data): array
    {
        $images = $data['images'] ?? [];

        if (is_string($images)) {
            return $this->gallery($images);
        }

        return array_values($images);
    }

    /**
     * @return list<string>
     */
    private function gallery(string $key): array
    {
        return match ($key) {
            'moto' => self::GALLERY_MOTO,
            'accessory' => self::GALLERY_ACCESSORY,
            'spare' => self::GALLERY_SPARE,
            'battery' => self::GALLERY_BATTERY,
            'lubricant' => self::GALLERY_LUBRICANT,
            'tire' => self::GALLERY_TIRE,
            default => [],
        };
    }

    /**
     * @param  list<string>  $images
     */
    private function syncProductImages(Product $product, array $images): void
    {
        $product->images()->delete();

        foreach ($images as $index => $path) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }
    }

    private function seedProductOffers(): void
    {
        $offers = [
            [
                'sku' => 'MW-ACC-001',
                'offer_price' => 399.90,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(30),
            ],
            [
                'sku' => 'MW-MOTO-004',
                'offer_price' => 11999.00,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(14),
            ],
            [
                'sku' => 'MW-LUB-001',
                'offer_price' => 45.00,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(7),
            ],
            [
                'sku' => 'MW-NEU-001',
                'offer_price' => 249.00,
                'starts_at' => now()->addDays(7),
                'ends_at' => now()->addDays(21),
            ],
        ];

        foreach ($offers as $data) {
            $product = Product::query()->where('sku', $data['sku'])->first();

            if ($product === null) {
                continue;
            }

            ProductOffer::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'offer_price_amount' => $data['offer_price'],
                ],
                [
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                    'currency' => $product->currency,
                ],
            );
        }
    }
}

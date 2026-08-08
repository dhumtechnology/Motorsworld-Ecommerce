<?php

namespace Database\Seeders;

use App\Models\Products\Brand;
use App\Models\Products\Category;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Seed initial brands and categories only.
     */
    public function run(): void
    {
        $this->seedCategories();
        $this->seedBrands();
    }

    private function seedCategories(): void
    {
        $definitions = [
            'Motos' => 'Motocicletas nuevas y usadas',
            'Accesorios' => 'Cascos, guantes, chaquetas y equipamiento',
            'Baterías' => 'Baterías para motocicletas',
            'Neumáticos' => 'Neumáticos delanteros y traseros',
            'Repuestos' => 'Filtros, pastillas de freno, transmisión y consumibles',
        ];

        foreach ($definitions as $name => $description) {
            Category::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }

    private function seedBrands(): void
    {
        foreach (['KTM', 'Husqvarna', 'Royal Enfield', 'CFMOTO', 'CFLITE'] as $name) {
            Brand::query()->updateOrCreate(
                ['name' => $name],
                ['image' => null],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Locations\Department;
use App\Models\Locations\District;
use App\Models\Locations\Province;
use App\Models\Shipping\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    public function run(): void
    {
        if (ShippingRate::query()->exists()) {
            return;
        }

        $lima = Department::query()->where('ubigeo', '15')->first();
        $callao = Department::query()->where('ubigeo', '07')->first();
        $limaProvince = Province::query()->where('ubigeo', '1501')->first();

        if ($lima === null) {
            return;
        }

        $zones = [
            [
                'name' => 'Zona Cercana',
                'price' => 12,
                'sort_order' => 1,
                'description' => 'Miraflores, San Isidro, Barranco, Surquillo, San Borja, Santiago de Surco, Magdalena, Lince, Jesús María, San Miguel, La Victoria',
                'districts' => [
                    'Miraflores', 'San Isidro', 'Barranco', 'Surquillo', 'San Borja',
                    'Santiago de Surco', 'Magdalena del Mar', 'Lince', 'Jesus Maria',
                    'San Miguel', 'La Victoria',
                ],
                'department_id' => $lima->id,
                'province_id' => $limaProvince?->id,
            ],
            [
                'name' => 'Zona Intermedia',
                'price' => 18,
                'sort_order' => 2,
                'description' => 'Lima Cercado, Breña, San Luis, Pueblo Libre, Rímac, Chorrillos, La Molina, Ate, Santa Anita, El Agustino, Callao',
                'districts' => [
                    'Lima', 'Brena', 'San Luis', 'Pueblo Libre', 'Rimac', 'Chorrillos',
                    'La Molina', 'Ate', 'Santa Anita', 'El Agustino',
                ],
                'department_id' => $lima->id,
                'province_id' => $limaProvince?->id,
                'extra_department_id' => $callao?->id,
                'extra_districts' => ['Callao'],
            ],
            [
                'name' => 'Zona Lejana',
                'price' => 25,
                'sort_order' => 3,
                'description' => 'Los Olivos, San Martín de Porres, San Juan de Lurigancho, Villa El Salvador, Villa María del Triunfo, Lurín, Lurigancho',
                'districts' => [
                    'Los Olivos', 'San Martin de Porres', 'San Juan de Lurigancho',
                    'Villa El Salvador', 'Villa Maria del Triunfo', 'Lurin', 'Lurigancho',
                ],
                'department_id' => $lima->id,
                'province_id' => $limaProvince?->id,
            ],
            [
                'name' => 'Provincias',
                'price' => 35,
                'sort_order' => 4,
                'description' => 'Envío vía Shalom u Olva a nivel nacional.',
                'is_fallback' => true,
                'districts' => [],
            ],
        ];

        foreach ($zones as $zone) {
            $rate = ShippingRate::query()->create([
                'name' => $zone['name'],
                'description' => $zone['description'],
                'price' => $zone['price'],
                'currency' => 'PEN',
                'is_fallback' => (bool) ($zone['is_fallback'] ?? false),
                'is_active' => true,
                'sort_order' => $zone['sort_order'],
            ]);

            $ids = $this->districtIds(
                $zone['districts'] ?? [],
                $zone['department_id'] ?? null,
                $zone['province_id'] ?? null,
            );

            if (! empty($zone['extra_districts']) && ! empty($zone['extra_department_id'])) {
                $ids = array_merge($ids, $this->districtIds(
                    $zone['extra_districts'],
                    $zone['extra_department_id'],
                ));
            }

            if ($ids !== []) {
                $rate->districts()->sync(array_values(array_unique($ids)));
            }
        }
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function districtIds(array $names, ?int $departmentId, ?int $provinceId = null): array
    {
        if ($names === [] || $departmentId === null) {
            return [];
        }

        $normalized = array_map(fn (string $name) => $this->fold($name), $names);

        return District::query()
            ->where('department_id', $departmentId)
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->get(['id', 'name'])
            ->filter(fn (District $district) => in_array($this->fold($district->name), $normalized, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function fold(string $name): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');

        return strtr($name, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        ]);
    }
}

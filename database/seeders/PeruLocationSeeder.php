<?php

namespace Database\Seeders;

use App\Models\Locations\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeruLocationSeeder extends Seeder
{
    public function run(): void
    {
        $peru = Country::query()->updateOrCreate(
            ['iso2' => 'PE'],
            [
                'name' => 'Perú',
                'iso3' => 'PER',
                'is_active' => true,
            ],
        );

        if (DB::table('departments')->where('country_id', $peru->id)->exists()) {
            return;
        }

        $departments = $this->readJson('departamentos.json', 'ubigeo_departamentos');
        $provinces = $this->readJson('provincias.json', 'ubigeo_provincias');
        $districts = $this->readJson('distritos.json', 'ubigeo_distritos');

        $now = now();
        $departmentRows = [];

        foreach ($departments as $department) {
            $departmentRows[] = [
                'id' => (int) $department['id'],
                'country_id' => $peru->id,
                'name' => $this->titleName((string) $department['departamento']),
                'ubigeo' => str_pad((string) $department['ubigeo'], 2, '0', STR_PAD_LEFT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('departments')->insert($departmentRows);

        $provinceRows = [];
        foreach ($provinces as $province) {
            $provinceRows[] = [
                'id' => (int) $province['id'],
                'department_id' => (int) $province['departamento_id'],
                'name' => $this->titleName((string) $province['provincia']),
                'ubigeo' => str_pad((string) $province['ubigeo'], 4, '0', STR_PAD_LEFT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($provinceRows, 200) as $chunk) {
            DB::table('provinces')->insert($chunk);
        }

        $districtRows = [];
        foreach ($districts as $district) {
            $districtRows[] = [
                'id' => (int) $district['id'],
                'department_id' => (int) $district['departamento_id'],
                'province_id' => (int) $district['provincia_id'],
                'name' => $this->titleName((string) $district['distrito']),
                'ubigeo' => str_pad((string) $district['ubigeo'], 6, '0', STR_PAD_LEFT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($districtRows, 300) as $chunk) {
            DB::table('districts')->insert($chunk);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJson(string $filename, string $key): array
    {
        $path = database_path('data/ubigeo/'.$filename);
        $payload = json_decode((string) file_get_contents($path), true);

        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
    }

    private function titleName(string $name): string
    {
        $name = mb_convert_case(mb_strtolower(trim($name), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        return str_replace(
            [' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y '],
            [' de ', ' del ', ' la ', ' las ', ' los ', ' y '],
            $name,
        );
    }
}

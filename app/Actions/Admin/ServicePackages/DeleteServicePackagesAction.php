<?php

namespace App\Actions\Admin\ServicePackages;

use App\Models\Appointments\ServicePackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteServicePackagesAction
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($ids) {
            $packages = ServicePackage::query()
                ->withCount('appointments')
                ->whereIn('id', $ids)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($packages as $package) {
                if ($package->appointments_count > 0) {
                    $blocked[] = $package->name;

                    continue;
                }

                $deletableIds[] = $package->id;
            }

            if ($deletableIds !== []) {
                ServicePackage::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar paquetes con reservas asociadas: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

<?php

namespace App\Actions\Admin\ServiceTypes;

use App\Models\Appointments\ServiceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteServiceTypesAction
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
            $serviceTypes = ServiceType::query()
                ->withCount('appointments')
                ->whereIn('id', $ids)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($serviceTypes as $serviceType) {
                if ($serviceType->appointments_count > 0) {
                    $blocked[] = $serviceType->name;

                    continue;
                }

                $deletableIds[] = $serviceType->id;
            }

            if ($deletableIds !== []) {
                ServiceType::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar servicios con reservas asociadas: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

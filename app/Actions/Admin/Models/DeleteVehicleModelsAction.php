<?php

namespace App\Actions\Admin\Models;

use App\Models\Products\VehicleModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteVehicleModelsAction
{
    /**
     * @param  list<int>  $modelIds
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $modelIds): array
    {
        $modelIds = array_values(array_unique(array_map('intval', $modelIds)));

        if ($modelIds === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($modelIds) {
            $models = VehicleModel::query()
                ->withCount('products')
                ->whereIn('id', $modelIds)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($models as $model) {
                if ($model->products_count > 0) {
                    $blocked[] = $model->name;

                    continue;
                }

                $deletableIds[] = $model->id;
            }

            if ($deletableIds !== []) {
                VehicleModel::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar modelos con productos asociados: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

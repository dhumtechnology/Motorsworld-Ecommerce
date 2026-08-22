<?php

namespace App\Actions\Admin\Models;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Support\Facades\DB;

class DeleteVehicleModelsAction
{
    public function __construct(
        private readonly DeleteProductsAction $deleteProducts,
    ) {}

    /**
     * @param  list<int>  $modelIds
     * @return array{deleted: int}
     */
    public function execute(array $modelIds): array
    {
        $modelIds = array_values(array_unique(array_map('intval', $modelIds)));

        if ($modelIds === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($modelIds) {
            $models = VehicleModel::query()
                ->whereIn('id', $modelIds)
                ->get();

            foreach ($models as $model) {
                $productIds = Product::query()
                    ->where('model_id', $model->id)
                    ->pluck('id')
                    ->all();

                if ($productIds !== []) {
                    $this->deleteProducts->execute($productIds);
                }
            }

            $deleted = VehicleModel::query()->whereIn('id', $modelIds)->delete();

            return [
                'deleted' => $deleted,
            ];
        });
    }
}

<?php

namespace App\Actions\Admin\Models;

use App\Actions\Admin\Products\DeleteProductsAction;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteVehicleModelsAction
{
    public function __construct(
        private readonly DeleteProductsAction $deleteProducts,
    ) {}

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
                ->whereIn('id', $modelIds)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($models as $model) {
                $products = Product::query()
                    ->withCount('orderItems')
                    ->where('model_id', $model->id)
                    ->get();

                if ($products->contains(fn (Product $product) => $product->order_items_count > 0)) {
                    $blocked[] = $model->name;

                    continue;
                }

                if ($products->isNotEmpty()) {
                    $this->deleteProducts->execute($products->pluck('id')->all());
                }

                $deletableIds[] = $model->id;
            }

            if ($deletableIds !== []) {
                VehicleModel::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar modelos con productos vinculados a pedidos: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

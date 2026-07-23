<?php

namespace App\Actions\Admin\PaymentMethods;

use App\Models\Orders\Payment;
use App\Models\Payments\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePaymentMethodsAction
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
            $paymentMethods = PaymentMethod::query()
                ->whereIn('id', $ids)
                ->get();

            $codesInUse = Payment::query()
                ->whereIn('method', $paymentMethods->pluck('code')->all())
                ->distinct()
                ->pluck('method')
                ->map(fn ($method) => (string) $method)
                ->all();

            $blocked = [];
            $deletableIds = [];

            foreach ($paymentMethods as $paymentMethod) {
                if (in_array($paymentMethod->code, $codesInUse, true)) {
                    $blocked[] = $paymentMethod->name;

                    continue;
                }

                $deletableIds[] = $paymentMethod->id;
            }

            if ($deletableIds !== []) {
                PaymentMethod::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar medios con pagos asociados: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

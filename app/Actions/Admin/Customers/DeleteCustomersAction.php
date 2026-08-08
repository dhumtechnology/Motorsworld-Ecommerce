<?php

namespace App\Actions\Admin\Customers;

use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCustomersAction
{
    /**
     * Soft-delete customer accounts (role Usuario) and their profiles.
     *
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
            $customers = User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'Usuario'))
                ->whereIn('id', $ids)
                ->with('customerProfile')
                ->get();

            $blocked = [];
            $deleted = 0;

            foreach ($customers as $customer) {
                if ($customer->canAccessAdmin()) {
                    $blocked[] = $customer->email;

                    continue;
                }

                $customer->customerProfile?->delete();
                $customer->delete();
                $deleted++;
            }

            if ($deleted === 0 && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pudieron eliminar los clientes seleccionados.',
                ]);
            }

            return [
                'deleted' => $deleted,
                'blocked' => $blocked,
            ];
        });
    }
}

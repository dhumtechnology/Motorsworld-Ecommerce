<?php

namespace App\Actions\Admin\Roles;

use App\Models\Auth\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteRolesAction
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
            $roles = Role::query()->whereIn('id', $ids)->get();
            $blocked = [];
            $deletableIds = [];

            foreach ($roles as $role) {
                if ($role->isSystem()) {
                    $blocked[] = $role->name;

                    continue;
                }

                $deletableIds[] = $role->id;
            }

            if ($deletableIds !== []) {
                Role::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No se pueden eliminar roles del sistema: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

<?php

namespace App\Actions\Admin\Permissions;

use App\Models\Auth\Permission;
use Illuminate\Support\Facades\DB;

class DeletePermissionsAction
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: int}
     */
    public function execute(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($ids) {
            $deleted = Permission::query()->whereIn('id', $ids)->delete();

            return ['deleted' => $deleted];
        });
    }
}

<?php

namespace App\Actions\Admin\Users;

use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAdminUsersAction
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $ids, ?int $currentUserId = null): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($ids, $currentUserId) {
            $users = User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'Administrador'))
                ->whereIn('id', $ids)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($users as $user) {
                if ($currentUserId !== null && $user->id === $currentUserId) {
                    $blocked[] = $user->email;

                    continue;
                }

                $deletableIds[] = $user->id;
            }

            if ($deletableIds !== []) {
                User::query()->whereIn('id', $deletableIds)->delete();
            }

            if ($deletableIds === [] && $blocked !== []) {
                throw ValidationException::withMessages([
                    'ids' => 'No puedes eliminar tu propia cuenta: '.implode(', ', $blocked).'.',
                ]);
            }

            return [
                'deleted' => count($deletableIds),
                'blocked' => $blocked,
            ];
        });
    }
}

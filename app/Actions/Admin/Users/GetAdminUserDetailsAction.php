<?php

namespace App\Actions\Admin\Users;

use App\Models\Auth\User;

class GetAdminUserDetailsAction
{
    /**
     * @return array{
     *     user: User,
     *     stats: array<string, mixed>
     * }
     */
    public function execute(User $user): array
    {
        $user->load('roles:id,name');

        return [
            'user' => $user,
            'stats' => [
                'sessions_count' => $user->sessions()->count(),
                'notifications_count' => $user->appNotifications()->count(),
                'is_current' => (int) $user->id === (int) auth()->id(),
            ],
        ];
    }
}

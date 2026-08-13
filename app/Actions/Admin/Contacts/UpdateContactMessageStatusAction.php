<?php

namespace App\Actions\Admin\Contacts;

use App\Enums\Contacts\ContactMessageStatus;
use App\Models\Auth\User;
use App\Models\Contacts\ContactMessage;

class UpdateContactMessageStatusAction
{
    public function execute(
        ContactMessage $message,
        ContactMessageStatus $status,
        User $admin,
        ?string $adminNotes = null,
    ): ContactMessage {
        $message->forceFill([
            'status' => $status,
            'handled_by' => $admin->id,
            'admin_notes' => $adminNotes !== null ? trim($adminNotes) : $message->admin_notes,
        ])->save();

        return $message->fresh(['user', 'handler']);
    }
}

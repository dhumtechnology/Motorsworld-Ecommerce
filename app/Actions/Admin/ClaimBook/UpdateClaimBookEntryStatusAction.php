<?php

namespace App\Actions\Admin\ClaimBook;

use App\Enums\Claims\ClaimBookStatus;
use App\Models\Auth\User;
use App\Models\Claims\ClaimBookEntry;

class UpdateClaimBookEntryStatusAction
{
    public function execute(
        ClaimBookEntry $entry,
        ClaimBookStatus $status,
        User $admin,
        ?string $adminNotes = null,
    ): ClaimBookEntry {
        $entry->forceFill([
            'status' => $status,
            'handled_by' => $admin->id,
            'admin_notes' => $adminNotes !== null ? trim($adminNotes) : $entry->admin_notes,
        ])->save();

        return $entry->fresh(['user', 'handler']);
    }
}

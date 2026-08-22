<?php

namespace App\Actions\Admin\ClaimBook;

use App\Enums\Claims\ClaimBookStatus;
use App\Mail\ClaimBookReplyMail;
use App\Models\Auth\User;
use App\Models\Claims\ClaimBookEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReplyClaimBookEntryAction
{
    public function execute(
        ClaimBookEntry $entry,
        string $reply,
        User $admin,
        ?ClaimBookStatus $status = null,
        ?string $adminNotes = null,
    ): ClaimBookEntry {
        $entry = DB::transaction(function () use ($entry, $reply, $admin, $status, $adminNotes): ClaimBookEntry {
            $entry->forceFill([
                'admin_reply' => $reply,
                'replied_at' => now(),
                'handled_by' => $admin->id,
                'status' => $status ?? ClaimBookStatus::InProgress,
                'admin_notes' => $adminNotes !== null && trim($adminNotes) !== ''
                    ? trim($adminNotes)
                    : $entry->admin_notes,
            ])->save();

            return $entry->fresh(['user', 'handler']);
        });

        try {
            Mail::to($entry->email)->send(new ClaimBookReplyMail($entry));
        } catch (Throwable $e) {
            report($e);

            throw $e;
        }

        return $entry;
    }
}

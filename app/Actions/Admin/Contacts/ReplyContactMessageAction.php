<?php

namespace App\Actions\Admin\Contacts;

use App\Enums\Contacts\ContactMessageStatus;
use App\Mail\ContactMessageReplyMail;
use App\Models\Auth\User;
use App\Models\Contacts\ContactMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReplyContactMessageAction
{
    public function execute(
        ContactMessage $message,
        string $reply,
        User $admin,
        ?ContactMessageStatus $status = null,
        ?string $adminNotes = null,
    ): ContactMessage {
        $message = DB::transaction(function () use ($message, $reply, $admin, $status, $adminNotes): ContactMessage {
            $message->forceFill([
                'admin_reply' => $reply,
                'replied_at' => now(),
                'handled_by' => $admin->id,
                'status' => $status ?? ContactMessageStatus::InProgress,
                'admin_notes' => $adminNotes !== null && trim($adminNotes) !== ''
                    ? trim($adminNotes)
                    : $message->admin_notes,
            ])->save();

            return $message->fresh(['user', 'handler']);
        });

        try {
            Mail::to($message->email)->send(new ContactMessageReplyMail($message));
        } catch (Throwable $e) {
            report($e);

            throw $e;
        }

        return $message;
    }
}

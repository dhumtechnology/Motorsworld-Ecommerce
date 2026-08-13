<?php

namespace App\Actions\Shop;

use App\Enums\Contacts\ContactMessageStatus;
use App\Mail\ContactMessageReceivedMail;
use App\Models\Auth\User;
use App\Models\Contacts\ContactMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StoreContactMessageAction
{
    /**
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     document: string,
     *     phone: string,
     *     email: string,
     *     message: string
     * }  $data
     */
    public function execute(array $data, ?User $user = null): ContactMessage
    {
        $message = DB::transaction(function () use ($data, $user): ContactMessage {
            return ContactMessage::query()->create([
                'code' => $this->nextCode(),
                'user_id' => $user?->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'document' => $data['document'],
                'phone' => $data['phone'],
                'email' => strtolower($data['email']),
                'message' => $data['message'],
                'status' => ContactMessageStatus::Pending,
            ]);
        });

        try {
            Mail::to($message->email)->send(new ContactMessageReceivedMail($message));
        } catch (Throwable $e) {
            report($e);
        }

        return $message;
    }

    private function nextCode(): string
    {
        $prefix = 'CT-'.now()->format('Ymd').'-';
        $latest = ContactMessage::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $sequence = 1;
        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Actions\Shop;

use App\Enums\Claims\ClaimBookGoodType;
use App\Enums\Claims\ClaimBookStatus;
use App\Enums\Claims\ClaimBookType;
use App\Mail\ClaimBookReceivedMail;
use App\Models\Auth\User;
use App\Models\Claims\ClaimBookEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StoreClaimBookEntryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?User $user = null): ClaimBookEntry
    {
        $entry = DB::transaction(function () use ($data, $user): ClaimBookEntry {
            $entry = ClaimBookEntry::query()->create([
                'code' => $this->nextCode(),
                'user_id' => $user?->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'document' => $data['document'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'email' => strtolower($data['email']),
                'good_type' => ClaimBookGoodType::from($data['good_type']),
                'good_description' => $data['good_description'],
                'claimed_amount' => $data['claimed_amount'],
                'claim_type' => ClaimBookType::from($data['claim_type']),
                'detail' => $data['detail'],
                'consumer_request' => $data['consumer_request'],
                'status' => ClaimBookStatus::Pending,
            ]);

            return $entry;
        });

        try {
            Mail::to($entry->email)->send(new ClaimBookReceivedMail($entry));
        } catch (Throwable $e) {
            report($e);
        }

        return $entry;
    }

    private function nextCode(): string
    {
        $prefix = 'LR-'.now()->format('Ymd').'-';
        $latest = ClaimBookEntry::query()
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

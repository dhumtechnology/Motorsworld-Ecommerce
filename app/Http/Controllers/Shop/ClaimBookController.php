<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\StoreClaimBookEntryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreClaimBookRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClaimBookController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user?->customerProfile;

        return view('shop.claim-book.index', [
            'contact' => config('shop.contact'),
            'prefill' => [
                'first_name' => $profile?->first_name ?? '',
                'last_name' => $profile?->last_name ?? '',
                'document' => $profile?->document ?? '',
                'phone' => $profile?->phone ?? '',
                'email' => $user?->email ?? '',
                'address' => $profile?->defaultShippingAddress?->line1 ?? '',
            ],
        ]);
    }

    public function store(
        StoreClaimBookRequest $request,
        StoreClaimBookEntryAction $storeClaimBookEntry,
    ): RedirectResponse {
        $entry = $storeClaimBookEntry->execute(
            $request->claimData(),
            $request->user(),
        );

        return redirect()
            ->route('shop.claim-book')
            ->with(
                'status',
                "Tu {$entry->claim_type->label()} fue registrada con el código {$entry->code}. Te enviamos la confirmación a {$entry->email}.",
            );
    }
}

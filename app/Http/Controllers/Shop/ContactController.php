<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\StoreContactMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreContactRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user?->customerProfile;

        return view('shop.contact.index', [
            'mapEmbedUrl' => config('shop.map_embed_url'),
            'contact' => config('shop.contact'),
            'prefill' => [
                'first_name' => $profile?->first_name ?? '',
                'last_name' => $profile?->last_name ?? '',
                'document' => $profile?->document ?? '',
                'phone' => $profile?->phone ?? '',
                'email' => $user?->email ?? '',
            ],
        ]);
    }

    public function store(
        StoreContactRequest $request,
        StoreContactMessageAction $storeContactMessage,
    ): RedirectResponse {
        $entry = $storeContactMessage->execute(
            $request->contactData(),
            $request->user(),
        );

        return redirect()
            ->route('shop.contact')
            ->with('status', "Gracias por escribirnos. Registramos tu mensaje con código {$entry->code}. Te responderemos pronto.");
    }
}

<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreClaimBookRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function store(StoreClaimBookRequest $request): RedirectResponse
    {
        $data = $request->claimData();

        Log::channel('stack')->info('Libro de reclamaciones submitted', $data);

        return redirect()
            ->route('shop.claim-book')
            ->with('status', 'Tu hoja de reclamación fue registrada. Nos pondremos en contacto contigo conforme a la normativa vigente.');
    }
}

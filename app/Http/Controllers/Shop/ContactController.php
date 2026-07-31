<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreContactRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('shop.contact.index', [
            'mapEmbedUrl' => config('shop.map_embed_url'),
            'contact' => config('shop.contact'),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $data = $request->contactData();

        Log::channel('stack')->info('Contact form submitted', $data);

        return redirect()
            ->route('shop.contact')
            ->with('status', 'Gracias por escribirnos. Te responderemos pronto.');
    }
}

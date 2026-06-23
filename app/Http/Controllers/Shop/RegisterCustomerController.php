<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\RegisterCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\RegisterCustomerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterCustomerController extends Controller
{
    public function store(
        RegisterCustomerRequest $request,
        RegisterCustomerAction $registerCustomer,
    ): RedirectResponse {
        $user = $registerCustomer->execute(
            email: $request->email(),
            password: $request->password(),
            document: $request->document(),
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            phone: $request->phone(),
        );

        Auth::login($user);

        return redirect()
            ->route('shop.home')
            ->with('status', 'Tu cuenta fue creada correctamente.');
    }
}

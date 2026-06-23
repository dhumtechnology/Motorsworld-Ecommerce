<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(
        LoginUserRequest $request,
        LoginUserAction $loginUser,
    ): RedirectResponse {
        $user = $loginUser->execute(
            email: $request->email(),
            password: $request->password(),
        );

        Auth::login($user);

        if ($user->hasRole('Administrador')) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Sesión iniciada correctamente.');
        }

        return redirect()
            ->route('shop.home')
            ->with('status', 'Sesión iniciada correctamente.');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Cart\MergeGuestCartAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        LoginUserRequest $request,
        LoginUserAction $loginUser,
        MergeGuestCartAction $mergeGuestCart,
    ): RedirectResponse {
        $sessionId = $request->session()->getId();

        $user = $loginUser->execute(
            email: $request->email(),
            password: $request->password(),
        );

        Auth::login($user, $request->boolean('remember'));

        $mergeGuestCart->execute($user, $sessionId);

        if ($user->hasRole('Administrador')) {
            return redirect()
                ->intended(route('admin.dashboard'))
                ->with('status', 'Sesión iniciada correctamente.');
        }

        return redirect()
            ->intended(route('shop.catalog'))
            ->with('status', 'Sesión iniciada correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Sesión cerrada correctamente.');
    }
}

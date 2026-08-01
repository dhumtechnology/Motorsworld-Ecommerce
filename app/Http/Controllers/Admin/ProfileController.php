<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\ChangeOwnPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeOwnPasswordRequest;
use App\Http\Requests\Admin\UpdateOwnAdminProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('roles');

        return view('admin.profile.show', [
            'user' => $user,
        ]);
    }

    public function update(
        UpdateOwnAdminProfileRequest $request,
    ): RedirectResponse {
        $request->user()->forceFill([
            'email' => $request->email(),
        ])->save();

        return redirect()
            ->route('admin.profile.show')
            ->with('status', 'Tu perfil se actualizó correctamente.');
    }

    public function updatePassword(
        ChangeOwnPasswordRequest $request,
        ChangeOwnPasswordAction $changePassword,
    ): RedirectResponse {
        $changePassword->execute(
            $request->user(),
            $request->currentPassword(),
            $request->newPassword(),
        );

        return redirect()
            ->route('admin.profile.show')
            ->with('status', 'Tu contraseña se actualizó correctamente.');
    }
}

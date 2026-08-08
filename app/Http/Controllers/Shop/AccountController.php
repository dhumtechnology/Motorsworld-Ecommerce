<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Auth\ChangeOwnPasswordAction;
use App\Actions\Shop\UpdateCustomerProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeOwnPasswordRequest;
use App\Http\Requests\Shop\UpdateCustomerProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->canAccessAdmin() && ! $user->hasRole('Usuario')) {
            return redirect()->route('admin.profile.show');
        }

        $user->load('customerProfile');

        $orders = $user->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(8, ['*'], 'orders_page')
            ->withQueryString();

        $appointments = $user->appointments()
            ->with(['serviceType:id,name', 'servicePackage:id,name', 'brand:id,name', 'vehicleModel:id,name'])
            ->latest('appointment_at')
            ->paginate(8, ['*'], 'appointments_page')
            ->withQueryString();

        return view('shop.account.index', [
            'user' => $user,
            'profile' => $user->customerProfile,
            'orders' => $orders,
            'appointments' => $appointments,
        ]);
    }

    public function updateProfile(
        UpdateCustomerProfileRequest $request,
        UpdateCustomerProfileAction $updateProfile,
    ): RedirectResponse {
        $updateProfile->execute($request->user(), $request->profileAttributes());

        return redirect()
            ->route('shop.account.show')
            ->with('status', 'Tus datos se actualizaron correctamente.');
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
            ->route('shop.account.show')
            ->with('status', 'Tu contraseña se actualizó correctamente.');
    }
}

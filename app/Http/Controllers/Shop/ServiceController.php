<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\GetAvailableAppointmentSlotsAction;
use App\Actions\Shop\StoreShopAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreShopAppointmentRequest;
use App\Models\Appointments\ServicePackage;
use App\Models\Appointments\ServiceType;
use App\Models\Products\Brand;
use App\Models\Products\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('shop.services.index');
    }

    public function servicesList(): View
    {
        return view('shop.services.list', [
            'serviceTypes' => ServiceType::query()
                ->with(['packages' => fn($q) => $q->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function booking(Request $request): View
    {
        $user = $request->user();
        $profile = $user?->customerProfile;

        $serviceTypes = ServiceType::query()
            ->with(['packages' => fn($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->withMotorcycleProducts()
            ->orderBy('name')
            ->get(['id', 'name']);

        $models = VehicleModel::query()
            ->withMotorcycleProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id']);

        return view('shop.services.booking', [
            'serviceTypes' => $serviceTypes,
            'brands' => $brands,
            'models' => $models,
            'packagesByType' => $serviceTypes
                ->mapWithKeys(fn(ServiceType $type) => [
                    $type->id => $type->packages->map(fn(ServicePackage $package) => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => $package->price,
                    ])->values(),
                ]),
            'modelsByBrand' => $models
                ->groupBy('brand_id')
                ->map(fn($group) => $group->map(fn(VehicleModel $model) => [
                    'id' => $model->id,
                    'name' => $model->name,
                ])->values()),
            'prefill' => [
                'first_name' => $profile?->first_name ?? '',
                'last_name' => $profile?->last_name ?? '',
                'customer_document' => $profile?->document ?? '',
                'customer_phone' => $profile?->phone ?? '',
                'customer_email' => $user?->email ?? '',
            ],
        ]);
    }

    public function store(
        StoreShopAppointmentRequest $request,
        StoreShopAppointmentAction $storeAppointment,
    ): RedirectResponse {
        try {
            $storeAppointment->execute($request->appointmentData(), $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()
            ->route('shop.services.booking')
            ->with('status', 'Tu reserva fue registrada. Te enviamos un correo con la confirmación de tus datos, fecha y hora.');
    }

    public function availableSlots(
        Request $request,
        GetAvailableAppointmentSlotsAction $availableSlots,
    ): JsonResponse {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        return response()->json([
            'date' => $request->string('date')->toString(),
            'slots' => $availableSlots->execute($request->string('date')->toString()),
        ]);
    }
}

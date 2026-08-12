<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\GetAvailableAppointmentSlotsAction;
use App\Actions\Shop\GetPopularProductsAction;
use App\Actions\Shop\StoreShopAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreShopAppointmentRequest;
use App\Models\Appointments\ServicePackage;
use App\Models\Appointments\ServiceType;
use App\Models\Products\Brand;
use App\Models\Products\VehicleModel;
use App\Services\Cart\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index(
        Request $request,
        GetPopularProductsAction $popularProducts,
        CartResolver $cartResolver,
    ): View {
        $user = $request->user();
        $profile = $user?->customerProfile;

        $serviceTypes = ServiceType::query()
            ->with(['packages' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $brands = Brand::query()->orderBy('name')->get(['id', 'name']);
        $models = VehicleModel::query()
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id']);

        $cart = $cartResolver->resolve($user, $request->session()->getId());
        $cartQuantities = $cart->items()
            ->pluck('quantity', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();

        return view('shop.services.index', [
            'serviceTypes' => $serviceTypes,
            'brands' => $brands,
            'models' => $models,
            'packagesByType' => $serviceTypes
                ->mapWithKeys(fn (ServiceType $type) => [
                    $type->id => $type->packages->map(fn (ServicePackage $package) => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => $package->price,
                    ])->values(),
                ]),
            'modelsByBrand' => $models
                ->groupBy('brand_id')
                ->map(fn ($group) => $group->map(fn (VehicleModel $model) => [
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
            'popularProducts' => $popularProducts->execute(10),
            'cartQuantities' => $cartQuantities,
            'mapEmbedUrl' => config('shop.map_embed_url'),
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
            ->route('shop.services.index')
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

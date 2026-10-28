<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Coupons\DeleteCouponsAction;
use App\Actions\Admin\Coupons\GetCouponDetailsAction;
use App\Actions\Admin\Coupons\UpsertCouponAction;
use App\Enums\Coupons\CouponType;
use App\Enums\Payments\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteCouponsRequest;
use App\Http\Requests\Admin\CouponIndexRequest;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\Product;
use App\Services\Coupons\CouponCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class CouponController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertCouponAction $upsertCoupon,
        private readonly DeleteCouponsAction $deleteCoupons,
        private readonly GetCouponDetailsAction $getCouponDetails,
        private readonly CouponCodeGenerator $codeGenerator,
    ) {}

    public function index(CouponIndexRequest $request): View
    {
        $now = now();

        $coupons = Coupon::query()
            ->withCount(['customers', 'usages', 'products', 'categories', 'brands'])
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';
                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('code', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('reason', 'like', $like);
                    });
                },
            )
            ->when(
                $request->statusFilter(),
                function (Builder $query, string $status) use ($now) {
                    match ($status) {
                        'active' => $query->where('is_active', true)->where('starts_at', '<=', $now)->where('ends_at', '>=', $now),
                        'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now),
                        'expired' => $query->where('ends_at', '<', $now),
                        'inactive' => $query->where('is_active', false),
                        default => null,
                    };
                },
            )
            ->when(
                $request->audienceFilter(),
                function (Builder $query, string $audience) {
                    if ($audience === 'public') {
                        $query->whereDoesntHave('customers');
                    } else {
                        $query->whereHas('customers');
                    }
                },
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->statusFilter(),
                'audience' => $request->audienceFilter(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.create', $this->formOptions());
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $payload = $request->couponPayload();
        $coupon = $this->upsertCoupon->execute($payload['attributes'], $payload['relations']);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', "Cupón «{$coupon->code}» creado correctamente.");
    }

    public function show(Coupon $coupon): View
    {
        return view('admin.coupons.show', $this->getCouponDetails->execute($coupon));
    }

    public function edit(Coupon $coupon): View
    {
        $coupon->load(['customers:id', 'products:id', 'categories:id', 'brands:id']);

        return view('admin.coupons.edit', [
            'coupon' => $coupon,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $payload = $request->couponPayload();
        $coupon = $this->upsertCoupon->execute($payload['attributes'], $payload['relations'], $coupon);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', "Cupón «{$coupon->code}» actualizado correctamente.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $result = $this->deleteCoupons->execute([$coupon->id]);

        if ($result['deleted'] === 0) {
            return redirect()
                ->route('admin.coupons.index')
                ->withErrors(['coupon' => "No se pudo eliminar el cupón «{$code}» porque ya fue usado en una compra."]);
        }

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', "Cupón «{$code}» eliminado correctamente.");
    }

    public function bulkDestroy(BulkDeleteCouponsRequest $request): RedirectResponse
    {
        $result = $this->deleteCoupons->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún cupón.',
            $result['deleted'] === 1 => '1 cupón eliminado correctamente.',
            default => "{$result['deleted']} cupones eliminados correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron por tener usos: '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', $message);
    }

    public function generateCode(): JsonResponse
    {
        return response()->json([
            'code' => $this->codeGenerator->generate(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'types' => CouponType::cases(),
            'paymentMethods' => [
                PaymentMethod::Card,
                PaymentMethod::Yape,
            ],
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'sku', 'name'])
                ->map(fn (Product $product) => (object) [
                    'id' => $product->id,
                    'name' => $product->sku.' — '.$product->name,
                ]),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'customers' => $this->customersForSelect(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function customersForSelect(): Collection
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'Usuario'))
            ->with('customerProfile:id,user_id,first_name,last_name')
            ->orderBy('email')
            ->get(['id', 'email'])
            ->map(function (User $user) {
                $profile = $user->customerProfile;
                $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
                $label = $fullName !== '' ? $fullName.' — '.$user->email : $user->email;

                return (object) [
                    'id' => $user->id,
                    'name' => $label,
                ];
            });
    }
}

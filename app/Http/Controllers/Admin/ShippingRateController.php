<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ShippingRates\DeleteShippingRatesAction;
use App\Actions\Admin\ShippingRates\UpsertShippingRateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteShippingRatesRequest;
use App\Http\Requests\Admin\ShippingRateIndexRequest;
use App\Http\Requests\Admin\StoreShippingRateRequest;
use App\Http\Requests\Admin\UpdateShippingRateRequest;
use App\Models\Shipping\ShippingRate;
use App\Services\Locations\LocationCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class ShippingRateController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertShippingRateAction $upsertShippingRate,
        private readonly DeleteShippingRatesAction $deleteShippingRates,
        private readonly LocationCatalog $locations,
    ) {}

    public function index(ShippingRateIndexRequest $request): View
    {
        $rates = ShippingRate::query()
            ->withCount('districts')
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';
                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
                },
            )
            ->when(
                $request->isActiveFilter() !== null,
                fn (Builder $query) => $query->where('is_active', $request->isActiveFilter()),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.shipping-rates.index', [
            'rates' => $rates,
            'filters' => [
                'search' => $request->searchTerm(),
                'is_active' => $request->input('is_active'),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.shipping-rates.create', $this->formOptions());
    }

    public function store(StoreShippingRateRequest $request): RedirectResponse
    {
        $rate = $this->upsertShippingRate->execute(
            $request->shippingRateAttributes(),
            $request->districtIds(),
        );

        return redirect()
            ->route('admin.shipping-rates.index')
            ->with('status', "Tarifa «{$rate->name}» creada correctamente.");
    }

    public function edit(ShippingRate $shippingRate): View
    {
        $shippingRate->load('districts:id');

        return view('admin.shipping-rates.edit', [
            'shippingRate' => $shippingRate,
            ...$this->formOptions($shippingRate->districts->pluck('id')->all()),
        ]);
    }

    public function update(UpdateShippingRateRequest $request, ShippingRate $shippingRate): RedirectResponse
    {
        $rate = $this->upsertShippingRate->execute(
            $request->shippingRateAttributes(),
            $request->districtIds(),
            $shippingRate,
        );

        return redirect()
            ->route('admin.shipping-rates.index')
            ->with('status', "Tarifa «{$rate->name}» actualizada correctamente.");
    }

    public function destroy(ShippingRate $shippingRate): RedirectResponse
    {
        $name = $shippingRate->name;
        $this->deleteShippingRates->execute([$shippingRate->id]);

        return redirect()
            ->route('admin.shipping-rates.index')
            ->with('status', "Tarifa «{$name}» eliminada correctamente.");
    }

    public function bulkDestroy(BulkDeleteShippingRatesRequest $request): RedirectResponse
    {
        $result = $this->deleteShippingRates->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ninguna tarifa.',
            $result['deleted'] === 1 => '1 tarifa eliminada correctamente.',
            default => "{$result['deleted']} tarifas eliminadas correctamente.",
        };

        return redirect()
            ->route('admin.shipping-rates.index')
            ->with('status', $message);
    }

    /**
     * @param  list<int|string>  $districtIds
     * @return array<string, mixed>
     */
    private function formOptions(array $districtIds = []): array
    {
        $ids = array_map('intval', (array) old('district_ids', $districtIds));

        return [
            'districts' => $this->locations->districtsByIds($ids),
            'districtsSource' => route('admin.locations.districts'),
        ];
    }
}

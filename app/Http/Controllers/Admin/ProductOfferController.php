<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Offers\DeleteProductOffersAction;
use App\Actions\Admin\Offers\UpsertProductOfferAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteProductOffersRequest;
use App\Http\Requests\Admin\ProductOfferIndexRequest;
use App\Http\Requests\Admin\StoreProductOfferRequest;
use App\Http\Requests\Admin\UpdateProductOfferRequest;
use App\Models\Products\Product;
use App\Models\Products\ProductOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class ProductOfferController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertProductOfferAction $upsertProductOffer,
        private readonly DeleteProductOffersAction $deleteProductOffers,
    ) {}

    public function index(ProductOfferIndexRequest $request): View
    {
        $now = now();

        $offers = ProductOffer::query()
            ->with(['product:id,sku,name,price_amount,currency'])
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';
                    $query->whereHas('product', function (Builder $productQuery) use ($like) {
                        $productQuery
                            ->where('sku', 'like', $like)
                            ->orWhere('name', 'like', $like);
                    });
                },
            )
            ->when(
                $request->productId(),
                fn (Builder $query, int $productId) => $query->where('product_id', $productId),
            )
            ->when(
                $request->statusFilter(),
                function (Builder $query, string $status) use ($now) {
                    match ($status) {
                        'active' => $query->where('starts_at', '<=', $now)->where('ends_at', '>=', $now),
                        'scheduled' => $query->where('starts_at', '>', $now),
                        'expired' => $query->where('ends_at', '<', $now),
                        default => null,
                    };
                },
            )
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.offers.index', [
            'offers' => $offers,
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->statusFilter(),
                'product_id' => $request->productId(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        $preselectedProductId = request()->integer('product_id') ?: null;

        return view('admin.offers.create', [
            'products' => $this->productsForSelect(),
            'preselectedProductId' => $preselectedProductId,
        ]);
    }

    public function store(StoreProductOfferRequest $request): RedirectResponse
    {
        $offer = $this->upsertProductOffer->execute($request->offerAttributes());

        $redirect = $request->input('redirect_to') === 'product'
            ? redirect()->route('admin.products.show', $offer->product_id)
            : redirect()->route('admin.offers.index');

        return $redirect->with(
            'status',
            "Oferta #{$offer->id} creada para «{$offer->product?->name}».",
        );
    }

    public function edit(ProductOffer $productOffer): View
    {
        $productOffer->load('product:id,sku,name,price_amount,currency');

        return view('admin.offers.edit', [
            'offer' => $productOffer,
            'products' => $this->productsForSelect(),
        ]);
    }

    public function update(UpdateProductOfferRequest $request, ProductOffer $productOffer): RedirectResponse
    {
        $offer = $this->upsertProductOffer->execute(
            $request->offerAttributes(),
            $productOffer,
        );

        return redirect()
            ->route('admin.offers.index')
            ->with('status', "Oferta #{$offer->id} actualizada correctamente.");
    }

    public function destroy(ProductOffer $productOffer): RedirectResponse
    {
        $id = $productOffer->id;
        $this->deleteProductOffers->execute([$id]);

        return redirect()
            ->route('admin.offers.index')
            ->with('status', "Oferta #{$id} eliminada correctamente.");
    }

    public function bulkDestroy(BulkDeleteProductOffersRequest $request): RedirectResponse
    {
        $result = $this->deleteProductOffers->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ninguna oferta.',
            $result['deleted'] === 1 => '1 oferta eliminada correctamente.',
            default => "{$result['deleted']} ofertas eliminadas correctamente.",
        };

        return redirect()
            ->route('admin.offers.index')
            ->with('status', $message);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function productsForSelect()
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'price_amount', 'currency']);
    }
}

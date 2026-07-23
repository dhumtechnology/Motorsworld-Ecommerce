<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Inventory\ExportInventoryMovementsAction;
use App\Actions\Inventory\ImportInventoryMovementsAction;
use App\Actions\Inventory\RegisterInventoryMovementAction;
use App\Actions\Inventory\ReverseInventoryMovementAction;
use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportInventoryMovementsRequest;
use App\Http\Requests\Admin\ImportInventoryMovementsRequest;
use App\Http\Requests\Admin\InventoryIndexRequest;
use App\Http\Requests\Admin\StoreInventoryMovementRequest;
use App\Models\Products\Brand;
use App\Models\Products\Category;
use App\Models\Products\InventoryMovement;
use App\Models\Products\Product;
use App\Models\Products\VehicleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly RegisterInventoryMovementAction $registerMovement,
        private readonly ReverseInventoryMovementAction $reverseMovement,
        private readonly ImportInventoryMovementsAction $importMovements,
        private readonly ExportInventoryMovementsAction $exportMovements,
    ) {}

    public function index(InventoryIndexRequest $request): View
    {
        $movements = InventoryMovement::query()
            ->with([
                'product.category:id,name',
                'product.vehicleModel.brand:id,name',
                'order:id',
                'creator:id,email',
            ])
            ->when(
                $request->type(),
                fn (Builder $query, InventoryMovementType $type) => $query->where('type', $type),
            )
            ->when(
                $request->dateFrom(),
                fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from),
            )
            ->when(
                $request->dateTo(),
                fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';
                    $query->whereHas('product', function (Builder $productQuery) use ($like) {
                        $productQuery
                            ->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like);
                    });
                },
            )
            ->when(
                $request->categoryIds() !== [],
                fn (Builder $query) => $query->whereHas(
                    'product',
                    fn (Builder $productQuery) => $productQuery->whereIn('category_id', $request->categoryIds()),
                ),
            )
            ->when(
                $request->brandIds() !== [],
                fn (Builder $query) => $query->whereHas(
                    'product.vehicleModel',
                    fn (Builder $modelQuery) => $modelQuery->whereIn('brand_id', $request->brandIds()),
                ),
            )
            ->when(
                $request->modelIds() !== [],
                fn (Builder $query) => $query->whereHas(
                    'product',
                    fn (Builder $productQuery) => $productQuery->whereIn('model_id', $request->modelIds()),
                ),
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.inventory.index', [
            'movements' => $movements,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'models' => VehicleModel::query()->orderBy('name')->get(['id', 'name', 'brand_id']),
            'types' => InventoryMovementType::cases(),
            'filters' => [
                'search' => $request->searchTerm(),
                'type' => $request->type()?->value,
                'categories' => $request->categoryIds(),
                'brands' => $request->brandIds(),
                'models' => $request->modelIds(),
                'date_from' => $request->dateFrom(),
                'date_to' => $request->dateTo(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.inventory.create', [
            'products' => Product::query()
                ->with('inventory:id,product_id,available_stock')
                ->orderBy('name')
                ->get(['id', 'sku', 'name']),
            'entryReasons' => InventoryMovementReason::forEntries(),
            'exitReasons' => InventoryMovementReason::forManualExits(),
            'types' => InventoryMovementType::cases(),
        ]);
    }

    public function store(StoreInventoryMovementRequest $request): RedirectResponse
    {
        $attrs = $request->movementAttributes();

        $movement = $this->registerMovement->execute([
            'product_id' => $attrs['product_id'],
            'type' => $attrs['type'],
            'reason' => $attrs['reason'],
            'quantity' => $attrs['quantity'],
            'notes' => $attrs['notes'],
            'created_by' => auth()->id(),
        ]);

        $movement->load('product:id,sku,name');
        $sign = $attrs['type'] === InventoryMovementType::Entry ? '+' : '-';

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', "Movimiento #{$movement->id} registrado: {$sign}{$movement->quantity} de «{$movement->product?->name}».");
    }

    public function show(InventoryMovement $inventoryMovement): View
    {
        $inventoryMovement->load([
            'product.inventory',
            'product.category:id,name',
            'product.vehicleModel.brand:id,name',
            'creator:id,email',
            'order.user.customerProfile',
        ]);

        return view('admin.inventory.show', [
            'movement' => $inventoryMovement,
        ]);
    }

    public function destroy(InventoryMovement $inventoryMovement): RedirectResponse
    {
        $this->reverseMovement->execute($inventoryMovement);

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', 'Movimiento revertido y eliminado correctamente.');
    }

    public function importForm(): View
    {
        return view('admin.inventory.import');
    }

    public function import(ImportInventoryMovementsRequest $request): RedirectResponse
    {
        $result = $this->importMovements->execute(
            $request->file('file'),
            auth()->id(),
        );

        $message = "{$result['imported']} movimiento(s) importado(s) correctamente.";

        if ($result['errors'] !== []) {
            $message .= ' Algunas filas fallaron: '.implode(' | ', array_slice($result['errors'], 0, 3));
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', $message);
    }

    public function export(ExportInventoryMovementsRequest $request): StreamedResponse|Response
    {
        return $this->exportMovements->execute($request->ids(), $request->format());
    }

    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'plantilla-inventario.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['sku', 'tipo', 'cantidad', 'motivo', 'notas']);
            fputcsv($out, ['MW-ACC-001', 'entrada', '10', 'purchase', 'Reposición de ejemplo']);
            fputcsv($out, ['MW-ACC-002', 'salida', '1', 'manual', 'Merma de ejemplo']);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

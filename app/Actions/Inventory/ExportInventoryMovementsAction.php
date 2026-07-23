<?php

namespace App\Actions\Inventory;

use App\Models\Products\InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportInventoryMovementsAction
{
    /**
     * @param  list<int>  $ids
     */
    public function execute(array $ids, string $format): StreamedResponse|\Illuminate\Http\Response
    {
        $movements = InventoryMovement::query()
            ->with([
                'product.category:id,name',
                'product.vehicleModel.brand:id,name',
                'order:id',
                'creator:id,email',
            ])
            ->whereIn('id', $ids)
            ->orderByDesc('id')
            ->get();

        if ($movements->isEmpty()) {
            abort(404, 'No hay movimientos para exportar.');
        }

        return $format === 'pdf'
            ? $this->exportPdf($movements)
            : $this->exportExcel($movements);
    }

    /**
     * @param  Collection<int, InventoryMovement>  $movements
     */
    private function exportExcel(Collection $movements): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');

        $headers = ['ID', 'Fecha', 'Tipo', 'SKU', 'Producto', 'Categoría', 'Marca', 'Modelo', 'Cantidad', 'Motivo', 'Orden', 'Notas'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;

        foreach ($movements as $movement) {
            $product = $movement->product;
            $sheet->fromArray([
                $movement->id,
                $movement->created_at?->format('d/m/Y H:i'),
                $movement->type?->label(),
                $product?->sku,
                $product?->name,
                $product?->category?->name,
                $product?->vehicleModel?->brand?->name,
                $product?->vehicleModel?->name,
                $movement->type?->value === 'entry' ? '+'.$movement->quantity : '-'.$movement->quantity,
                $movement->reason?->label(),
                $movement->order_id ? '#'.$movement->order_id : '',
                $movement->notes,
            ], null, 'A'.$row);
            $row++;
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'inventario-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, InventoryMovement>  $movements
     */
    private function exportPdf(Collection $movements): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('admin.inventory.export-pdf', [
            'movements' => $movements,
            'exportedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('inventario-'.now()->format('Ymd-His').'.pdf');
    }
}

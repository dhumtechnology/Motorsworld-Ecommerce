<?php

namespace App\Actions\Inventory;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInventoryImportTemplateAction
{
    /**
     * @return list<list<string>>
     */
    private function rows(): array
    {
        return [
            ['sku', 'tipo', 'cantidad', 'motivo', 'notas'],
            ['MW-ACC-001-STD', 'entrada', '10', 'purchase', 'Reposición de ejemplo'],
            ['MW-ACC-002-STD', 'salida', '1', 'manual', 'Merma de ejemplo'],
        ];
    }

    public function execute(string $format = 'xlsx'): StreamedResponse
    {
        $format = strtolower($format) === 'csv' ? 'csv' : 'xlsx';

        return $format === 'csv'
            ? $this->csv()
            : $this->xlsx();
    }

    private function csv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($this->rows() as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, 'plantilla-inventario.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function xlsx(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Importar');
        $sheet->fromArray($this->rows(), null, 'A1');

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instrucciones');
        $instructions->fromArray([
            ['Campo', 'Descripción', 'Valores válidos'],
            ['sku', 'SKU de la variante (recomendado) o del producto', 'Ej. MW-ACC-001-STD'],
            ['tipo', 'Tipo de movimiento', 'entrada, salida'],
            ['cantidad', 'Cantidad entera mayor o igual a 1', '1, 2, 10…'],
            ['motivo', 'Motivo del movimiento', 'entrada: purchase, return, adjustment, manual · salida: manual, damage, adjustment, return'],
            ['notas', 'Opcional', 'Texto libre'],
            ['', '', ''],
            ['Notas', 'No importar salidas con motivo sale/venta (se generan al pagar órdenes).', ''],
            ['', 'Elimina las filas de ejemplo antes de cargar tu archivo real, o adáptalas a SKUs existentes.', ''],
        ], null, 'A1');

        foreach (range('A', 'C') as $column) {
            $instructions->getColumnDimension($column)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'plantilla-inventario.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

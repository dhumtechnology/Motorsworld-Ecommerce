<?php

namespace App\Actions\Inventory;

use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use App\Models\Products\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportInventoryMovementsAction
{
    public function __construct(
        private readonly RegisterInventoryMovementAction $registerMovement,
    ) {}

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function execute(UploadedFile $file, ?int $createdBy = null): array
    {
        $rows = $this->parseRows($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no contiene filas de datos.',
            ]);
        }

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $createdBy, &$imported, &$errors) {
            foreach ($rows as $index => $row) {
                $line = $index + 2;

                try {
                    $this->importRow($row, $createdBy);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Fila {$line}: ".$e->getMessage();
                }
            }

            if ($imported === 0 && $errors !== []) {
                throw ValidationException::withMessages([
                    'file' => 'No se importó ninguna fila. '.$errors[0],
                ]);
            }
        });

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->parseSpreadsheet($file);
        }

        return $this->parseCsv($file);
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo CSV.',
            ]);
        }

        $header = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($value) => $this->normalizeHeader((string) $value), $data);

                continue;
            }

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $assoc = [];

            foreach ($header as $i => $key) {
                if ($key === '') {
                    continue;
                }

                $assoc[$key] = trim((string) ($data[$i] ?? ''));
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if ($sheet === []) {
            return [];
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) $value), array_shift($sheet) ?? []);
        $rows = [];

        foreach ($sheet as $data) {
            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $assoc = [];

            foreach ($header as $i => $key) {
                if ($key === '') {
                    continue;
                }

                $assoc[$key] = trim((string) ($data[$i] ?? ''));
            }

            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(array $row, ?int $createdBy): void
    {
        $sku = $row['sku'] ?? '';
        $typeRaw = strtolower($row['tipo'] ?? $row['type'] ?? '');
        $quantity = (int) ($row['cantidad'] ?? $row['quantity'] ?? 0);
        $reasonRaw = strtolower($row['motivo'] ?? $row['reason'] ?? '');
        $notes = $row['notas'] ?? $row['notes'] ?? null;

        if ($sku === '') {
            throw new \RuntimeException('Falta el SKU.');
        }

        $productId = Product::query()->where('sku', $sku)->value('id');

        if ($productId === null) {
            throw new \RuntimeException("SKU «{$sku}» no existe.");
        }

        $type = match ($typeRaw) {
            'entry', 'entrada', 'in' => InventoryMovementType::Entry,
            'exit', 'salida', 'out' => InventoryMovementType::Exit,
            default => null,
        };

        if ($type === null) {
            throw new \RuntimeException('Tipo inválido (usa entrada/salida).');
        }

        $reason = $this->resolveReason($type, $reasonRaw);

        $this->registerMovement->execute([
            'product_id' => (int) $productId,
            'type' => $type,
            'reason' => $reason,
            'quantity' => $quantity,
            'notes' => is_string($notes) && trim($notes) !== '' ? trim($notes) : null,
            'created_by' => $createdBy,
        ]);
    }

    private function resolveReason(InventoryMovementType $type, string $reasonRaw): InventoryMovementReason
    {
        if ($reasonRaw === '') {
            return $type === InventoryMovementType::Entry
                ? InventoryMovementReason::Purchase
                : InventoryMovementReason::Manual;
        }

        $aliases = [
            'compra' => InventoryMovementReason::Purchase,
            'purchase' => InventoryMovementReason::Purchase,
            'reposicion' => InventoryMovementReason::Purchase,
            'devolucion' => InventoryMovementReason::Return,
            'return' => InventoryMovementReason::Return,
            'ajuste' => InventoryMovementReason::Adjustment,
            'adjustment' => InventoryMovementReason::Adjustment,
            'manual' => InventoryMovementReason::Manual,
            'merma' => InventoryMovementReason::Damage,
            'dano' => InventoryMovementReason::Damage,
            'daño' => InventoryMovementReason::Damage,
            'damage' => InventoryMovementReason::Damage,
            'venta' => InventoryMovementReason::Sale,
            'sale' => InventoryMovementReason::Sale,
        ];

        $normalized = strtr($reasonRaw, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $reason = $aliases[$normalized] ?? InventoryMovementReason::tryFrom($reasonRaw);

        if ($reason === null) {
            throw new \RuntimeException("Motivo «{$reasonRaw}» no válido.");
        }

        if ($reason === InventoryMovementReason::Sale) {
            throw new \RuntimeException('No se pueden importar salidas por venta; se generan automáticamente.');
        }

        $allowed = $type === InventoryMovementType::Entry
            ? InventoryMovementReason::forEntries()
            : InventoryMovementReason::forManualExits();

        if (! in_array($reason, $allowed, true)) {
            throw new \RuntimeException('El motivo no corresponde al tipo.');
        }

        return $reason;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return match ($value) {
            'sku', 'codigo', 'código' => 'sku',
            'tipo', 'type', 'movimiento' => 'tipo',
            'cantidad', 'quantity', 'qty' => 'cantidad',
            'motivo', 'reason' => 'motivo',
            'notas', 'notes', 'nota', 'observaciones' => 'notas',
            default => $value,
        };
    }

    /**
     * @param  array<int, mixed>  $data
     */
    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}

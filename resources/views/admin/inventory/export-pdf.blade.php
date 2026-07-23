<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Inventario</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f3f3; font-size: 10px; text-transform: uppercase; }
        .entry { color: #067d3c; font-weight: bold; }
        .exit { color: #b42318; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Movimientos de inventario</h1>
    <p class="meta">Exportado: {{ $exportedAt->format('d/m/Y H:i') }} · {{ $movements->count() }} fila(s)</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>SKU</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Cant.</th>
                <th>Motivo</th>
                <th>Orden</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($movements as $movement)
                @php
                    $isEntry = $movement->type?->value === 'entry';
                    $product = $movement->product;
                @endphp
                <tr>
                    <td>#{{ $movement->id }}</td>
                    <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="{{ $isEntry ? 'entry' : 'exit' }}">{{ $movement->type?->label() }}</td>
                    <td>{{ $product?->sku }}</td>
                    <td>{{ $product?->name }}</td>
                    <td>{{ $product?->category?->name }}</td>
                    <td>{{ $product?->vehicleModel?->brand?->name }}</td>
                    <td>{{ $product?->vehicleModel?->name }}</td>
                    <td class="{{ $isEntry ? 'entry' : 'exit' }}">{{ $isEntry ? '+' : '-' }}{{ $movement->quantity }}</td>
                    <td>{{ $movement->reason?->label() }}</td>
                    <td>{{ $movement->order_id ? '#'.$movement->order_id : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

@extends('layouts.admin')

@section('title', 'Importar inventario — Admin')
@section('page-title', 'Importar movimientos')
@section('page-subtitle', 'Carga masiva desde Excel o CSV con plantilla')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-primary transition-colors">
            ← Volver a inventario
        </a>
    </div>

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('admin.inventory.template', ['format' => 'xlsx']) }}"
           class="rounded bg-emerald-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-500 transition-colors">
            Descargar plantilla Excel
        </a>
        <a href="{{ route('admin.inventory.template', ['format' => 'csv']) }}"
           class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
            Descargar plantilla CSV
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-border bg-surface p-6">
            <form method="POST" action="{{ route('admin.inventory.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                @if ($errors->any())
                    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="file" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Archivo *</label>
                    <input id="file" name="file" type="file" required accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text file:mr-4 file:rounded file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-bold file:uppercase file:text-white">
                    <p class="mt-2 text-xs text-muted">Usa la plantilla Excel/CSV. Formatos: .xlsx, .xls, .csv — máx. 5 MB</p>
                </div>

                <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                    Importar archivo
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-border bg-surface p-6">
            <h2 class="text-sm font-title text-text mb-4">Columnas de la plantilla</h2>
            <ul class="space-y-2 text-sm text-text-soft">
                <li><span class="font-mono text-primary">sku</span> — SKU de la <strong>variante</strong> (recomendado) o del producto</li>
                <li><span class="font-mono text-primary">tipo</span> — <code>entrada</code> / <code>salida</code></li>
                <li><span class="font-mono text-primary">cantidad</span> — entero ≥ 1</li>
                <li><span class="font-mono text-primary">motivo</span> — purchase, return, adjustment, manual, damage…</li>
                <li><span class="font-mono text-primary">notas</span> — opcional</li>
            </ul>
            <p class="mt-4 text-xs text-muted">
                La plantilla Excel incluye una hoja de instrucciones. Borra o adapta las filas de ejemplo a SKUs reales antes de importar.
            </p>
            <p class="mt-2 text-xs text-muted">No se pueden importar salidas por venta; esas se crean al pagar una orden.</p>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Importar inventario — Admin')
@section('page-title', 'Importar movimientos')
@section('page-subtitle', 'Carga masiva desde Excel o CSV')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-400 hover:text-orange-400 transition-colors">
            ← Volver a inventario
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
            <form method="POST" action="{{ route('admin.inventory.import.store') }}" enctype="multipart/form-data">
                @csrf

                @if ($errors->any())
                    <div class="mb-6 rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="file" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Archivo *</label>
                    <input id="file" name="file" type="file" required accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="w-full rounded border border-neutral-700 bg-[#252525] px-4 py-2.5 text-sm text-white file:mr-4 file:rounded file:border-0 file:bg-orange-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:uppercase file:text-white">
                    <p class="mt-2 text-xs text-neutral-500">Formatos: .csv, .xlsx, .xls — máx. 5 MB</p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="submit" class="rounded bg-orange-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-orange-500 transition-colors">
                        Importar
                    </button>
                    <a href="{{ route('admin.inventory.template') }}" class="rounded border border-neutral-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-neutral-400 hover:text-white hover:border-neutral-500 transition-colors">
                        Descargar plantilla CSV
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-[#1e1e1e] p-6">
            <h2 class="text-sm font-black uppercase tracking-wider text-white mb-4">Columnas esperadas</h2>
            <ul class="space-y-2 text-sm text-neutral-300">
                <li><span class="font-mono text-orange-400">sku</span> — código del producto (obligatorio)</li>
                <li><span class="font-mono text-orange-400">tipo</span> — entrada / salida</li>
                <li><span class="font-mono text-orange-400">cantidad</span> — número entero ≥ 1</li>
                <li><span class="font-mono text-orange-400">motivo</span> — purchase, return, adjustment, manual, damage…</li>
                <li><span class="font-mono text-orange-400">notas</span> — opcional</li>
            </ul>
            <p class="mt-4 text-xs text-neutral-500">No se pueden importar salidas por venta; esas se crean al pagar una orden.</p>
        </div>
    </div>
@endsection

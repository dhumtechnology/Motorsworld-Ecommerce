@php
    $fieldClass = $fieldClass ?? 'w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';
    $modalCancelBtn = 'rounded-lg border border-border bg-surface px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-text-soft hover:border-border-strong hover:text-text transition-colors font-secondary';
    $modalSubmitBtn = 'rounded-lg bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors font-secondary';
@endphp

{{-- IMPORTANTE: estos formularios deben vivir FUERA del <form> del producto (HTML no admite forms anidados). --}}

<div id="category-modal" data-quick-modal class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6" aria-hidden="true">
    <div data-quick-overlay class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
    <div class="admin-quick-modal-panel relative z-10 w-full max-w-lg" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
        <div class="flex items-start gap-4 border-b border-border px-6 py-5 sm:px-8 sm:py-6">
            <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </div>
            <div class="min-w-0 pt-1">
                <h3 id="category-modal-title" class="font-title text-lg text-text tracking-wide">Nueva categoría</h3>
                <p class="mt-1.5 text-sm text-muted font-secondary leading-relaxed">Se seleccionará automáticamente en el producto.</p>
            </div>
        </div>
        <form data-quick-form="category" class="flex flex-col">
            <div class="space-y-5 px-6 py-6 sm:px-8 sm:py-7">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
                    <input name="name" type="text" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Descripción</label>
                    <textarea name="description" rows="3" class="{{ $fieldClass }}"></textarea>
                </div>
                <p data-quick-error class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-border bg-secondary px-6 py-4 sm:px-8 sm:py-5">
                <button type="button" data-quick-cancel class="{{ $modalCancelBtn }}">Cancelar</button>
                <button type="submit" class="{{ $modalSubmitBtn }}">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="brand-modal" data-quick-modal class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6" aria-hidden="true">
    <div data-quick-overlay class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
    <div class="admin-quick-modal-panel relative z-10 w-full max-w-lg" role="dialog" aria-modal="true" aria-labelledby="brand-modal-title">
        <div class="flex items-start gap-4 border-b border-border px-6 py-5 sm:px-8 sm:py-6">
            <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </div>
            <div class="min-w-0 pt-1">
                <h3 id="brand-modal-title" class="font-title text-lg text-text tracking-wide">Nueva marca</h3>
                <p class="mt-1.5 text-sm text-muted font-secondary leading-relaxed">Se seleccionará automáticamente en el producto.</p>
            </div>
        </div>
        <form data-quick-form="brand" class="flex flex-col" enctype="multipart/form-data">
            <div class="space-y-5 px-6 py-6 sm:px-8 sm:py-7">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
                    <input name="name" type="text" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imagen (opcional)</label>
                    <input name="image" type="file" accept="image/*" class="{{ $fieldClass }}">
                </div>
                <p data-quick-error class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-border bg-secondary px-6 py-4 sm:px-8 sm:py-5">
                <button type="button" data-quick-cancel class="{{ $modalCancelBtn }}">Cancelar</button>
                <button type="submit" class="{{ $modalSubmitBtn }}">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="model-modal" data-quick-modal class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6" aria-hidden="true">
    <div data-quick-overlay class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
    <div class="admin-quick-modal-panel relative z-10 w-full max-w-lg" role="dialog" aria-modal="true" aria-labelledby="model-modal-title">
        <div class="flex items-start gap-4 border-b border-border px-6 py-5 sm:px-8 sm:py-6">
            <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </div>
            <div class="min-w-0 pt-1">
                <h3 id="model-modal-title" class="font-title text-lg text-text tracking-wide">Nuevo modelo</h3>
                <p class="mt-1.5 text-sm text-muted font-secondary leading-relaxed">Se seleccionará automáticamente en el producto.</p>
            </div>
        </div>
        <form data-quick-form="model" class="flex flex-col">
            <div class="space-y-5 px-6 py-6 sm:px-8 sm:py-7">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Marca *</label>
                    <select name="brand_id" required data-model-modal-brand class="{{ $fieldClass }}">
                        <option value="">Seleccionar marca</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Nombre *</label>
                    <input name="name" type="text" required class="{{ $fieldClass }}">
                </div>
                <p data-quick-error class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-border bg-secondary px-6 py-4 sm:px-8 sm:py-5">
                <button type="button" data-quick-cancel class="{{ $modalCancelBtn }}">Cancelar</button>
                <button type="submit" class="{{ $modalSubmitBtn }}">Guardar</button>
            </div>
        </form>
    </div>
</div>

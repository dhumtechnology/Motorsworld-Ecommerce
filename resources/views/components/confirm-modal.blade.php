@props([
    'id' => 'confirm-modal',
    'title' => '¿Confirmar acción?',
    'message' => 'Esta acción no se puede deshacer.',
    'confirmLabel' => 'Confirmar',
    'cancelLabel' => 'Cancelar',
    'formId' => null,
    'method' => 'POST',
    'action' => '#',
    'danger' => true,
])

@php
    $confirmButtonClass = $danger
        ? 'bg-red-600 hover:bg-red-500 focus:ring-red-500'
        : 'bg-orange-600 hover:bg-orange-500 focus:ring-orange-500';
@endphp

<div
    id="{{ $id }}"
    data-confirm-modal
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    aria-hidden="true"
>
    <div data-confirm-overlay class="absolute inset-0 bg-black/70 backdrop-blur-[1px]"></div>

    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        class="relative z-10 w-full max-w-md overflow-hidden rounded-xl border border-neutral-700 bg-[#1e1e1e] shadow-2xl"
    >
        <div class="border-b border-neutral-800 px-6 py-4 flex items-start gap-3">
            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $danger ? 'bg-red-950 text-red-400' : 'bg-orange-950 text-orange-400' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 id="{{ $id }}-title" class="text-lg font-black text-white tracking-wide">
                    {{ $title }}
                </h3>
                <p data-confirm-message class="mt-1 text-sm text-neutral-400 leading-relaxed">
                    {{ $message }}
                </p>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 bg-[#252525]/60">
            <button
                type="button"
                data-confirm-cancel
                class="rounded border border-neutral-700 px-4 py-2 text-sm font-bold uppercase tracking-wide text-neutral-300 hover:border-neutral-500 hover:text-white transition-colors"
            >
                {{ $cancelLabel }}
            </button>
            <button
                type="button"
                data-confirm-submit
                class="rounded px-4 py-2 text-sm font-bold uppercase tracking-wide text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#1e1e1e] {{ $confirmButtonClass }}"
            >
                {{ $confirmLabel }}
            </button>
        </div>
    </div>

    <form
        id="{{ $formId ?? $id.'-form' }}"
        data-confirm-form
        method="POST"
        action="{{ $action }}"
        class="hidden"
    >
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <div data-confirm-extra-fields></div>
    </form>
</div>

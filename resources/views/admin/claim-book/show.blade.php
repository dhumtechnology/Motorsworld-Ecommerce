@extends('layouts.admin')

@section('title', $entry->code.' — Libro de reclamaciones')
@section('page-title', $entry->claim_type->label().' '.$entry->code)
@section('page-subtitle', 'Gestión y respuesta al cliente')

@section('content')
    <div class="mb-4">
        <a href="{{ route($listRoute) }}" class="text-xs font-bold uppercase tracking-wider text-muted hover:text-primary">
            ← Volver al listado
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <section class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-black uppercase tracking-wide text-text mb-4">Datos del consumidor</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Nombre</dt>
                        <dd class="text-text font-semibold">{{ $entry->consumerFullName() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Documento</dt>
                        <dd class="text-text">{{ $entry->document }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Correo</dt>
                        <dd class="text-text">{{ $entry->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Teléfono</dt>
                        <dd class="text-text">{{ $entry->phone }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Domicilio</dt>
                        <dd class="text-text">{{ $entry->address }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Cuenta asociada</dt>
                        <dd class="text-text">
                            @if ($entry->user)
                                #{{ $entry->user_id }} — {{ $entry->user->email }}
                            @else
                                Sin cuenta (registro como invitado)
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-black uppercase tracking-wide text-text mb-4">Detalle de la {{ strtolower($entry->claim_type->label()) }}</h2>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Bien contratado</dt>
                        <dd class="text-text">{{ $entry->good_type->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Descripción</dt>
                        <dd class="text-text whitespace-pre-wrap">{{ $entry->good_description }}</dd>
                    </div>
                    @if ($entry->claimed_amount !== null)
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Monto reclamado</dt>
                            <dd class="text-text font-semibold">S/ {{ number_format((float) $entry->claimed_amount, 2) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Detalle</dt>
                        <dd class="text-text whitespace-pre-wrap">{{ $entry->detail }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-muted font-bold mb-1">Pedido del consumidor</dt>
                        <dd class="text-text whitespace-pre-wrap">{{ $entry->consumer_request }}</dd>
                    </div>
                </dl>
            </section>

            @if ($entry->admin_reply)
                <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-emerald-800 mb-2">Última respuesta enviada</h2>
                    <p class="text-xs text-emerald-700 mb-3">
                        {{ $entry->replied_at?->format('d/m/Y H:i') }}
                        @if ($entry->handler)
                            · por {{ $entry->handler->email }}
                        @endif
                    </p>
                    <p class="text-sm text-emerald-950 whitespace-pre-wrap">{{ $entry->admin_reply }}</p>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            <section class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-black uppercase tracking-wide text-text mb-4">Gestionar estado</h2>
                <form method="POST" action="{{ route('admin.claim-book.update', $entry) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado</label>
                        <select id="status" name="status" required
                                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $entry->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="admin_notes" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Notas internas</label>
                        <textarea id="admin_notes" name="admin_notes" rows="4"
                                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                  placeholder="Solo visible para el equipo admin">{{ old('admin_notes', $entry->admin_notes) }}</textarea>
                    </div>
                    <button type="submit" class="w-full rounded bg-black px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-neutral-800 transition-colors">
                        Guardar gestión
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-border bg-surface p-5">
                <h2 class="text-sm font-black uppercase tracking-wide text-text mb-4">Responder al cliente</h2>
                <p class="text-xs text-muted mb-4">Se enviará un correo a <strong class="text-text">{{ $entry->email }}</strong>.</p>
                <form method="POST" action="{{ route('admin.claim-book.reply', $entry) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="admin_reply" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Mensaje</label>
                        <textarea id="admin_reply" name="admin_reply" rows="8" required
                                  class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                  placeholder="Escribe la respuesta oficial al cliente...">{{ old('admin_reply') }}</textarea>
                    </div>
                    <div>
                        <label for="reply_status" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado al responder</label>
                        <select id="reply_status" name="status"
                                class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="in_progress" @selected(old('status', 'in_progress') === 'in_progress')>En atención</option>
                            <option value="resolved" @selected(old('status') === 'resolved')>Resuelto</option>
                            <option value="closed" @selected(old('status') === 'closed')>Cerrado</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                        Enviar respuesta por correo
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection

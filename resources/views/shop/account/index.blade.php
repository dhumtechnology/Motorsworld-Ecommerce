@extends('layouts.shop')

@section('title', 'Mi cuenta — '.config('app.name'))

@section('content')
    @php
        $orderStatusLabels = [
            'created' => 'Creada',
            'paid' => 'Pagada',
            'processing' => 'En proceso',
            'shipped' => 'Enviada',
            'delivered' => 'Entregada',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
        ];
        $paymentStatusLabels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            'partially_refunded' => 'Reembolso parcial',
        ];
        $appointmentStatusLabels = [
            'pending' => 'Pendiente',
            'in_progress' => 'En proceso',
            'attended' => 'Atendida',
            'absent' => 'Ausente',
            'cancelled' => 'Cancelada',
        ];
    @endphp

    <div class="min-h-[70vh] px-4 py-12 max-w-6xl mx-auto text-black font-title">
        <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-black uppercase tracking-wide">Mi cuenta</h1>
                <p class="text-sm text-neutral-500 mt-1">
                    {{ $profile?->first_name }} {{ $profile?->last_name }} · {{ $user->email }}
                </p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs font-bold uppercase tracking-wider text-orange-600 hover:text-orange-500">
                    Cerrar sesión
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
            <section class="rounded-md border border-neutral-200 bg-white p-6">
                <h2 class="text-lg font-black uppercase tracking-wide mb-4">Mis datos</h2>

                <form action="{{ route('shop.account.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Nombres</label>
                            <input id="first_name" name="first_name" type="text" required
                                   value="{{ old('first_name', $profile?->first_name) }}"
                                   class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                        </div>
                        <div>
                            <label for="last_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Apellidos</label>
                            <input id="last_name" name="last_name" type="text" required
                                   value="{{ old('last_name', $profile?->last_name) }}"
                                   class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                        </div>
                    </div>

                    <div>
                        <label for="document" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Documento</label>
                        <input id="document" name="document" type="text" required
                               value="{{ old('document', $profile?->document) }}"
                               class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Correo</label>
                        <input id="email" name="email" type="email" required
                               value="{{ old('email', $user->email) }}"
                               class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Teléfono</label>
                        <input id="phone" name="phone" type="tel"
                               value="{{ old('phone', $profile?->phone) }}"
                               class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-orange-600 text-white text-xs font-extrabold uppercase tracking-widest rounded hover:bg-orange-700">
                        Guardar datos
                    </button>
                </form>
            </section>

            <section class="rounded-md border border-neutral-200 bg-white p-6">
                <h2 class="text-lg font-black uppercase tracking-wide mb-4">Cambiar contraseña</h2>

                <form action="{{ route('shop.account.password.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Contraseña actual</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                               class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Nueva contraseña</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Confirmar nueva contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               class="w-full px-3 py-2.5 rounded border border-neutral-300 bg-white text-sm focus:outline-none focus:border-orange-600">
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-black text-white text-xs font-extrabold uppercase tracking-widest rounded hover:bg-neutral-800">
                        Actualizar contraseña
                    </button>
                </form>
            </section>
        </div>

        <section class="rounded-md border border-neutral-200 bg-white p-6 mb-10">
            <h2 class="text-lg font-black uppercase tracking-wide mb-4">Historial de compras</h2>

            @if ($orders->isEmpty())
                <p class="text-sm text-neutral-500">Aún no tienes pedidos.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 text-left text-xs uppercase tracking-wider text-neutral-500">
                                <th class="py-3 pr-4">Pedido</th>
                                <th class="py-3 pr-4">Fecha</th>
                                <th class="py-3 pr-4">Estado</th>
                                <th class="py-3 pr-4">Pago</th>
                                <th class="py-3 pr-4">Total</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-3 pr-4 font-semibold">#{{ $order->id }}</td>
                                    <td class="py-3 pr-4">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 pr-4">
                                        {{ $orderStatusLabels[$order->status?->value ?? ''] ?? ($order->status?->value ?? '—') }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $paymentStatusLabels[$order->payment_status?->value ?? ''] ?? ($order->payment_status?->value ?? '—') }}
                                    </td>
                                    <td class="py-3 pr-4 font-semibold">S/ {{ number_format((float) $order->total_amount, 2) }}</td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('shop.checkout.orders.show', $order) }}" class="text-orange-600 hover:text-orange-500 font-bold text-xs uppercase tracking-wider">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $orders->links() }}</div>
            @endif
        </section>

        <section class="rounded-md border border-neutral-200 bg-white p-6">
            <h2 class="text-lg font-black uppercase tracking-wide mb-4">Historial de reservas</h2>

            @if ($appointments->isEmpty())
                <p class="text-sm text-neutral-500">Aún no tienes reservas de taller.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 text-left text-xs uppercase tracking-wider text-neutral-500">
                                <th class="py-3 pr-4">Fecha</th>
                                <th class="py-3 pr-4">Servicio</th>
                                <th class="py-3 pr-4">Vehículo</th>
                                <th class="py-3 pr-4">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $appointment)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-3 pr-4 font-semibold">
                                        {{ $appointment->appointment_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $appointment->serviceType?->name ?? '—' }}
                                        @if ($appointment->servicePackage)
                                            <span class="block text-xs text-neutral-500">{{ $appointment->servicePackage->name }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $appointment->brand?->name }} {{ $appointment->vehicleModel?->name }}
                                        @if ($appointment->plate)
                                            <span class="block text-xs text-neutral-500">{{ $appointment->plate }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $appointmentStatusLabels[$appointment->status?->value ?? ''] ?? ($appointment->status?->value ?? '—') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $appointments->links() }}</div>
            @endif
        </section>
    </div>
@endsection

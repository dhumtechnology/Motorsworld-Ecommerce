@php
    $navGroups = [
        [
            'label' => 'Gestión de productos',
            'items' => [
                [
                    'label' => 'Productos',
                    'route' => 'admin.products.index',
                    'active' => request()->routeIs('admin.products.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Categorías',
                    'route' => 'admin.categories.index',
                    'active' => request()->routeIs('admin.categories.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Marcas',
                    'route' => 'admin.brands.index',
                    'active' => request()->routeIs('admin.brands.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Modelos',
                    'route' => 'admin.models.index',
                    'active' => request()->routeIs('admin.models.*'),
                    'enabled' => true,
                ],
            ],
        ],
        [
            'label' => 'Gestión de usuarios',
            'items' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'admin.users.index',
                    'active' => request()->routeIs('admin.users.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Clientes',
                    'route' => 'admin.customers.index',
                    'active' => request()->routeIs('admin.customers.*'),
                    'enabled' => true,
                ],
            ],
        ],
        [
            'label' => 'Gestión de compras',
            'items' => [
                [
                    'label' => 'Órdenes',
                    'route' => 'admin.orders.index',
                    'active' => request()->routeIs('admin.orders.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Pagos',
                    'route' => 'admin.payments.index',
                    'active' => request()->routeIs('admin.payments.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Medios de pago',
                    'route' => 'admin.payment-methods.index',
                    'active' => request()->routeIs('admin.payment-methods.*'),
                    'enabled' => true,
                ],
            ],
        ],
        [
            'label' => 'Gestión de reservas',
            'items' => [
                [
                    'label' => 'Reservas',
                    'route' => 'admin.appointments.index',
                    'active' => request()->routeIs('admin.appointments.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Servicios',
                    'route' => 'admin.service-types.index',
                    'active' => request()->routeIs('admin.service-types.*'),
                    'enabled' => true,
                ],
            ],
        ],
        [
            'label' => 'Gestión de inventario',
            'items' => [
                [
                    'label' => 'Inventario',
                    'route' => 'admin.inventory.index',
                    'active' => request()->routeIs('admin.inventory.*'),
                    'enabled' => true,
                ],
            ],
        ],
    ];
@endphp

<aside class="w-64 shrink-0 bg-[#1e1e1e] border-r border-neutral-800 flex flex-col">
    <div class="p-5 border-b border-neutral-800">
        <x-logo href="{{ route('admin.dashboard') }}" />
        <p class="text-[10px] uppercase tracking-widest text-neutral-500 font-bold mt-3">
            Panel administrativo
        </p>
    </div>

    <nav class="flex-1 p-4 space-y-5 overflow-y-auto">
        <a
            href="{{ route('admin.dashboard') }}"
            class="block px-4 py-2.5 rounded text-sm font-bold uppercase tracking-wide transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-orange-600 text-white' : 'text-neutral-400 hover:text-white hover:bg-[#252525]' }}"
        >
            Dashboard
        </a>

        @foreach ($navGroups as $group)
            <div>
                <p class="px-4 mb-1.5 text-[10px] font-bold uppercase tracking-widest text-neutral-600">
                    {{ $group['label'] }}
                </p>

                <div class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @if ($item['enabled'] && $item['route'])
                            <a
                                href="{{ route($item['route']) }}"
                                class="block px-4 py-2.5 rounded text-sm font-semibold tracking-wide transition-colors {{ $item['active'] ? 'bg-orange-600 text-white' : 'text-neutral-400 hover:text-white hover:bg-[#252525]' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span class="block px-4 py-2.5 rounded text-sm font-semibold tracking-wide text-neutral-600 cursor-not-allowed select-none">
                                {{ $item['label'] }}
                                <span class="ml-1 text-[10px] font-normal text-neutral-700">Pronto</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="p-4 border-t border-neutral-800 text-xs text-neutral-500">
        Motosworld Admin
    </div>
</aside>

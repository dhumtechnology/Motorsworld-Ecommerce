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

<aside class="w-64 shrink-0 bg-sidebar border-r border-sidebar-border flex flex-col min-h-screen sticky top-0 h-screen">
    <div class="p-5 border-b border-sidebar-border">
        <x-logo href="{{ route('admin.dashboard') }}" />
        <p class="text-[10px] uppercase tracking-widest text-sidebar-muted font-bold mt-3 font-secondary">
            Panel administrativo
        </p>
    </div>

    <nav class="flex-1 p-3 space-y-5 overflow-y-auto">
        <a
            href="{{ route('admin.dashboard') }}"
            class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
        >
            Dashboard
        </a>

        @foreach ($navGroups as $group)
            <div>
                <p class="admin-nav-group">
                    {{ $group['label'] }}
                </p>

                <div class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @if ($item['enabled'] && $item['route'])
                            <a
                                href="{{ route($item['route']) }}"
                                class="admin-nav-link {{ $item['active'] ? 'is-active' : '' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @else
                            <span class="block px-4 py-2.5 rounded-lg text-sm font-semibold tracking-wide text-sidebar-muted cursor-not-allowed select-none">
                                {{ $item['label'] }}
                                <span class="ml-1 text-[10px] font-normal opacity-70">Pronto</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="p-4 border-t border-sidebar-border text-xs text-sidebar-muted font-secondary">
        Motosworld Admin
    </div>
</aside>

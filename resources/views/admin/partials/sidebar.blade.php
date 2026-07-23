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
            'label' => 'Gestión de clientes',
            'items' => [
                [
                    'label' => 'Clientes',
                    'route' => 'admin.customers.index',
                    'active' => request()->routeIs('admin.customers.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Órdenes',
                    'route' => 'admin.orders.index',
                    'active' => request()->routeIs('admin.orders.*'),
                    'enabled' => true,
                ],
                [
                    'label' => 'Reservas',
                    'route' => null,
                    'active' => false,
                    'enabled' => false,
                ],
            ],
        ],
        [
            'label' => 'Gestión de compras',
            'items' => [
                [
                    'label' => 'Pagos',
                    'route' => null,
                    'active' => false,
                    'enabled' => false,
                ],
                [
                    'label' => 'Medios de pago',
                    'route' => null,
                    'active' => false,
                    'enabled' => false,
                ],
            ],
        ],        [
            'label' => 'Gestión de inventario',
            'items' => [
                [
                    'label' => 'Entradas',
                    'route' => null,
                    'active' => false,
                    'enabled' => false,
                ],
                [
                    'label' => 'Salidas',
                    'route' => null,
                    'active' => false,
                    'enabled' => false,
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

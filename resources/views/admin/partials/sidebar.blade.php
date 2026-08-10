@php
    $user = auth()->user();

    $navGroups = [
        [
            'label' => 'Gestión de productos',
            'items' => [
                [
                    'label' => 'Productos',
                    'route' => 'admin.products.index',
                    'active' => request()->routeIs('admin.products.*'),
                    'permission' => 'products.view',
                ],
                [
                    'label' => 'Ofertas',
                    'route' => 'admin.offers.index',
                    'active' => request()->routeIs('admin.offers.*'),
                    'permission' => 'product_offers.view',
                ],
                [
                    'label' => 'Categorías',
                    'route' => 'admin.categories.index',
                    'active' => request()->routeIs('admin.categories.*'),
                    'permission' => 'categories.view',
                ],
                [
                    'label' => 'Marcas',
                    'route' => 'admin.brands.index',
                    'active' => request()->routeIs('admin.brands.*'),
                    'permission' => 'brands.view',
                ],
                [
                    'label' => 'Modelos',
                    'route' => 'admin.models.index',
                    'active' => request()->routeIs('admin.models.*'),
                    'permission' => 'vehicle_models.view',
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
                    'permission' => 'users.view',
                ],
                [
                    'label' => 'Roles',
                    'route' => 'admin.roles.index',
                    'active' => request()->routeIs('admin.roles.*'),
                    'permission' => 'roles.view',
                ],
                [
                    'label' => 'Permisos',
                    'route' => 'admin.permissions.index',
                    'active' => request()->routeIs('admin.permissions.*'),
                    'permission' => 'permissions.view',
                ],
                [
                    'label' => 'Clientes',
                    'route' => 'admin.customers.index',
                    'active' => request()->routeIs('admin.customers.*'),
                    'permission' => 'customer_profiles.view',
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
                    'permission' => 'orders.view',
                ],
                [
                    'label' => 'Pagos',
                    'route' => 'admin.payments.index',
                    'active' => request()->routeIs('admin.payments.*'),
                    'permission' => 'payments.view',
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
                    'permission' => 'appointments.view',
                ],
                [
                    'label' => 'Servicios',
                    'route' => 'admin.service-types.index',
                    'active' => request()->routeIs('admin.service-types.*'),
                    'permission' => 'service_types.view',
                ],
                [
                    'label' => 'Paquetes de servicio',
                    'route' => 'admin.service-packages.index',
                    'active' => request()->routeIs('admin.service-packages.*'),
                    'permission' => 'service_packages.view',
                ],
            ],
        ],
        [
            'label' => 'Gestión de contenido',
            'items' => [
                [
                    'label' => 'Blog',
                    'route' => 'admin.blog-posts.index',
                    'active' => request()->routeIs('admin.blog-posts.*'),
                    'permission' => 'blog_posts.view',
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
                    'permission' => 'inventory_movements.view',
                ],
            ],
        ],
    ];

    $navGroups = collect($navGroups)
        ->map(function (array $group) use ($user) {
            $items = collect($group['items'])
                ->filter(fn (array $item) => $user?->hasPermission($item['permission']) ?? false)
                ->values()
                ->all();

            $group['items'] = $items;

            return $group;
        })
        ->filter(fn (array $group) => $group['items'] !== [])
        ->values()
        ->all();
@endphp

<aside class="w-64 shrink-0 bg-black border-r border-sidebar-border flex flex-col min-h-screen sticky top-0 h-screen">
    <div class="p-5 border-b border-sidebar-border">
        <x-logo href="{{ route('admin.dashboard') }}" />
        <p class="text-[10px] uppercase tracking-widest text-sidebar-muted font-bold mt-3 font-secondary">
            Panel administrativo
        </p>
    </div>

    <nav class="admin-sidebar-nav flex-1 p-3 space-y-5">
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
                        <a
                            href="{{ route($item['route']) }}"
                            class="admin-nav-link {{ $item['active'] ? 'is-active' : '' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="p-4 border-t border-sidebar-border space-y-2">
        <a
            href="{{ route('admin.profile.show') }}"
            class="admin-nav-link {{ request()->routeIs('admin.profile.*') ? 'is-active' : '' }}"
        >
            Mi perfil
        </a>
        <p class="px-4 text-[10px] uppercase tracking-widest text-sidebar-muted font-secondary truncate" title="{{ auth()->user()?->email }}">
            {{ auth()->user()?->email }}
        </p>
    </div>
</aside>

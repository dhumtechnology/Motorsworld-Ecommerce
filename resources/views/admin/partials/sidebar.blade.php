@php
    $user = auth()->user();
    $pendingCounts = $sidebarPendingCounts ?? [
        'appointments' => 0,
        'complaints' => 0,
        'claims' => 0,
        'contacts' => 0,
        'trashed_products' => 0,
    ];

    $navGroups = [
        [
            'label' => 'Gestión de productos',
            'items' => [
                [
                    'label' => 'Productos',
                    'route' => 'admin.products.index',
                    'active' => request()->routeIs('admin.products.index', 'admin.products.show', 'admin.products.create', 'admin.products.edit'),
                    'permission' => 'products.view',
                ],
                [
                    'label' => 'Papelera',
                    'route' => 'admin.products.trash',
                    'active' => request()->routeIs('admin.products.trash'),
                    'permission' => 'products.view',
                    'badge' => (int) ($pendingCounts['trashed_products'] ?? 0),
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
                    'route_params' => ['status' => 'pending'],
                    'active' => request()->routeIs('admin.appointments.*'),
                    'permission' => 'appointments.view',
                    'badge' => (int) ($pendingCounts['appointments'] ?? 0),
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
                    'label' => 'Configuración',
                    'route' => 'admin.home-banners.index',
                    'active' => request()->routeIs('admin.home-banners.*'),
                    'permission' => 'home_banners.view',
                ],
                [
                    'label' => 'Blog',
                    'route' => 'admin.blog-posts.index',
                    'active' => request()->routeIs('admin.blog-posts.*'),
                    'permission' => 'blog_posts.view',
                ],
            ],
        ],
        [
            'label' => 'Incidencias',
            'items' => [
                [
                    'label' => 'Quejas',
                    'route' => 'admin.claim-book.complaints',
                    'route_params' => ['status' => 'pending'],
                    'active' => request()->routeIs('admin.claim-book.complaints')
                        || (request()->routeIs('admin.claim-book.show') && optional(request()->route('claimBookEntry'))->claim_type?->value === 'complaint'),
                    'permission' => 'claim_book_entries.view',
                    'badge' => (int) ($pendingCounts['complaints'] ?? 0),
                ],
                [
                    'label' => 'Reclamos',
                    'route' => 'admin.claim-book.claims',
                    'route_params' => ['status' => 'pending'],
                    'active' => request()->routeIs('admin.claim-book.claims')
                        || (request()->routeIs('admin.claim-book.show') && optional(request()->route('claimBookEntry'))->claim_type?->value === 'claim'),
                    'permission' => 'claim_book_entries.view',
                    'badge' => (int) ($pendingCounts['claims'] ?? 0),
                ],
                [
                    'label' => 'Contactos',
                    'route' => 'admin.contacts.index',
                    'route_params' => ['status' => 'pending'],
                    'active' => request()->routeIs('admin.contacts.*'),
                    'permission' => 'contact_messages.view',
                    'badge' => (int) ($pendingCounts['contacts'] ?? 0),
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
                        @php $badge = (int) ($item['badge'] ?? 0); @endphp
                        <a
                            href="{{ route($item['route'], $item['route_params'] ?? []) }}"
                            class="admin-nav-link {{ $item['active'] ? 'is-active' : '' }}"
                        >
                            <span>{{ $item['label'] }}</span>
                            @if ($badge > 0)
                                <span class="admin-nav-badge" title="{{ $badge }} pendiente{{ $badge === 1 ? '' : 's' }}">
                                    {{ $badge > 99 ? '99+' : $badge }}
                                </span>
                            @endif
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

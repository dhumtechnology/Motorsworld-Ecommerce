@php
    $navItems = [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => request()->routeIs('admin.dashboard'),
            'enabled' => true,
        ],
        [
            'label' => 'Productos',
            'route' => 'admin.products.index',
            'active' => request()->routeIs('admin.products.*'),
            'enabled' => true,
        ],
        [
            'label' => 'Pedidos',
            'route' => null,
            'active' => false,
            'enabled' => false,
        ],
        [
            'label' => 'Usuarios',
            'route' => null,
            'active' => false,
            'enabled' => false,
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

    <nav class="flex-1 p-4 space-y-1">
        @foreach ($navItems as $item)
            @if ($item['enabled'] && $item['route'])
                <a
                    href="{{ route($item['route']) }}"
                    class="block px-4 py-3 rounded text-sm font-bold uppercase tracking-wide transition-colors {{ $item['active'] ? 'bg-orange-600 text-white' : 'text-neutral-400 hover:text-white hover:bg-[#252525]' }}"
                >
                    {{ $item['label'] }}
                </a>
            @else
                <span class="block px-4 py-3 rounded text-sm font-bold uppercase tracking-wide text-neutral-600 cursor-not-allowed select-none">
                    {{ $item['label'] }}
                    <span class="block text-[10px] font-normal normal-case tracking-normal text-neutral-700 mt-0.5">Próximamente</span>
                </span>
            @endif
        @endforeach
    </nav>

    <div class="p-4 border-t border-neutral-800 text-xs text-neutral-500">
        Motosworld Admin
    </div>
</aside>

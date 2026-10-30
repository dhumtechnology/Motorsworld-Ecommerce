@extends('layouts.admin')

@section('title', 'Banners de páginas — Admin')
@section('page-title', 'Banners de páginas')
@section('page-subtitle', 'Imágenes de cabecera de las páginas estáticas')

@section('content')
    <div class="mb-6 rounded-lg border border-border bg-surface px-5 py-4">
        <p class="text-sm text-muted">
            Cada página de la tienda (excepto el home) usa una imagen fija. El slider del home se gestiona en
            <a href="{{ route('admin.home-banners.index') }}" class="font-semibold text-text hover:text-primary">Configuración</a>.
            Si no subes una imagen, se muestra la portada por defecto.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($pages as $page)
            <article class="overflow-hidden rounded-lg border border-border bg-surface">
                <div class="relative aspect-[21/9] bg-secondary">
                    <img src="{{ $page['imageUrl'] }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                    <span class="absolute left-3 top-3 rounded px-2 py-1 text-[10px] font-bold uppercase tracking-wider {{ $page['isCustom'] ? 'bg-primary text-white' : 'bg-surface/90 text-muted' }}">
                        {{ $page['isCustom'] ? 'Personalizado' : 'Por defecto' }}
                    </span>
                    <h2 class="absolute inset-x-3 bottom-3 text-sm font-bold uppercase tracking-wide text-white">
                        {{ $page['key']->label() }}
                    </h2>
                </div>
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    @if ($page['shopUrl'])
                        <a href="{{ $page['shopUrl'] }}" target="_blank" rel="noopener" class="text-xs font-bold uppercase tracking-wide text-muted hover:text-text">
                            Ver página
                        </a>
                    @else
                        <span></span>
                    @endif
                    <a href="{{ route('admin.page-banners.edit', $page['key']->value) }}"
                       class="rounded bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
                        Cambiar imagen
                    </a>
                </div>
            </article>
        @endforeach
    </div>
@endsection

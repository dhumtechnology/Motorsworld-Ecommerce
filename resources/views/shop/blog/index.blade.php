{{--
    Blog — listado

    Banner opcional: public/images/blog/banner-blog.png
--}}
@extends('layouts.shop')

@section('title', 'Blog — '.config('app.name'))

@section('content')
@php
    $banner = file_exists(public_path('images/blog/banner-blog.png'))
        ? asset('images/blog/banner-blog.png')
        : asset('images/services/banner-servicios.png');
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[420px] w-full">
        <img
            src="{{ $banner }}"
            alt="Blog Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Motoworld</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Blog
            </h1>
            <p class="mt-2 max-w-xl text-sm md:text-base text-white/85">
                Noticias, tips y novedades del mundo motociclista.
            </p>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-12 md:py-16">
        @if ($posts->isEmpty())
            <p class="text-center text-sm text-neutral-500 py-16">
                Pronto publicaremos artículos aquí.
            </p>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($posts as $post)
                    <a
                        href="{{ route('shop.blog.show', $post->slug) }}"
                        class="group overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition hover:shadow-md"
                    >
                        <div class="aspect-[16/10] overflow-hidden bg-neutral-100">
                            @if ($post->image)
                                <img
                                    src="{{ $post->image }}"
                                    alt="{{ $post->title }}"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-neutral-200 text-neutral-400">
                                    <span class="text-xs font-bold uppercase tracking-wider">Sin imagen</span>
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <h2 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title leading-snug group-hover:text-orange-600 transition-colors">
                                {{ $post->title }}
                            </h2>
                            @if ($post->published_at)
                                <p class="mt-2 text-xs text-neutral-400">
                                    {{ $post->published_at->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div class="mt-10">
                    {{ $posts->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        @endif
    </div>
</section>
@endsection

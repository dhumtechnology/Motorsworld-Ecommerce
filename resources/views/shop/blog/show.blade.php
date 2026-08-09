@extends('layouts.shop')

@section('title', $post->title.' — Blog — '.config('app.name'))

@section('content')
<article class="bg-white">
    <div class="mx-auto max-w-3xl px-4 md:px-8 py-10 md:py-14">
        <a href="{{ route('shop.blog.index') }}" class="inline-flex text-sm font-bold text-orange-600 hover:text-orange-500 uppercase tracking-wider">
            ← Volver al blog
        </a>

        <header class="mt-6 mb-8">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">Blog Motoworld</p>
            <h1 class="mt-2 text-2xl md:text-4xl font-black uppercase tracking-wide text-neutral-900 font-title leading-tight">
                {{ $post->title }}
            </h1>
            @if ($post->published_at)
                <p class="mt-3 text-sm text-neutral-500">
                    {{ $post->published_at->translatedFormat('d \d\e F \d\e Y') }}
                </p>
            @endif
        </header>

        @if ($post->image)
            <div class="mb-8 overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100">
                <img
                    src="{{ $post->image }}"
                    alt="{{ $post->title }}"
                    class="w-full h-auto object-cover max-h-[480px]"
                >
            </div>
        @endif

        <div class="blog-content prose prose-neutral max-w-none text-neutral-700 leading-relaxed text-sm md:text-base">
            {!! $post->body !!}
        </div>

        <div class="mt-12 pt-8 border-t border-neutral-200">
            <a
                href="{{ route('shop.blog.index') }}"
                class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors"
            >
                Ver más publicaciones
            </a>
        </div>
    </div>
</article>

<style>
    .blog-content h1, .blog-content h2, .blog-content h3 {
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #171717;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .blog-content h1 { font-size: 1.5rem; }
    .blog-content h2 { font-size: 1.25rem; }
    .blog-content h3 { font-size: 1.1rem; }
    .blog-content p { margin-bottom: 1rem; }
    .blog-content ul, .blog-content ol { margin: 0 0 1rem 1.25rem; }
    .blog-content li { margin-bottom: 0.35rem; }
    .blog-content a { color: #ea580c; font-weight: 600; text-decoration: underline; }
    .blog-content blockquote {
        border-left: 3px solid #ea580c;
        padding-left: 1rem;
        margin: 1rem 0;
        color: #525252;
        font-style: italic;
    }
    .blog-content img { max-width: 100%; height: auto; border-radius: 0.75rem; }
</style>
@endsection

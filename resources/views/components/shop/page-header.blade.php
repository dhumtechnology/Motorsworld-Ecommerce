@props([
    'page',
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'alt' => null,
    'overlay' => 'from-black/75 via-black/30 to-black/20',
])

@php
    $key = $page instanceof \App\Enums\Content\PageBannerKey
        ? $page
        : \App\Enums\Content\PageBannerKey::from($page);
    $imageUrl = app(\App\Services\Content\PageBannerResolver::class)->imageUrl($key);
    $imageAlt = $alt ?? $title;
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="shop-page-banner">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 h-full w-full object-cover"
            loading="eager"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t {{ $overlay }}"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            @if (filled($eyebrow))
                <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">{{ $eyebrow }}</p>
            @endif
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                {{ $title }}
            </h1>
            @if (filled($subtitle))
                <p class="mt-2 max-w-2xl text-sm md:text-base text-white/85">
                    {{ $subtitle }}
                </p>
            @endif
            {{ $slot }}
        </div>
    </div>
</section>

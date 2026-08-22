@props(['popularProducts' => collect()])

<section class="w-full bg-white border-t border-neutral-100 overflow-hidden">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
        <h2 class="mb-8 md:mb-10 text-center text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Productos populares
        </h2>

        @if ($popularProducts->isEmpty())
            <p class="text-center text-sm text-neutral-500">Pronto verás aquí los productos más vendidos.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4 w-full">
                @foreach ($popularProducts as $product)
                    @php
                        $brand = $product->vehicleModel?->brand?->name ?? $product->category?->name ?? 'Motoworld';
                        $description = \Illuminate\Support\Str::limit(
                            trim((string) ($product->description ?: $product->name)),
                            90
                        );
                        $price = (float) ($product->effective_price ?? $product->price_amount);
                        $image = $product->image ?: asset('images/home/product-placeholder.png');
                    @endphp

                    <a
                        href="{{ route('shop.product.show', $product) }}"
                        class="group flex w-full flex-col overflow-hidden border border-neutral-200 bg-white transition-shadow hover:shadow-md"
                    >
                        <div class="relative aspect-square overflow-hidden bg-neutral-100">
                            <img
                                src="{{ $image }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            >
                        </div>

                        <div class="flex min-h-[4.5rem] border-t border-neutral-200 mt-auto">
                            <div class="flex flex-1 flex-col justify-center min-w-0 p-2 md:p-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500 truncate">
                                    {{ $brand }}
                                </p>
                                <p class="text-[11px] md:text-xs font-semibold text-neutral-900 leading-tight line-clamp-2 mt-0.5">
                                    {{ $description }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center justify-center bg-primary px-2 sm:px-3 text-center">
                                <span class="text-xs sm:text-sm md:text-base font-black text-white tracking-tight whitespace-nowrap">
                                    S/ {{ number_format($price, 2) }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
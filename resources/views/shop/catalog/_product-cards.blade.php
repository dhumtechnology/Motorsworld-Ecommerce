{{-- Grid de cards de productos del catálogo --}}
@forelse ($products as $product)
    <x-card
        :title="$product->name ?? $product->sku"
        :category="$product->category?->name ?? 'Producto'"
        :price="$product->effective_price"
        :oldPrice="$product->is_on_sale ? $product->list_price : null"
        :image="$product->image ?? 'https://via.placeholder.com/300?text=MotoWorld'"
        :isSale="$product->is_on_sale"
        :discountPercent="$product->discount_percent"
        :currency="$product->currency ?? 'PEN'"
        :href="route('shop.product.show', $product)"
        :cartQty="$cartQuantities[$product->id] ?? 0"
    />
@empty
    @if (($products->currentPage() ?? 1) <= 1)
        <div class="col-span-1 md:col-span-2 lg:col-span-4 text-center py-2 text-gray-400">
            <p class="mt-2 text-sm">No se encontraron productos disponibles en este momento.</p>
        </div>
    @endif
@endforelse

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Models\Products\Category;
use App\Models\Products\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function index(ProductIndexRequest $request): View
    {
        $products = Product::query()
            ->with(['category', 'inventory', 'primaryImage'])
            ->when(
                $request->categoryId(),
                fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId),
            )
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('sku', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', $like));
                    });
                },
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->searchTerm(),
                'category_id' => $request->categoryId(),
            ],
        ]);
    }
}

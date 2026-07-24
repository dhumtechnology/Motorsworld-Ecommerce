<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Categories\DeleteCategoriesAction;
use App\Actions\Admin\Categories\UpsertCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteCategoriesRequest;
use App\Http\Requests\Admin\CategoryIndexRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Products\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertCategoryAction $upsertCategory,
        private readonly DeleteCategoriesAction $deleteCategories,
    ) {}

    public function index(CategoryIndexRequest $request): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->when(
                $request->searchTerm(),
                function (Builder $query, string $search) {
                    $like = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($like) {
                        $searchQuery
                            ->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
                },
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'filters' => [
                'search' => $request->searchTerm(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $category = $this->upsertCategory->execute($request->categoryAttributes());

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
            ]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('status', "Categoría «{$category->name}» creada correctamente.");
    }

    public function edit(Category $category): View
    {
        $category->loadCount('products');

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category = $this->upsertCategory->execute($request->categoryAttributes(), $category);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', "Categoría «{$category->name}» actualizada correctamente.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $result = $this->deleteCategories->execute([$category->id]);

        $message = $result['deleted'] === 1
            ? 'Categoría eliminada correctamente.'
            : 'No se pudo eliminar la categoría.';

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (productos en pedidos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteCategoriesRequest $request): RedirectResponse
    {
        $result = $this->deleteCategories->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ninguna categoría.',
            $result['deleted'] === 1 => '1 categoría eliminada correctamente.',
            default => "{$result['deleted']} categorías eliminadas correctamente.",
        };

        if ($result['blocked'] !== []) {
            $message .= ' No se eliminaron (productos en pedidos): '.implode(', ', $result['blocked']).'.';
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('status', $message);
    }
}

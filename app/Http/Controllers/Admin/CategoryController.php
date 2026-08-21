<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Categories\DeleteCategoriesAction;
use App\Actions\Admin\Categories\GetCategoryDetailsAction;
use App\Actions\Admin\Categories\ReorderCategoriesAction;
use App\Actions\Admin\Categories\UpsertCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteCategoriesRequest;
use App\Http\Requests\Admin\CategoryIndexRequest;
use App\Http\Requests\Admin\ReorderCategoriesRequest;
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
        private readonly GetCategoryDetailsAction $getCategoryDetails,
        private readonly ReorderCategoriesAction $reorderCategories,
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
            ->orderBy('sort_order')
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
        $category = $this->upsertCategory->execute(
            $request->categoryAttributes(),
            null,
            $request->imageFile(),
        );

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

    public function show(Category $category): View
    {
        return view('admin.categories.show', $this->getCategoryDetails->execute($category));
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
        $category = $this->upsertCategory->execute(
            $request->categoryAttributes(),
            $category,
            $request->imageFile(),
            $request->shouldRemoveImage(),
        );

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

        return redirect()
            ->route('admin.categories.index')
            ->with('status', $message);
    }

    public function reorder(): View
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'image', 'sort_order']);

        return view('admin.categories.reorder', [
            'categories' => $categories,
        ]);
    }

    public function updateOrder(ReorderCategoriesRequest $request): JsonResponse|RedirectResponse
    {
        $this->reorderCategories->execute($request->ids());

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()
            ->route('admin.categories.reorder')
            ->with('status', 'Orden de categorías actualizado correctamente.');
    }
}

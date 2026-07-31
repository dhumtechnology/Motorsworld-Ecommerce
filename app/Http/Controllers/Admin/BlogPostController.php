<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BlogPosts\DeleteBlogPostsAction;
use App\Actions\Admin\BlogPosts\UpsertBlogPostAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogPostIndexRequest;
use App\Http\Requests\Admin\BulkDeleteBlogPostsRequest;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Models\Content\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class BlogPostController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertBlogPostAction $upsertBlogPost,
        private readonly DeleteBlogPostsAction $deleteBlogPosts,
    ) {}

    public function index(BlogPostIndexRequest $request): View
    {
        $posts = BlogPost::query()
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where('title', 'like', '%'.$search.'%'),
            )
            ->when(
                $request->isPublishedFilter() !== null,
                fn (Builder $query) => $query->where('is_published', $request->isPublishedFilter()),
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.blog-posts.index', [
            'posts' => $posts,
            'filters' => [
                'search' => $request->searchTerm(),
                'is_published' => $request->input('is_published'),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog-posts.create');
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $post = $this->upsertBlogPost->execute(
            $request->blogPostAttributes(),
            null,
            $request->imageFile(),
        );

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', "Publicación «{$post->title}» creada correctamente.");
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('admin.blog-posts.edit', [
            'blogPost' => $blogPost,
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $post = $this->upsertBlogPost->execute(
            $request->blogPostAttributes(),
            $blogPost,
            $request->imageFile(),
            $request->shouldRemoveImage(),
        );

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', "Publicación «{$post->title}» actualizada correctamente.");
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $this->deleteBlogPosts->execute([$blogPost->id]);

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', 'Publicación eliminada correctamente.');
    }

    public function bulkDestroy(BulkDeleteBlogPostsRequest $request): RedirectResponse
    {
        $result = $this->deleteBlogPosts->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ninguna publicación.',
            $result['deleted'] === 1 => '1 publicación eliminada correctamente.',
            default => "{$result['deleted']} publicaciones eliminadas correctamente.",
        };

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', $message);
    }
}

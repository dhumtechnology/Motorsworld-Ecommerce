<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Content\BlogPost;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends Controller
{
    private const PER_PAGE = 12;

    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->paginate(self::PER_PAGE);

        return view('shop.blog.index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($post === null) {
            throw new NotFoundHttpException;
        }

        return view('shop.blog.show', [
            'post' => $post,
        ]);
    }
}

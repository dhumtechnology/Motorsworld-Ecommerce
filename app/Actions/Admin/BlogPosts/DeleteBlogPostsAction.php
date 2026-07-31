<?php

namespace App\Actions\Admin\BlogPosts;

use App\Models\Content\BlogPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteBlogPostsAction
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: int}
     */
    public function execute(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return ['deleted' => 0];
        }

        return DB::transaction(function () use ($ids) {
            $posts = BlogPost::query()->whereIn('id', $ids)->get();

            foreach ($posts as $post) {
                if ($post->image) {
                    $this->deleteStoredFile($post->image);
                }
            }

            $deleted = BlogPost::query()->whereIn('id', $ids)->delete();

            return ['deleted' => $deleted];
        });
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '' || str_contains($path, '://')) {
            return;
        }

        $relative = str_starts_with($path, '/storage/')
            ? substr($path, strlen('/storage/'))
            : (str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : null);

        if ($relative === null) {
            return;
        }

        Storage::disk('public')->delete($relative);
    }
}

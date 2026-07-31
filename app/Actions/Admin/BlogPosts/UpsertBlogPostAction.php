<?php

namespace App\Actions\Admin\BlogPosts;

use App\Models\Content\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertBlogPostAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        array $attributes,
        ?BlogPost $blogPost = null,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): BlogPost {
        return DB::transaction(function () use ($attributes, $blogPost, $image, $removeImage) {
            if ($blogPost === null) {
                $attributes['slug'] = BlogPost::uniqueSlugFromTitle((string) $attributes['title']);
                $blogPost = BlogPost::query()->create($attributes);
            } else {
                unset($attributes['slug']);
                $blogPost->update($attributes);
            }

            if ($removeImage && $blogPost->image) {
                $this->deleteStoredFile($blogPost->image);
                $blogPost->forceFill(['image' => null])->save();
            }

            if ($image !== null) {
                if ($blogPost->image) {
                    $this->deleteStoredFile($blogPost->image);
                }

                $storedPath = $image->store("blog-posts/{$blogPost->id}", 'public');
                $blogPost->forceFill(['image' => '/storage/'.$storedPath])->save();
            }

            return $blogPost->fresh();
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

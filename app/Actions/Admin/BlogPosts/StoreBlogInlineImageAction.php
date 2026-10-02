<?php

namespace App\Actions\Admin\BlogPosts;

use Illuminate\Http\UploadedFile;

class StoreBlogInlineImageAction
{
    /**
     * @return array{url: string, path: string}
     */
    public function execute(UploadedFile $image): array
    {
        $storedPath = $image->store('blog-posts/inline/'.now()->format('Y/m'), 'public');

        return [
            'url' => '/storage/'.$storedPath,
            'path' => $storedPath,
        ];
    }
}

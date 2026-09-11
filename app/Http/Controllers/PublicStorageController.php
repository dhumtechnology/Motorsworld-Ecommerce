<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        abort_if(str_contains($path, '..'), 404);

        $candidates = array_unique(array_filter([
            Storage::disk('public')->path($path),
            storage_path('app/public'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path)),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path),
        ]));

        foreach ($candidates as $fullPath) {
            if (is_file($fullPath)) {
                return response()->file($fullPath);
            }
        }

        abort(404);
    }
}

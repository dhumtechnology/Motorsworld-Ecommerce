<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locations\District;
use App\Services\Locations\LocationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationCatalog $locations,
    ) {}

    public function districts(Request $request): JsonResponse
    {
        $search = mb_substr(trim((string) $request->input('search', '')), 0, 80);
        $page = $this->locations->paginateDistricts(
            $search,
            max(1, (int) $request->input('page', 1)),
        );

        return response()->json([
            'data' => $page->getCollection()
                ->map(fn (District $district) => [
                    'id' => $district->id,
                    'name' => $district->displayName(),
                ])
                ->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }
}

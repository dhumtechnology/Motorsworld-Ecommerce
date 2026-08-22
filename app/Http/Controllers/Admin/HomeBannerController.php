<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\HomeBanners\DeleteHomeBannersAction;
use App\Actions\Admin\HomeBanners\UpsertHomeBannerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteHomeBannersRequest;
use App\Http\Requests\Admin\HomeBannerIndexRequest;
use App\Http\Requests\Admin\StoreHomeBannerRequest;
use App\Http\Requests\Admin\UpdateHomeBannerRequest;
use App\Models\Content\HomeBanner;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class HomeBannerController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly UpsertHomeBannerAction $upsertHomeBanner,
        private readonly DeleteHomeBannersAction $deleteHomeBanners,
    ) {}

    public function index(HomeBannerIndexRequest $request): View
    {
        $now = now();

        $banners = HomeBanner::query()
            ->when(
                $request->searchTerm(),
                fn (Builder $query, string $search) => $query->where('title', 'like', '%'.$search.'%'),
            )
            ->when(
                $request->statusFilter(),
                function (Builder $query, string $status) use ($now) {
                    match ($status) {
                        'active' => $query
                            ->where('is_active', true)
                            ->where('starts_at', '<=', $now)
                            ->where(function (Builder $query) use ($now) {
                                $query
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $now);
                            }),
                        'scheduled' => $query
                            ->where('is_active', true)
                            ->where('starts_at', '>', $now),
                        'expired' => $query
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<', $now),
                        'inactive' => $query->where('is_active', false),
                        default => null,
                    };
                },
            )
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.home-banners.index', [
            'banners' => $banners,
            'filters' => [
                'search' => $request->searchTerm(),
                'status' => $request->statusFilter(),
            ],
            'hasActiveFilters' => $request->hasActiveFilters(),
        ]);
    }

    public function create(): View
    {
        return view('admin.home-banners.create');
    }

    public function store(StoreHomeBannerRequest $request): RedirectResponse
    {
        $banner = $this->upsertHomeBanner->execute(
            $request->bannerAttributes(),
            null,
            $request->imageFile(),
        );

        return redirect()
            ->route('admin.home-banners.index')
            ->with('status', 'Banner del home creado correctamente.');
    }

    public function edit(HomeBanner $homeBanner): View
    {
        return view('admin.home-banners.edit', [
            'banner' => $homeBanner,
        ]);
    }

    public function update(UpdateHomeBannerRequest $request, HomeBanner $homeBanner): RedirectResponse
    {
        $banner = $this->upsertHomeBanner->execute(
            $request->bannerAttributes(),
            $homeBanner,
            $request->imageFile(),
        );

        return redirect()
            ->route('admin.home-banners.index')
            ->with('status', 'Banner del home actualizado correctamente.');
    }

    public function destroy(HomeBanner $homeBanner): RedirectResponse
    {
        $result = $this->deleteHomeBanners->execute([$homeBanner->id]);

        $message = $result['deleted'] === 1
            ? 'Banner eliminado correctamente.'
            : 'No se pudo eliminar el banner.';

        return redirect()
            ->route('admin.home-banners.index')
            ->with('status', $message);
    }

    public function bulkDestroy(BulkDeleteHomeBannersRequest $request): RedirectResponse
    {
        $result = $this->deleteHomeBanners->execute($request->ids());

        $message = match (true) {
            $result['deleted'] === 0 => 'No se eliminó ningún banner.',
            $result['deleted'] === 1 => '1 banner eliminado correctamente.',
            default => "{$result['deleted']} banners eliminados correctamente.",
        };

        return redirect()
            ->route('admin.home-banners.index')
            ->with('status', $message);
    }
}

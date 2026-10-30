<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\PageBanners\UpdatePageBannerAction;
use App\Enums\Content\PageBannerKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageBannerRequest;
use App\Models\Content\PageBanner;
use App\Services\Content\PageBannerResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class PageBannerController extends Controller
{
    public function __construct(
        private readonly UpdatePageBannerAction $updatePageBanner,
        private readonly PageBannerResolver $resolver,
    ) {}

    public function index(): View
    {
        PageBanner::ensureRows();

        $banners = PageBanner::query()
            ->get()
            ->keyBy(fn (PageBanner $banner) => $banner->page_key->value);

        $pages = collect(PageBannerKey::cases())->map(function (PageBannerKey $key) use ($banners) {
            $banner = $banners->get($key->value);

            return [
                'banner' => $banner,
                'key' => $key,
                'imageUrl' => $this->resolver->imageUrl($key),
                'isCustom' => $banner?->hasCustomImage() ?? false,
                'shopUrl' => Route::has($key->shopRouteName()) ? route($key->shopRouteName()) : null,
            ];
        });

        return view('admin.page-banners.index', [
            'pages' => $pages,
        ]);
    }

    public function edit(PageBanner $pageBanner): View
    {
        PageBanner::ensureRows();

        return view('admin.page-banners.edit', [
            'banner' => $pageBanner,
            'imageUrl' => $this->resolver->imageUrl($pageBanner->page_key),
        ]);
    }

    public function update(UpdatePageBannerRequest $request, PageBanner $pageBanner): RedirectResponse
    {
        $this->updatePageBanner->execute(
            $pageBanner,
            $request->imageFile(),
            $request->shouldRestoreDefault(),
        );

        $message = $request->shouldRestoreDefault()
            ? 'Se restauró la imagen por defecto de '.$pageBanner->page_key->label().'.'
            : 'Banner de '.$pageBanner->page_key->label().' actualizado correctamente.';

        return redirect()
            ->route('admin.page-banners.index')
            ->with('status', $message);
    }
}

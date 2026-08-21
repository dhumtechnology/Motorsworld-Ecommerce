<?php

namespace App\Providers;

use App\Actions\Admin\GetAdminSidebarPendingCountsAction;
use App\Actions\Cart\BuildCartLinesAction;
use App\Actions\Shop\GetShopHeaderSearchDataAction;
use App\Models\Auth\User;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartTotalsService;
use App\Services\Payments\Culqi\CulqiClient;
use App\Services\Payments\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CulqiClient::class, fn () => CulqiClient::fromConfig());
        $this->app->singleton(MercadoPagoClient::class, fn () => MercadoPagoClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureSharedHostingPublicDisk();

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            return $user->hasPermission($ability) ? true : null;
        });

        View::composer('layouts.admin', function ($view): void {
            $user = auth()->user();

            if ($user instanceof User && ! $user->relationLoaded('roles')) {
                $user->load('roles.permissions');
            }

            $view->with(
                'sidebarPendingCounts',
                app(GetAdminSidebarPendingCountsAction::class)->execute(),
            );
        });

        View::composer('layouts.shop', function ($view): void {
            $request = request();
            $cartLines = collect();
            $count = 0;
            $cartTotals = null;

            if ($request->hasSession()) {
                $cart = app(CartResolver::class)->resolve(
                    $request->user(),
                    $request->session()->getId(),
                );

                $cartLines = app(BuildCartLinesAction::class)->execute($cart);
                $count = (int) $cartLines->sum('quantity');
                $cartTotals = app(CartTotalsService::class)->summarize($cartLines);
            }

            $searchData = app(GetShopHeaderSearchDataAction::class)->execute();

            $view->with([
                'cartItemCount' => $count,
                'cartDrawerLines' => $cartLines,
                'cartDrawerTotals' => $cartTotals,
                'searchCategories' => $searchData['searchCategories'],
                'searchRecommendedProducts' => $searchData['searchRecommendedProducts'],
            ]);
        });
    }

    /**
     * Deploy típico: /laravel (app) + /public_html (web).
     * Sin symlink, las imágenes públicas deben vivir en public_html/storage.
     */
    private function configureSharedHostingPublicDisk(): void
    {
        if (env('FILESYSTEM_PUBLIC_ROOT')) {
            return;
        }

        $publicHtml = dirname(base_path()).DIRECTORY_SEPARATOR.'public_html';
        if (! is_dir($publicHtml)) {
            return;
        }

        $storageRoot = $publicHtml.DIRECTORY_SEPARATOR.'storage';
        if (! is_dir($storageRoot)) {
            @mkdir($storageRoot, 0755, true);
        }

        if (! is_dir($storageRoot)) {
            return;
        }

        config(['filesystems.disks.public.root' => $storageRoot]);
    }
}

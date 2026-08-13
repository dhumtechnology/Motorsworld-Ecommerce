<?php

namespace App\Providers;

use App\Actions\Admin\GetAdminSidebarPendingCountsAction;
use App\Actions\Cart\BuildCartLinesAction;
use App\Actions\Shop\GetShopHeaderSearchDataAction;
use App\Models\Auth\User;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartTotalsService;
use App\Services\Payments\Culqi\CulqiClient;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
}

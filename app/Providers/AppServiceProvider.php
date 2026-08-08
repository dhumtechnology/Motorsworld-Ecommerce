<?php

namespace App\Providers;

use App\Models\Auth\User;
use App\Services\Cart\CartResolver;
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
        });

        View::composer('layouts.shop', function ($view): void {
            $request = request();
            $count = 0;

            if ($request->hasSession()) {
                $cart = app(CartResolver::class)->resolve(
                    $request->user(),
                    $request->session()->getId(),
                );

                $count = (int) $cart->items()->sum('quantity');
            }

            $view->with('cartItemCount', $count);
        });
    }
}

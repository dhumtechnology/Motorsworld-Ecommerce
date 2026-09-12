<?php

namespace App\Providers;

use App\Actions\Admin\GetAdminSidebarPendingCountsAction;
use App\Actions\Cart\BuildCartLinesAction;
use App\Actions\Shop\GetShopFooterLinksAction;
use App\Actions\Shop\GetShopHeaderSearchDataAction;
use App\Models\Auth\User;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartTotalsService;
use App\Services\Payments\Culqi\CulqiClient;
use App\Services\Payments\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->useSharedHostingPublicPath();
        $this->ensureStorageDirectories();

        $this->app->singleton(CulqiClient::class, fn () => CulqiClient::fromConfig());
        $this->app->singleton(MercadoPagoClient::class, fn () => MercadoPagoClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->configureSharedHostingMail();
        $this->configureProductionCulqi();
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
            $cartLines = collect();
            $count = 0;
            $cartTotals = null;
            $searchCategories = [];
            $searchRecommendedProducts = collect();
            $footerLinks = ['clientes' => [], 'productos' => [], 'acerca_de' => []];

            try {
                $request = request();

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
                $searchCategories = $searchData['searchCategories'];
                $searchRecommendedProducts = $searchData['searchRecommendedProducts'];
                $footerLinks = app(GetShopFooterLinksAction::class)->execute();
            } catch (Throwable $exception) {
                report($exception);
            }

            $view->with([
                'cartItemCount' => $count,
                'cartDrawerLines' => $cartLines,
                'cartDrawerTotals' => $cartTotals,
                'searchCategories' => $searchCategories,
                'searchRecommendedProducts' => $searchRecommendedProducts,
                'footerLinks' => $footerLinks,
            ]);
        });
    }

    /**
     * cPanel: public_html es la web; Laravel vive en /laravel.
     * Sin esto, Vite busca CSS en laravel/public/build y no carga estilos.
     */
    private function useSharedHostingPublicPath(): void
    {
        $publicHtml = dirname(base_path()).DIRECTORY_SEPARATOR.'public_html';

        if (! is_dir($publicHtml)) {
            return;
        }

        $this->app->usePublicPath($publicHtml);
    }

    /**
     * En cPanel esas carpetas a menudo no se suben (Docker las monta como volúmenes).
     * Si views no existe, realpath() deja view.compiled vacío y Blade lanza
     * "Please provide a valid cache path".
     */
    private function ensureStorageDirectories(): void
    {
        $directories = [
            storage_path('app/public'),
            storage_path('app/private'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }

        $viewsPath = storage_path('framework/views');
        if (! is_dir($viewsPath)) {
            @mkdir($viewsPath, 0775, true);
        }
        if (is_dir($viewsPath)) {
            config(['view.compiled' => $viewsPath]);
        }
    }

    /**
     * cPanel SMTP usa el certificado del servidor (priva10...), no de mail.dominio.
     * Si config está cacheada con verify_peer=true, el checkout espera 60s y el
     * navegador muestra "Failed to fetch".
     */
    private function configureSharedHostingMail(): void
    {
        config([
            'mail.mailers.smtp.verify_peer' => false,
            'mail.mailers.smtp.timeout' => 8,
        ]);
    }

    /**
     * Si hay llaves live en el servidor, nunca simular Culqi aunque CULQI_FAKE
     * haya quedado en true o la config esté cacheada.
     */
    private function configureProductionCulqi(): void
    {
        $public = trim((string) config('services.culqi.public_key'));
        $secret = trim((string) config('services.culqi.secret_key'));

        if (str_starts_with($public, 'pk_live_') && str_starts_with($secret, 'sk_live_')) {
            config(['services.culqi.fake' => false]);
        }
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

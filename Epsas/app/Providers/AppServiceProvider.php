<?php

namespace App\Providers;

use App\Auth\CachedEloquentUserProvider;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Exception;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('cached_eloquent', function ($app, array $config) {
            return new CachedEloquentUserProvider($app['hash'], $config['model']);
        });

        View::composer('*', function ($view) {
            static $sharedCompanySettings;
            static $sharedAuthUserResolved = false;
            static $sharedAuthUser;

            // Get company settings with fallback defaults if database is unavailable
            $sharedCompanySettings ??= Cache::remember('shared_company_settings', now()->addDays(7), function () {
                try {
                    return SystemSetting::getValue('general', [
                        'company_name' => 'EPSAS',
                        'company_alias' => 'Panel administrativo',
                        'company_logo' => null,
                    ]);
                } catch (Exception $e) {
                    // If database connection fails, return default values
                    // This prevents cascading failures when DB is down
                    return [
                        'company_name' => 'EPSAS',
                        'company_alias' => 'Panel administrativo',
                        'company_logo' => null,
                    ];
                }
            });

            if (!$sharedAuthUserResolved) {
                try {
                    $sharedAuthUser = auth()->check()
                        ? auth()->user()
                        : null;
                } catch (Exception $e) {
                    // If auth check fails, treat as not authenticated
                    $sharedAuthUser = null;
                }
                $sharedAuthUserResolved = true;
            }

            $view->with('sharedCompanySettings', $sharedCompanySettings);
            $view->with('sharedAuthUser', $sharedAuthUser);
        });
    }
}

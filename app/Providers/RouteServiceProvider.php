<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::group([
                'prefix' => 'api',
                'middleware' => 'api',
                'namespace' => $this->namespace,
            ], function () {
                require base_path('routes/api.php');
            });

            Route::group([
                'middleware' => 'web',
                'namespace' => $this->namespace,
            ], function () {
                require base_path('routes/web.php');
            });
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Public endpoints that may be auto-refreshed; keep it strict enough to block abuse,
        // but loose enough for refresh interval 30–60s.
        RateLimiter::for('public-summary', function (Request $request) {
            return Limit::perMinute(30)->by('public-summary|' . $request->ip());
        });
    }
}

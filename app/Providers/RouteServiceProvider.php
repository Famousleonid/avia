<?php

namespace App\Providers;

use App\Models\Workorder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Jenssegers\Agent\Agent;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/cabinet';

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
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });

        parent::boot();

        Route::bind('workorder', function ($value) {
            return Workorder::withDrafts()->whereKey($value)->firstOrFail();
        });


    }

    public static function redirectPath(Request $request = null): string
    {
        $request = $request ?? request(); // на всякий случай
        $user = auth()->user();
        $agent = new Agent();

        if (!$user) {
            return '/login';
        }

        if ($agent->isMobile()) {
            return '/mobile';
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return '/admin';
        }

        return self::HOME; // '/cabinet'
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
    }
}

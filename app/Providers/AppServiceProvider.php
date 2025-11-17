<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('valuestore', function () {
            return \Spatie\Valuestore\Valuestore::make(storage_path('app/private/setting.json'));
        });

        $values = $this->app->valuestore->all();

        $this->app->bind('settings', function () use ($values) {
            return $values;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Blade::if('role', function (string $role) {
            return auth()->check() && auth()->user()->roleIs($role);
        });

        Blade::if('hasanyrole', function (string $roles) {
            return auth()->check() && auth()->user()->hasAnyRole($roles);
        });
    }
}

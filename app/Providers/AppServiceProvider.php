<?php

namespace App\Providers;

use App\Models\Workorder;
use App\Observers\WorkorderObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        Blade::if('roles', function (string $roles) {
            if (! auth()->check()) {
                return false;
            }

            // 'Admin|Manager' → ['Admin', 'Manager']
            $rolesArray = explode('|', $roles);

            return auth()->user()->roleIs($rolesArray);
        });

        Blade::if('admin', function () {
            // return auth()->check() && auth()->user()->roleIs('Admin');
            return auth()->check() && auth()->user()->is_admin;
        });

        Blade::if('notrole', function (string $role) {
            return auth()->check() && !auth()->user()->roleIs($role);
        });

        Blade::if('notroles', function (string $roles) {
            if (!auth()->check()) return false;

            $rolesArray = explode('|', $roles);

            return !auth()->user()->roleIs($rolesArray);
        });

        Workorder::observe(WorkorderObserver::class);

        DB::listen(function ($query) {
            // время в миллисекундах
            if ($query->time > 500) {
                Log::channel('avia')->warning('SLOW SQL', [
                    'time_ms'  => $query->time,
                    'sql'      => $query->sql,
                    'bindings' => $query->bindings,
                    // 'connection' => $query->connectionName, // если нужно
                ]);
            }
        });
    }
}

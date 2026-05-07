<?php

namespace App\Providers;

use App\Models\Workorder;
use App\Observers\WorkorderObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->guardDatabaseSafety();

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

            // 'Admin|Manager' -> ['Admin', 'Manager']
            $rolesArray = explode('|', $roles);

            return auth()->user()->roleIs($rolesArray);
        });

        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->roleIs('Admin');
        });

        Blade::if('systemadmin', function () {
            return auth()->check() && auth()->user()->isSystemAdmin();
        });

        Blade::if('notrole', function (string $role) {
            return auth()->check() && ! auth()->user()->roleIs($role);
        });

        Blade::if('notroles', function (string $roles) {
            if (! auth()->check()) {
                return false;
            }

            $rolesArray = explode('|', $roles);

            return ! auth()->user()->roleIs($rolesArray);
        });

        Workorder::observe(WorkorderObserver::class);
    }

    private function guardDatabaseSafety(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database", '');
        $normalizedDatabase = strtolower(trim($database));

        if (app()->environment('testing') && ! $this->isTestingDatabaseName($normalizedDatabase)) {
            throw new RuntimeException(sprintf(
                'Refusing to boot testing environment against non-testing database [%s] on connection [%s].',
                $database !== '' ? $database : 'undefined',
                $connection
            ));
        }

        if (! app()->runningInConsole()) {
            return;
        }

        $command = $_SERVER['argv'][1] ?? null;
        if (! in_array($command, ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'], true)) {
            return;
        }

        if ($this->isTestingDatabaseName($normalizedDatabase)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run destructive database command [%s] against non-testing database [%s] on connection [%s].',
            $command,
            $database !== '' ? $database : 'undefined',
            $connection
        ));
    }

    private function isTestingDatabaseName(string $database): bool
    {
        return $database !== '' && str_contains($database, 'testing');
    }
}

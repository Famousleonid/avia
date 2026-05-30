<?php

namespace App\Providers;

use App\Models\Component;
use App\Models\Workorder;
use App\Models\Tdr;
use App\Observers\ComponentObserver;
use App\Observers\TdrObserver;
use App\Observers\WorkorderObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
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

        Blade::directive('projectDate', function ($expression) {
            return "<?php echo e(format_project_date({$expression}) ?? '-'); ?>";
        });

        Workorder::observe(WorkorderObserver::class);
        Tdr::observe(TdrObserver::class);
        Component::observe(ComponentObserver::class);

        View::composer($this->printFormViews(), function (): void {
            $this->disableDebugbarForResponse();
        });

        View::composer($this->mobileViews(), function (): void {
            $this->disableDebugbarForResponse();
        });
    }

    /**
     * @return array<int, string>
     */
    private function printFormViews(): array
    {
        return [
            'admin.tdrs.wo_BoxTitle',
            'admin.tdrs.wo_ProcessForm',
            'admin.tdrs.tdrForm',
            'admin.tdrs.prlForm',
            'admin.tdrs.specProcessForm',
            'admin.tdrs.specProcessFormEmp',
            'admin.tdrs.logCardForm',
            'admin.tdrs.ndtFormStd',
            'admin.tdrs.cadFormStd',
            'admin.tdrs.stressFormStd',
            'admin.tdrs.paintFormStd',
            'admin.trainings.form112',
            'admin.trainings.form132',
            'admin.tdr-processes.processesForm',
            'admin.tdr-processes.travelForm',
            'admin.tdr-processes.packageForms',
            'admin.extra_processes.processesForm',
            'admin.wo_bushings.processesForm',
            'admin.wo_bushings.specProcessForm',
            'admin.rm_reports.rmRecordForm',
            'admin.quality.forms.specProcessForm',
            'admin.log_card.logCardForm',
            'admin.log_card.logCardForm2',
            'admin.log_card.logCardForm3',
            'admin.transfers.transferForm',
            'admin.transfers.transfersForm',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function mobileViews(): array
    {
        return [
            'mobile.*',
        ];
    }

    private function disableDebugbarForResponse(): void
    {
        if (! $this->app->bound('debugbar')) {
            return;
        }

        $debugbar = $this->app->make('debugbar');

        if (method_exists($debugbar, 'disable')) {
            $debugbar->disable();
        }
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

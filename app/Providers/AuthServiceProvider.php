<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
//    protected $policies = [
//        \App\Models\Workorder::class => \App\Policies\WorkorderPolicy::class,
//        \App\Models\Manual::class => \App\Policies\ManualPolicy::class,
//        \App\Models\Unit::class => \App\Policies\UnitPolicy::class,
//        \App\Models\User::class => \App\Policies\UserPolicy::class,
//        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
//        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
//
//    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        foreach (config('permissions') as $model => $actions) {
            foreach ($actions as $action => $rolesAllowed) {

                Gate::define("{$model}.{$action}", function ($user, $item = null) use ($rolesAllowed, $model, $action) {

                    // 1. Проверяем роль
                    if (! $user->roleIs($rolesAllowed)) {
                        return false;
                    }

//                    // 2. Дополнительные проверки для моделей
//                    if ($item) {
//                        // technician → редактирует только свои workorders/tasks
//                        if ($user->role->name === 'technician') {
//                            if (in_array($model, ['workorders','tasks','manuals'])) {
//                                return $item->user_id === $user->id;
//                            }
//                        }
//                    }

                    return true;
                });
            }
        }
    }
}

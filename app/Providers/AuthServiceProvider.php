<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */

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

                    if ($model === 'users' && $user->roleIs('Admin') && ! $user->isSystemAdmin()) {
                        return false;
                    }

                    if ($model === 'users'
                        && ! $user->isSystemAdmin()
                        && ! in_array($action, ['viewAny', 'view'], true)) {
                        return false;
                    }

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

        foreach (config('features', []) as $featureKey => $definition) {
            Gate::define("feature.{$featureKey}", function (User $user) use ($definition) {
                $rolesAllowed = $definition['roles'] ?? [];
                $userIds = array_map(static fn ($id) => (int) $id, $definition['user_ids'] ?? []);
                $allowIsAdmin = (bool) ($definition['allow_is_admin'] ?? false);

                if ($allowIsAdmin && $user->isAdmin()) {
                    return true;
                }

                if ($rolesAllowed !== [] && $user->roleIs($rolesAllowed)) {
                    return true;
                }

                if ($userIds !== [] && in_array((int) $user->id, $userIds, true)) {
                    return true;
                }

                return false;
            });
        }
    }
}

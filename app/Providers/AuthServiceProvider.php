<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (config('permissions') as $model => $actions) {
            foreach ($actions as $action => $rolesAllowed) {
                Gate::define("{$model}.{$action}", function ($user) use ($rolesAllowed) {
                    return $user->roleIs($rolesAllowed);
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

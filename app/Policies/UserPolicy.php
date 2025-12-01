<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->roleIs(config('permissions.users.viewAny'));
    }

    public function view(User $user, User $target): bool
    {
        return $user->roleIs(config('permissions.users.view'));
    }

    public function create(User $user): bool
    {
        return $user->roleIs(config('permissions.users.create'));
    }

    public function update(User $user, User $target): bool
    {
        return $user->roleIs(config('permissions.users.update'));
    }

    public function delete(User $user, User $target): bool
    {
        // никто не должен иметь возможность удалить себя
        if ($user->id === $target->id) {
            return false;
        }

        return $user->roleIs(config('permissions.users.delete'));
    }

//    public function before(User $user, string $ability)
//    {
//        dd('UserPolicy@before', [
//            'ability'       => $ability,                      // view, delete, update...
//            'auth_id'       => $user->id,
//            'auth_role'     => $user->roleName(),
//            'is_admin_flag' => $user->is_admin,
//            'config_users'  => config('permissions.users'),
//        ]);
//    }

}

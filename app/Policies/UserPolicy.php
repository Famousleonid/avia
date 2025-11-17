<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('users.viewAny');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.view', $target);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update', $target);
    }

    public function delete(User $user, User $target): bool
    {

    // никто не должен иметь возможность удалить себя
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.delete', $target);
    }
}

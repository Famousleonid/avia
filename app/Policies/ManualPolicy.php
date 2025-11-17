<?php

namespace App\Policies;

use App\Models\Manual;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManualPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manuals.viewAny');
    }

    public function view(User $user, Manual $manual): bool
    {
        return $user->can('manuals.view', $manual);
    }

    public function create(User $user): bool
    {
        return $user->can('manuals.create');
    }

    public function update(User $user, Manual $manual): bool
    {
        return $user->can('manuals.update', $manual);
    }

    public function delete(User $user, Manual $manual): bool
    {
        return $user->can('manuals.delete', $manual);
    }
}

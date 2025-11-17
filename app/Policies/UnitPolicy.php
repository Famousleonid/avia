<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('units.viewAny');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('units.view', $unit);
    }

    public function create(User $user): bool
    {
        return $user->can('units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('units.update', $unit);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('units.delete', $unit);
    }
}

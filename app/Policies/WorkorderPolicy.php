<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workorder;

class WorkorderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('workorders.viewAny');
    }

    public function view(User $user, Workorder $workorder): bool
    {
        return $user->can('workorders.view', $workorder);
    }

    public function create(User $user): bool
    {
        return $user->can('workorders.create');
    }

    public function update(User $user, Workorder $workorder): bool
    {
        return $user->can('workorders.update', $workorder);
    }

    public function delete(User $user, Workorder $workorder): bool
    {
        return $user->can('workorders.delete', $workorder);
    }
}

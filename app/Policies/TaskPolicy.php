<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tasks.viewAny');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('tasks.view', $task);
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('tasks.update', $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('tasks.delete', $task);
    }
}

<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('customer.viewAny');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customer.view', $customer);
    }

    public function create(User $user): bool
    {
        return $user->can('customer.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customer.update', $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customer.delete', $customer);
    }
}

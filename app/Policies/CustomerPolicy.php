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
        return $this->canUseCustomerDirectory($user, 'viewAny');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->canUseCustomerDirectory($user, 'view', $customer);
    }

    public function create(User $user): bool
    {
        return $this->canUseCustomerDirectory($user, 'create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->canUseCustomerDirectory($user, 'update', $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->roleIs('Admin');
    }

    private function canUseCustomerDirectory(User $user, string $ability, ?Customer $customer = null): bool
    {
        if ($user->roleIs(['Admin', 'Manager'])) {
            return true;
        }

        return $customer === null
            ? $user->can("customer.{$ability}")
            : $user->can("customer.{$ability}", $customer);
    }
}

<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Manual;
use App\Models\User;

class ManualPartAccessGuard
{
    public function canManageManualParts(?User $user, Manual $manual): ProcessAccessDecision
    {
        if (! $user) {
            return ProcessAccessDecision::deny('You must be logged in to manage manual parts.', 'auth_required');
        }

        if ($this->canBypassLocks($user)) {
            return ProcessAccessDecision::allow();
        }

        if ($manual->relationLoaded('partLock') ? $manual->partLock : $manual->partLock()->exists()) {
            return $this->denyLocked();
        }

        return ProcessAccessDecision::allow();
    }

    public function canMutateComponent(?User $user, Component $component): ProcessAccessDecision
    {
        $manual = $component->manual;
        if (! $manual) {
            return ProcessAccessDecision::deny('Manual not found for this part.', 'manual_missing');
        }

        return $this->canManageManualParts($user, $manual);
    }

    public function canLockManualParts(?User $user, Manual $manual): ProcessAccessDecision
    {
        if (! $user) {
            return ProcessAccessDecision::deny('You must be logged in to lock manual parts.', 'auth_required');
        }

        if (! $this->canBypassLocks($user)) {
            return $this->denyLocked('Only assigned lock managers can change manual parts locks. Contact', 'lock_manager_required');
        }

        return ProcessAccessDecision::allow();
    }

    public function canUnlockManualParts(?User $user, Manual $manual): ProcessAccessDecision
    {
        return $this->canLockManualParts($user, $manual);
    }

    private function canBypassLocks(User $user): bool
    {
        return $user->isAdmin() || $user->canManageLockedManualParts();
    }

    private function denyLocked(string $prefix = 'Manual parts are locked. Contact', string $reason = 'manual_parts_locked'): ProcessAccessDecision
    {
        $contacts = User::query()
            ->where(function ($query) {
                $query->where('is_admin', 1)
                    ->orWhere('can_manage_locked_manual_parts', 1);
            })
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $message = $prefix.' '.$this->formatContacts($contacts).'.';

        return ProcessAccessDecision::deny($message, $reason, $contacts);
    }

    private function formatContacts(array $contacts): string
    {
        if ($contacts === []) {
            return 'System Administrator';
        }

        return implode(', ', $contacts);
    }
}

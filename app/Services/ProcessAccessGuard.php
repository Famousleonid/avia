<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\ProcessName;
use App\Models\User;

class ProcessAccessGuard
{
    public function canBrowseProcessCatalog(User $user, Manual $manual): ProcessAccessDecision
    {
        if ($user) {
            return ProcessAccessDecision::allow();
        }

        return ProcessAccessDecision::deny('You must be logged in to view process catalog.', 'auth_required');
    }

    public function canAttachExistingManualProcess(User $user, Manual $manual): ProcessAccessDecision
    {
        return $this->canManageManual($user, $manual);
    }

    public function canAddManualProcess(User $user, Manual $manual, ?ProcessName $processName = null): ProcessAccessDecision
    {
        $baseAccess = $this->canManageManual($user, $manual);
        if (! $baseAccess->allowed) {
            return $baseAccess;
        }

        if ($processName && $this->isProcessNameLocked($manual, $processName) && ! $this->canBypassLocks($user)) {
            return $this->denyProcessNameCreationLocked($manual, $processName);
        }

        return ProcessAccessDecision::allow();
    }

    public function canCreateProcessDefinition(User $user, Manual $manual, ?ProcessName $processName = null): ProcessAccessDecision
    {
        if (! $user) {
            return ProcessAccessDecision::deny('You must be logged in to create a process.', 'auth_required');
        }

        if ($processName && $this->isProcessNameLocked($manual, $processName) && ! $this->canBypassLocks($user)) {
            return $this->denyProcessNameCreationLocked($manual, $processName);
        }

        return ProcessAccessDecision::allow();
    }

    public function canUpdateManualProcess(User $user, ManualProcess $manualProcess): ProcessAccessDecision
    {
        return $this->canMutateManualProcess($user, $manualProcess);
    }

    public function canDeleteManualProcess(User $user, ManualProcess $manualProcess): ProcessAccessDecision
    {
        return $this->canMutateManualProcess($user, $manualProcess);
    }

    public function canLockProcessName(User $user, Manual $manual, ProcessName $processName): ProcessAccessDecision
    {
        $baseAccess = $this->canManageManual($user, $manual);
        if (! $baseAccess->allowed) {
            return $baseAccess;
        }

        if (! $this->canBypassLocks($user)) {
            return $this->denyLocked('Only assigned lock managers can change process locks. Contact', 'lock_manager_required');
        }

        return ProcessAccessDecision::allow();
    }

    public function canUnlockProcessName(User $user, Manual $manual, ProcessName $processName): ProcessAccessDecision
    {
        return $this->canLockProcessName($user, $manual, $processName);
    }

    public function canLockManualProcess(User $user, ManualProcess $manualProcess): ProcessAccessDecision
    {
        $manual = $manualProcess->manual;
        if (! $manual) {
            return ProcessAccessDecision::deny('Manual not found for this process.', 'manual_missing');
        }

        $baseAccess = $this->canManageManual($user, $manual);
        if (! $baseAccess->allowed) {
            return $baseAccess;
        }

        if (! $this->canBypassLocks($user)) {
            return $this->denyLocked('Only assigned lock managers can change process locks. Contact', 'lock_manager_required');
        }

        return ProcessAccessDecision::allow();
    }

    public function canUnlockManualProcess(User $user, ManualProcess $manualProcess): ProcessAccessDecision
    {
        return $this->canLockManualProcess($user, $manualProcess);
    }

    public function canManageManual(User $user, Manual $manual): ProcessAccessDecision
    {
        if ($user->roleIs('Admin') || $user->hasFullManualsAccess()) {
            return ProcessAccessDecision::allow();
        }

        $hasManualPermission = $user->permittedManuals()
            ->where('manuals.id', $manual->id)
            ->exists();

        if ($hasManualPermission) {
            return ProcessAccessDecision::allow();
        }

        return ProcessAccessDecision::deny('You do not have access to this manual.', 'manual_access_denied');
    }

    private function canMutateManualProcess(User $user, ManualProcess $manualProcess): ProcessAccessDecision
    {
        $manual = $manualProcess->manual;
        if (! $manual) {
            return ProcessAccessDecision::deny('Manual not found for this process.', 'manual_missing');
        }

        $baseAccess = $this->canManageManual($user, $manual);
        if (! $baseAccess->allowed) {
            return $baseAccess;
        }

        if ($manualProcess->is_locked && ! $this->canBypassLocks($user)) {
            return $this->denyLocked('This process is locked. Contact', 'manual_process_locked');
        }

        return ProcessAccessDecision::allow();
    }

    private function isProcessNameLocked(Manual $manual, ProcessName $processName): bool
    {
        return $this->findProcessNameLock($manual, $processName) !== null;
    }

    private function findProcessNameLock(Manual $manual, ProcessName $processName): ?ManualProcessNameLock
    {
        return ManualProcessNameLock::query()
            ->where('manual_id', $manual->id)
            ->where('process_name_id', $processName->id)
            ->with('lockedBy')
            ->first();
    }

    private function canBypassLocks(User $user): bool
    {
        return $user->isAdmin() || $user->canManageLockedManualProcesses();
    }

    private function denyLocked(string $prefix, string $reason): ProcessAccessDecision
    {
        $contacts = User::query()
            ->where(function ($query) {
                $query->where('is_admin', 1)
                    ->orWhereHas('featureAccesses', fn ($featureQuery) => $featureQuery
                        ->where('feature_key', 'manuals.locked_processes'));
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

    private function denyProcessNameCreationLocked(Manual $manual, ProcessName $processName): ProcessAccessDecision
    {
        $contacts = User::query()
            ->whereHas('featureAccesses', fn ($featureQuery) => $featureQuery
                ->where('feature_key', 'manuals.locked_processes'))
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $contact = $this->formatLockContacts($contacts);

        $message = sprintf(
            'Locked. Choose from the list or contact %s to add a subprocess.',
            $contact
        );

        return ProcessAccessDecision::deny($message, 'process_name_locked', $contacts);
    }

    private function formatContacts(array $contacts): string
    {
        if ($contacts === []) {
            return 'System Admin';
        }

        return implode(', ', $contacts);
    }

    private function formatLockContacts(array $contacts): string
    {
        if ($contacts === []) {
            return 'System Administrator';
        }

        return implode(', ', $contacts).' or System Administrator';
    }
}

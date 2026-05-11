<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Collection;

class LogCardTdrAccessService
{
    private const RESTRICTED_ROLES = ['Technician', 'Team Leader'];
    private const LOCK_TASK_NAME = 'post disassembly inspection';

    public function forWorkorder(Workorder $workorder, ?User $user): array
    {
        $locked = $this->isLockedByPostDisassemblyInspection($workorder);
        $restrictedRole = $user?->roleIs(self::RESTRICTED_ROLES) ?? false;
        $readOnly = $locked && $restrictedRole;

        return [
            'locked' => $locked,
            'restricted_role' => $restrictedRole,
            'read_only' => $readOnly,
            'message' => $readOnly
                ? 'Log Card editing is locked after Post Disassembly inspection date is filled. Please contact Quality Manager.'
                : null,
        ];
    }

    public function isLockedByPostDisassemblyInspection(Workorder $workorder): bool
    {
        $mainRows = $workorder->relationLoaded('main')
            ? $workorder->main
            : $workorder->main()->with('task:id,name')->get();

        if (! $mainRows instanceof Collection) {
            return false;
        }

        return $mainRows
            ->filter(function ($main) {
                if (! $main || ! $main->task) {
                    return false;
                }

                return $this->normalizeTaskName((string) $main->task->name) === self::LOCK_TASK_NAME;
            })
            ->contains(fn ($main) => ! empty($main->date_finish));
    }

    private function normalizeTaskName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
    }
}

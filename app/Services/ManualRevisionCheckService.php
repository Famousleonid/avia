<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualRevisionCheck;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ManualRevisionCheckService
{
    public const INTERVAL_MONTHS = 3;

    public function lastCheck(Manual $manual): ?ManualRevisionCheck
    {
        if ($manual->relationLoaded('revisionChecks')) {
            return $manual->revisionChecks->sortByDesc('checked_at')->first();
        }

        return $manual->revisionChecks()
            ->with('checkedBy:id,name')
            ->latest('checked_at')
            ->latest('id')
            ->first();
    }

    public function nextDueDate(Manual $manual, ?ManualRevisionCheck $lastCheck = null): Carbon
    {
        $lastCheck ??= $this->lastCheck($manual);

        if ($lastCheck?->checked_at) {
            return Carbon::parse($lastCheck->checked_at)->addMonthsNoOverflow(self::INTERVAL_MONTHS)->startOfDay();
        }

        return now()->startOfDay();
    }

    public function statusFor(Manual $manual, ?ManualRevisionCheck $lastCheck = null): array
    {
        $nextDue = $this->nextDueDate($manual, $lastCheck);
        $today = now()->startOfDay();
        $daysUntilDue = $today->diffInDays($nextDue, false);

        return [
            'next_due_at' => $nextDue,
            'days_until_due' => $daysUntilDue,
            'status' => $daysUntilDue < 0 ? 'overdue' : ($daysUntilDue === 0 ? 'due_today' : 'scheduled'),
        ];
    }

    public function dueManuals(int $days = 30, int $limit = 10, bool $includeOverdue = true): Collection
    {
        $days = max(0, min(365, $days));
        $limit = max(1, min(100, $limit));
        $today = now()->startOfDay();
        $cutoff = $today->copy()->addDays($days);

        return Manual::query()
            ->with(['revisionChecks' => fn ($query) => $query->with('checkedBy:id,name')->latest('checked_at')->latest('id')])
            ->orderBy('number')
            ->get()
            ->map(function (Manual $manual) use ($today): array {
                $lastCheck = $manual->revisionChecks->first();
                $status = $this->statusFor($manual, $lastCheck);

                return [
                    'manual' => $manual,
                    'last_check' => $lastCheck,
                    'next_due_at' => $status['next_due_at'],
                    'days_until_due' => $status['days_until_due'],
                    'status' => $status['status'],
                    'is_overdue' => $status['next_due_at']->lt($today),
                ];
            })
            ->filter(function (array $row) use ($cutoff, $includeOverdue): bool {
                /** @var CarbonInterface $nextDue */
                $nextDue = $row['next_due_at'];

                if (! $includeOverdue && $nextDue->isPast() && ! $nextDue->isToday()) {
                    return false;
                }

                return $nextDue->lte($cutoff);
            })
            ->sortBy([
                ['next_due_at', 'asc'],
                fn (array $a, array $b) => strcmp((string) $a['manual']->number, (string) $b['manual']->number),
            ])
            ->values()
            ->take($limit);
    }
}

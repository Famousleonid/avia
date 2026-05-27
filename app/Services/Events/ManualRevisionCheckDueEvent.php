<?php

namespace App\Services\Events;

use App\Models\Manual;
use App\Services\ManualRevisionCheckService;
use Illuminate\Support\Collection;

class ManualRevisionCheckDueEvent implements EventDefinition
{
    public function __construct(
        protected ?ManualRevisionCheckService $checks = null,
    ) {
        $this->checks ??= app(ManualRevisionCheckService::class);
    }

    public function key(): string
    {
        return 'manual.revision_check_due';
    }

    public function dueSubjects(): Collection
    {
        return $this->checks->dueManuals(days: 0, limit: 500, includeOverdue: true)
            ->pluck('manual')
            ->values();
    }

    public function recipients($subject): array
    {
        return [];
    }

    public function message($subject): array
    {
        if (! $subject instanceof Manual) {
            return [];
        }

        $lastCheck = $this->checks->lastCheck($subject);
        $status = $this->checks->statusFor($subject, $lastCheck);
        $days = (int) $status['days_until_due'];
        $dueText = $days < 0
            ? abs($days).' days overdue'
            : ($days === 0 ? 'due today' : 'due in '.$days.' days');

        return [
            'fromUserId' => 0,
            'fromName' => 'System',
            'type' => 'manual_revision_check_due',
            'event' => $this->key(),
            'severity' => $days < 0 ? 'danger' : 'warning',
            'title' => 'Manual revision check due',
            'text' => 'CMM '.$subject->number.' revision check is '.$dueText.'.',
            'url' => route('manuals.show', ['manual' => $subject->id, 'tab' => 'revision']),
            'ui' => [
                'manual' => [
                    'number' => $subject->number,
                    'title' => $subject->title,
                    'revision_date' => $this->dateString($subject->revision_date),
                    'last_checked_at' => format_project_date($lastCheck?->checked_at),
                    'next_due_at' => format_project_date($status['next_due_at']),
                    'days_until_due' => $days,
                ],
            ],
            'payload' => [
                'manual_number' => $subject->number,
                'manual_title' => $subject->title,
                'manual_revision_date' => $this->dateString($subject->revision_date),
                'manual_last_checked_at' => format_project_date($lastCheck?->checked_at),
                'manual_next_due_at' => format_project_date($status['next_due_at']),
                'manual_days_until_due' => $days,
            ],
        ];
    }

    public function repeatEveryMinutes(): int
    {
        return 60 * 24;
    }

    private function dateString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return format_project_date($value) ?? '';
        }

        return format_project_date($value) ?? '';
    }
}

<?php

namespace App\Services\Ai\Tools;

use App\Models\User;
use App\Services\ManualRevisionCheckService;

class ListManualRevisionChecksDueTool
{
    public function __construct(
        protected ManualRevisionCheckService $checks,
    ) {
    }

    public function run(User $user, array $args): array
    {
        $days = (int) ($args['days'] ?? 30);
        $limit = (int) ($args['limit'] ?? 10);
        $includeOverdue = array_key_exists('include_overdue', $args)
            ? (bool) $args['include_overdue']
            : true;

        $rows = $this->checks->dueManuals($days, $limit, $includeOverdue)
            ->map(function (array $row): array {
                $manual = $row['manual'];
                $lastCheck = $row['last_check'];

                return [
                    'manual_number' => $manual->number,
                    'manual_title' => $manual->title,
                    'manual_url' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'revision']),
                    'current_revision_date' => format_project_date($manual->revision_date) ?? '',
                    'last_revision_number' => (string) ($lastCheck?->revision_number ?? ''),
                    'last_checked_at' => format_project_date($lastCheck?->checked_at),
                    'last_checked_by' => $lastCheck?->checkedBy?->name,
                    'next_due_at' => format_project_date($row['next_due_at']),
                    'days_until_due' => (int) $row['days_until_due'],
                    'status' => $row['status'],
                ];
            })
            ->values()
            ->all();

        return [
            'ok' => true,
            'days' => max(0, min(365, $days)),
            'limit' => max(1, min(100, $limit)),
            'include_overdue' => $includeOverdue,
            'count' => count($rows),
            'manuals' => $rows,
            'instruction_for_model' => 'Summarize as a compact table. Link only the CMM/manual number using manual_url. Never mention internal ids. If count is 0, say no manuals are due in the requested window.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'listManualRevisionChecksDue',
            'description' => 'List top CMM/manuals whose revision check is overdue or due within a requested number of days.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'days' => [
                        'type' => 'integer',
                        'description' => 'Window in days from today, e.g. 15, 30, 45. Defaults to 30.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of manuals to return, e.g. 10, 15, 20. Defaults to 10.',
                    ],
                    'include_overdue' => [
                        'type' => 'boolean',
                        'description' => 'Whether overdue manuals should be included. Defaults to true.',
                    ],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];
    }
}

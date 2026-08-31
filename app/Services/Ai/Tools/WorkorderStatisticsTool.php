<?php

namespace App\Services\Ai\Tools;

use App\Models\Main;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WorkorderStatisticsTool
{
    private const DEFAULT_LIMIT = 10;

    private const MAX_LIMIT = 50;

    public function run(User $user, array $args): array
    {
        if (! $user->isSystemAdmin()) {
            return [
                'ok' => false,
                'message' => 'Workorder statistics are available only to a System Admin.',
            ];
        }

        $mode = Str::lower(trim((string) ($args['mode'] ?? 'summary')));
        if (! in_array($mode, ['summary', 'turnaround'], true)) {
            return [
                'ok' => false,
                'message' => 'Unsupported statistics mode.',
            ];
        }

        $technicianQuery = trim((string) ($args['technician_query'] ?? ''));
        $customerQuery = trim((string) ($args['customer_query'] ?? ''));
        $instructionQuery = trim((string) ($args['instruction_query'] ?? ''));
        $status = Str::lower(trim((string) ($args['status'] ?? 'all')));
        if (! in_array($status, ['all', 'active', 'completed'], true)) {
            $status = 'all';
        }
        $shopRolesOnly = (bool) ($args['shop_roles_only'] ?? false);

        $query = Workorder::query()
            ->select([
                'workorders.id',
                'workorders.number',
                'workorders.user_id',
                'workorders.customer_id',
                'workorders.instruction_id',
                'workorders.open_at',
            ])
            ->with([
                'user:id,name,selection_name_order',
                'customer:id,name',
                'instruction:id,name',
                'main' => fn ($main) => $main
                    ->select([
                        'mains.id',
                        'mains.workorder_id',
                        'mains.task_id',
                        'mains.date_start',
                        'mains.date_finish',
                        'mains.ignore_row',
                    ])
                    ->with('task:id,name'),
            ]);

        if ($technicianQuery !== '') {
            $tokens = preg_split('/\s+/u', $technicianQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $query->whereHas('user', function ($userQuery) use ($tokens): void {
                foreach ($tokens as $token) {
                    $userQuery->where('name', 'like', '%' . $this->escapeLike($token) . '%');
                }
            });
        }

        if ($customerQuery !== '') {
            $like = '%' . $this->escapeLike($customerQuery) . '%';
            $query->whereHas('customer', fn ($customer) => $customer->where('name', 'like', $like));
        }

        if ($instructionQuery !== '') {
            $like = '%' . $this->escapeLike($instructionQuery) . '%';
            $query->whereHas('instruction', fn ($instruction) => $instruction->where('name', 'like', $like));
        }

        if ($shopRolesOnly) {
            $query->whereHas('user.role', fn ($role) => $role->whereIn('name', ['Technician', 'Team Leader']));
        }

        $completedTaskNames = collect(config('workorders.completed_task_names', ['Completed']))
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->values()
            ->all();
        if ($completedTaskNames === []) {
            $completedTaskNames = ['Completed'];
        }

        if ($status !== 'all') {
            $completionFilter = function ($main) use ($completedTaskNames): void {
                $main->whereNotNull('task_id')
                    ->where(function ($row): void {
                        $row->where('ignore_row', false)->orWhereNull('ignore_row');
                    })
                    ->whereNotNull('date_finish')
                    ->whereHas('task', fn ($task) => $task->whereIn('name', $completedTaskNames));
            };

            if ($status === 'active') {
                $query->whereDoesntHave('main', $completionFilter);
            } else {
                $query->whereHas('main', $completionFilter);
            }
        }

        $workorders = $query->get();
        $filters = [
            'technician_query' => $technicianQuery !== '' ? $technicianQuery : null,
            'customer_query' => $customerQuery !== '' ? $customerQuery : null,
            'instruction_query' => $instructionQuery !== '' ? $instructionQuery : null,
            'status' => $status,
            'assignee_roles' => $shopRolesOnly ? ['Technician', 'Team Leader'] : null,
            'drafts_included' => false,
            'deleted_included' => false,
        ];

        if ($mode === 'summary') {
            return $this->summaryResult($workorders, $filters);
        }

        return $this->turnaroundResult($workorders, $filters, $args);
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'getWorkorderStatistics',
            'description' => 'System Admin-only read-only workorder statistics. Use summary to count all, active, or completed workorders for a technician/customer intersection and group them by instruction such as Overhaul or Repair. Team Leaders may also be assigned shop technicians. Use turnaround for top X longest or shortest workorders by calendar days from Workorder Open Date to the latest non-ignored Submitted for Final Inspection task date.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'mode' => [
                        'type' => 'string',
                        'enum' => ['summary', 'turnaround'],
                        'description' => 'summary = counts and instruction breakdown; turnaround = elapsed-time ranking.',
                    ],
                    'technician_query' => [
                        'type' => 'string',
                        'description' => 'Optional technician name fragment. Combine with customer_query as an intersection.',
                    ],
                    'customer_query' => [
                        'type' => 'string',
                        'description' => 'Optional customer name fragment. Combine with technician_query as an intersection.',
                    ],
                    'instruction_query' => [
                        'type' => 'string',
                        'description' => 'Optional instruction fragment such as Overhaul, Repair, Test or Inspect.',
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['all', 'active', 'completed'],
                        'description' => 'Optional WO completion status. active means there is no finished, non-ignored Completed task. Default: all.',
                    ],
                    'shop_roles_only' => [
                        'type' => 'boolean',
                        'description' => 'Use true for statistics about technicians as a group. Includes assignees with role Technician or Team Leader, because Team Leaders also carry technician workorders.',
                    ],
                    'ranking' => [
                        'type' => 'string',
                        'enum' => ['longest', 'shortest'],
                        'description' => 'For turnaround mode: longest by default, or shortest.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'For turnaround mode: top X rows (default 10, minimum 1, maximum 50).',
                    ],
                ],
                'required' => ['mode'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function summaryResult(Collection $workorders, array $filters): array
    {
        return [
            'ok' => true,
            'mode' => 'summary',
            'filters' => $filters,
            'total_workorders' => $workorders->count(),
            'instruction_counts' => $this->countByLabel(
                $workorders,
                fn (Workorder $workorder): string => trim((string) ($workorder->instruction?->name ?? '')) ?: 'Unspecified'
            ),
            'technician_counts' => $this->countByLabel(
                $workorders,
                fn (Workorder $workorder): string => trim((string) ($workorder->user?->selection_name ?? '')) ?: 'Unassigned'
            ),
            'customer_counts' => $this->countByLabel(
                $workorders,
                fn (Workorder $workorder): string => trim((string) ($workorder->customer?->name ?? '')) ?: 'Unspecified'
            ),
            'instruction_for_model' => 'Explain that total_workorders is the number of non-draft, non-deleted workorders matching all supplied filters, including the requested active/completed status. Report the instruction breakdown (Overhaul, Repair, etc.). Team Leader assignees count as shop technicians when assignee_roles is present. If a technician or customer fragment matched more than one name, show that breakdown instead of pretending it was one exact match. Do not call workorders contracts unless the user used that word; clarify that the count is workorders.',
        ];
    }

    private function turnaroundResult(Collection $workorders, array $filters, array $args): array
    {
        $limit = max(1, min(self::MAX_LIMIT, (int) ($args['limit'] ?? self::DEFAULT_LIMIT)));
        $ranking = Str::lower(trim((string) ($args['ranking'] ?? 'longest')));
        if (! in_array($ranking, ['longest', 'shortest'], true)) {
            $ranking = 'longest';
        }

        $missingOpenDate = 0;
        $missingFinalSubmission = 0;
        $submissionBeforeOpen = 0;

        $eligible = $workorders->map(function (Workorder $workorder) use (&$missingOpenDate, &$missingFinalSubmission, &$submissionBeforeOpen): ?array {
            if ($workorder->open_at === null) {
                $missingOpenDate++;
                return null;
            }

            $submissionDate = $this->latestFinalSubmissionDate($workorder->main);
            if ($submissionDate === null) {
                $missingFinalSubmission++;
                return null;
            }

            $openDate = Carbon::parse($workorder->open_at)->startOfDay();
            $submittedDate = Carbon::parse($submissionDate)->startOfDay();
            if ($submittedDate->lt($openDate)) {
                $submissionBeforeOpen++;
                return null;
            }

            return [
                'workorder_number' => (int) $workorder->number,
                'open_url' => route('mains.show', $workorder->id),
                'technician' => $workorder->user?->selection_name,
                'customer' => $workorder->customer?->name,
                'instruction' => $workorder->instruction?->name,
                'open_date' => format_project_date($openDate),
                'submitted_for_final_inspection_date' => format_project_date($submittedDate),
                'duration_days' => $openDate->diffInDays($submittedDate),
            ];
        })->filter()->values();

        $sorted = $eligible->sort(function (array $left, array $right) use ($ranking): int {
            $comparison = $left['duration_days'] <=> $right['duration_days'];
            if ($comparison === 0) {
                $comparison = $left['workorder_number'] <=> $right['workorder_number'];
            }

            return $ranking === 'longest' ? -$comparison : $comparison;
        })->values();

        $durations = $eligible->pluck('duration_days');

        return [
            'ok' => true,
            'mode' => 'turnaround',
            'filters' => $filters,
            'ranking' => $ranking,
            'requested_limit' => $limit,
            'matching_workorders' => $workorders->count(),
            'eligible_workorders' => $eligible->count(),
            'average_days' => $durations->isEmpty() ? null : round((float) $durations->average(), 1),
            'median_days' => $durations->isEmpty() ? null : round((float) $durations->median(), 1),
            'omitted' => [
                'missing_open_date' => $missingOpenDate,
                'missing_final_submission_date' => $missingFinalSubmission,
                'submission_before_open_date' => $submissionBeforeOpen,
            ],
            'workorders' => $sorted->take($limit)->all(),
            'duration_basis' => 'Calendar days from Workorder Open Date to the latest non-ignored task date whose name contains both "submitted" and "final". The task stores a date, not a time of day.',
            'instruction_for_model' => 'Format every ranked row as [WO <workorder_number>](open_url) — technician — customer — instruction — <duration_days> calendar days (<open_date> → <submitted_for_final_inspection_date>). State whether this is the longest or shortest ranking. Mention omitted-data counts when non-zero. Never expose internal ids or bare URLs.',
        ];
    }

    private function latestFinalSubmissionDate(Collection $mainRows): ?Carbon
    {
        $row = $mainRows
            ->filter(function (Main $main): bool {
                if ($main->ignore_row || ($main->date_finish === null && $main->date_start === null)) {
                    return false;
                }

                $name = Str::lower(trim((string) $main->task?->name));

                return Str::contains($name, 'submitted') && Str::contains($name, 'final');
            })
            ->sortByDesc(fn (Main $main): string => ($main->date_finish ?? $main->date_start)?->format('Y-m-d') ?? '')
            ->first();

        return $row?->date_finish ?? $row?->date_start;
    }

    private function countByLabel(Collection $workorders, callable $label): array
    {
        return $workorders
            ->groupBy($label)
            ->map(fn (Collection $items, string $name): array => [
                'name' => $name,
                'workorders' => $items->count(),
            ])
            ->sort(function (array $left, array $right): int {
                $countComparison = $right['workorders'] <=> $left['workorders'];

                return $countComparison !== 0
                    ? $countComparison
                    : strcasecmp($left['name'], $right['name']);
            })
            ->values()
            ->all();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

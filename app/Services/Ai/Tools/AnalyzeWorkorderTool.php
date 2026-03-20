<?php

namespace App\Services\Ai\Tools;

use App\Models\Workorder;
use App\Models\User;

class AnalyzeWorkorderTool
{
    public function run(User $user, array $args): array
    {
        $workorderId = (int)($args['workorder_id'] ?? 0);

        // Task progress lives on `mains` (Main rows), not a Workorder::tasks relation.
        $workorder = Workorder::withDrafts()
            ->with([
                'main.task.generalTask',
            ])
            ->find($workorderId);

        if (! $workorder) {
            return [
                'ok' => false,
                'message' => 'Workorder not found.',
            ];
        }

        if (! $user->can('workorders.view', $workorder)) {
            return [
                'ok' => false,
                'message' => 'You do not have permission to view this workorder.',
            ];
        }

        $mains = $workorder->main->filter(fn ($m) => ! empty($m->task_id));

        $openTasks = $mains
            ->filter(fn ($m) => ! $m->ignore_row && empty($m->date_finish))
            ->count();

        $completedMains = $mains->filter(fn ($m) => ! $m->ignore_row && ! empty($m->date_finish));

        $completed_tasks = $completedMains
            ->map(function ($m) {
                $gt = $m->task?->generalTask?->name;
                $name = $m->task?->name;

                return [
                    'general_task' => $gt,
                    'task' => $name,
                    'date_finish' => $m->date_finish?->format('Y-m-d'),
                ];
            })
            ->values()
            ->all();

        $open_task_names = $mains
            ->filter(fn ($m) => ! $m->ignore_row && empty($m->date_finish))
            ->map(fn ($m) => [
                'general_task' => $m->task?->generalTask?->name,
                'task' => $m->task?->name,
            ])
            ->values()
            ->all();

        $hasApprove = ! empty($workorder->approve_at);

        $reasons = [];

        if ($openTasks > 0) {
            $reasons[] = "{$openTasks} open tasks";
        }

        if (! $hasApprove) {
            $reasons[] = 'approve is missing';
        }

        // Photos in any section are never a blocker for closing a workorder.

        return [
            'ok' => true,
            'workorder' => [
                'id' => $workorder->id,
                'number' => $workorder->number,
                'status' => $workorder->status ?? null,
            ],
            'analysis' => [
                'open_tasks' => $openTasks,
                'open_task_rows' => $open_task_names,
                'completed_tasks' => $completed_tasks,
                'completed_task_rows_count' => count($completed_tasks),
                'has_approve' => $hasApprove,
                'reasons' => $reasons,
                'can_be_completed' => count($reasons) === 0,
                'note' => 'Missing photos in any section does not prevent closing the workorder.',
            ],
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'analyzeWorkorder',
            'description' => 'Analyze workorder progress: open vs finished task rows (from mains), approve. Photos are informational only and never block completion. Use for which tasks are done or what blocks closing.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'workorder_id' => [
                        'type' => 'integer',
                        'description' => 'Workorder ID',
                    ],
                ],
                'required' => ['workorder_id'],
                'additionalProperties' => false,
            ],
        ];
    }
}

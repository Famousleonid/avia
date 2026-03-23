<?php

namespace App\Services\Ai\Tools;

use App\Models\GeneralTask;
use App\Models\Task;
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

        $tasks = Task::with('generalTask')
            ->orderBy('general_task_id')
            ->orderBy('name')
            ->get();

        // There can be legacy duplicates; use latest row per task for state.
        $mainsByTaskId = $workorder->main
            ->filter(fn ($m) => !empty($m->task_id))
            ->groupBy('task_id')
            ->map(fn ($rows) => $rows->sortByDesc('id')->first());

        $taskRows = $tasks->map(function (Task $task) use ($mainsByTaskId) {
            $main = $mainsByTaskId->get($task->id);
            $ignored = (bool)($main?->ignore_row ?? false);
            $dateStart = $main?->date_start?->format('Y-m-d');
            $dateFinish = $main?->date_finish?->format('Y-m-d');
            $isClosed = !$ignored && !empty($dateFinish);
            $isOpen = !$ignored && empty($dateFinish);

            return [
                'general_task_id' => $task->general_task_id,
                'general_task' => $task->generalTask?->name,
                'task_id' => $task->id,
                'task' => $task->name,
                'ignored' => $ignored,
                'date_start' => $dateStart,
                'date_finish' => $dateFinish,
                'is_closed' => $isClosed,
                'is_open' => $isOpen,
            ];
        });

        $tasksIgnored = $taskRows->where('ignored', true)->count();
        $effectiveRows = $taskRows->where('ignored', false)->values();
        $tasksTotal = $effectiveRows->count();          // total excluding ignored
        $tasksClosed = $effectiveRows->where('is_closed', true)->count();
        $tasksOpen = $effectiveRows->where('is_open', true)->count();

        $completedTasks = $effectiveRows
            ->where('is_closed', true)
            ->map(fn ($r) => [
                'general_task' => $r['general_task'],
                'task' => $r['task'],
                'date_finish' => $r['date_finish'],
            ])
            ->values()
            ->all();

        $openTaskRows = $effectiveRows
            ->where('is_open', true)
            ->map(fn ($r) => [
                'general_task' => $r['general_task'],
                'task' => $r['task'],
            ])
            ->values()
            ->all();

        $tasksByGeneralId = $tasks->groupBy('general_task_id');

        $generalTaskStats = $taskRows
            ->groupBy('general_task_id')
            ->map(function ($rows) {
                $rows = $rows->values();
                $active = $rows->where('ignored', false)->values();
                $openRows = $active->where('is_open', true)->values();

                return [
                    'general_task' => $rows->first()['general_task'] ?? null,
                    'tasks_total' => $active->count(), // excluding ignored
                    'tasks_closed' => $active->where('is_closed', true)->count(),
                    'tasks_open' => $openRows->count(),
                    'tasks_ignored' => $rows->where('ignored', true)->count(),
                    'open_tasks' => $openRows->map(fn ($r) => $r['task'])->all(),
                ];
            })
            ->values()
            ->all();

        // Status / step: first "general task" stage (by sort_order) that still has an open non-ignored task row.
        $generalTasksOrdered = GeneralTask::orderBy('sort_order')->orderBy('id')->get();
        $currentStageName = null;
        $currentStageSortOrder = null;
        foreach ($generalTasksOrdered as $gt) {
            $rowsInGt = $tasksByGeneralId->get($gt->id, collect());
            if ($rowsInGt->isEmpty()) {
                continue;
            }
            $stageHasOpen = false;
            foreach ($rowsInGt as $row) {
                $main = $mainsByTaskId->get($row->id);
                if (! $main) {
                    $stageHasOpen = true;
                    break;
                }
                if ($main->ignore_row) {
                    continue;
                }
                if (empty($main->date_finish)) {
                    $stageHasOpen = true;
                    break;
                }
            }
            if ($stageHasOpen) {
                $currentStageName = $gt->name;
                $currentStageSortOrder = $gt->sort_order;
                break;
            }
        }

        $workorderClosed = $workorder->isDone();
        $hasApprove = !empty($workorder->approve_at);

        $reasons = [];
        if (!$workorderClosed) {
            $reasons[] = 'Complete task is not finished (isDone() is false).';
        }
        if ($tasksOpen > 0) {
            $reasons[] = "{$tasksOpen} task(s) are still open out of {$tasksTotal} active task(s).";
        }

        return [
            'ok' => true,
            'workorder' => [
                'number' => $workorder->number,
            ],
            'analysis' => [
                'workorder_closed' => $workorderClosed,
                'close_rule' => 'Workorder is considered closed only by isDone() (Complete task finished).',
                'status_step' => [
                    'current_general_stage_name' => $currentStageName,
                    'current_general_stage_sort_order' => $currentStageSortOrder,
                    'definition' => 'First general task stage (general_tasks.sort_order) that still has at least one open non-ignored task (no finish date). Photos do not affect this.',
                ],
                'tasks_total' => $tasksTotal,
                'tasks_closed' => $tasksClosed,
                'tasks_open' => $tasksOpen,
                'tasks_ignored' => $tasksIgnored,
                'open_of_total' => "{$tasksOpen} / {$tasksTotal}",
                'open_task_rows' => $openTaskRows,
                'completed_tasks' => $completedTasks,
                'completed_task_rows_count' => count($completedTasks),
                'general_tasks' => $generalTaskStats,
                'has_approve' => $hasApprove,
                'reasons' => $reasons,
                'can_be_completed' => $workorderClosed,
                'note' => 'Task completion is defined by date_finish. Ignored rows are excluded from totals. Photos do not affect workorder status or closed state.',
            ],
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'analyzeWorkorder',
            'description' => 'Analyze workorder: closed only via isDone(); task done = finish date; ignored rows excluded; status step = first unfinished general stage by sort_order. Open/total counts. Do not tell the user internal DB ids — only WO number.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'workorder_id' => [
                        'type' => 'integer',
                        'description' => 'Internal workorder id (from context; never repeat to end users)',
                    ],
                ],
                'required' => ['workorder_id'],
                'additionalProperties' => false,
            ],
        ];
    }
}

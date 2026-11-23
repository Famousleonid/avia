<?php

namespace App\Services\Workorders;

use App\Models\GeneralTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class WorkorderVisibilityService
{
    private const TECHNICIAN_SUBMISSIONS = [
        'disassembly' => 'wo submitted to inspection',
        'assembly' => 'wo submitted for final inspection',
    ];

    public function usesSubmissionDatesOnly(User $user): bool
    {
        return $user->roleIs(['Technician', 'Team Leader']);
    }

    public function desktopGeneralTasksFor(User $user): Collection
    {
        if ($user->roleIs(['Admin', 'Manager'])) {
            return $this->visibleGeneralTasksFor($user);
        }

        if (!$this->usesSubmissionDatesOnly($user)) {
            return collect();
        }

        return GeneralTask::inCatalog()->whereIn('technician_stage', GeneralTask::TECHNICIAN_STAGES)
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public function canEditDesktopTask(User $user, ?Task $task): bool
    {
        if ($user->roleIs(['Admin', 'Manager'])) {
            return true;
        }

        $stage = $task?->generalTask;

        return $this->usesSubmissionDatesOnly($user) && $stage && $stage->catalog_removed_at === null
            && isset(self::TECHNICIAN_SUBMISSIONS[$stage->technician_stage])
            && $this->desktopTasksFor($user, collect([$stage]))->contains('id', $task->id);
    }

    public function desktopTasksFor(User $user, Collection $stages): Collection
    {
        if ($stages->isEmpty()) {
            return collect();
        }

        $tasks = Task::active()->whereIn('general_task_id', $stages->pluck('id'))->inDisplayOrder()->get();
        if (!$this->usesSubmissionDatesOnly($user)) {
            return $tasks;
        }

        $stagesById = $stages->keyBy('id');

        return $tasks->filter(function (Task $task) use ($stagesById): bool {
            $stage = $stagesById->get($task->general_task_id);
            $expected = self::TECHNICIAN_SUBMISSIONS[$stage->technician_stage] ?? null;
            $name = strtolower(preg_replace('/^\d+[.)]\s*/', '', trim($task->name)));

            return $expected === $name;
        })->unique('general_task_id')->values();
    }

    public function visibleGeneralTasksFor(?User $user): Collection
    {
        $generalTasks = GeneralTask::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Keep positional visibility rules tied to the original stage order.
        return $this->filterVisibleGeneralTasks($generalTasks, $user)->whereNull('catalog_removed_at')->values();
    }

    public function filterVisibleGeneralTasks(Collection $generalTasks, ?User $user): Collection
    {
        $generalTasks = $generalTasks->values();

        if (! $this->userHasConfiguredRole($user, 'roles_hide_general_task_positions')) {
            return $generalTasks;
        }

        $hiddenPositions = collect(config('workorders.hidden_general_task_positions', []))
            ->map(fn ($position): int => (int) $position)
            ->filter(fn (int $position): bool => $position > 0)
            ->values();

        $hiddenNames = collect(config('workorders.hidden_general_task_names', []))
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->values();

        if ($hiddenPositions->isEmpty() && $hiddenNames->isEmpty()) {
            return $generalTasks;
        }

        return $generalTasks
            ->reject(fn ($generalTask, int $index): bool => $hiddenPositions->contains($index + 1)
                || $hiddenNames->contains(trim((string) $generalTask->name)))
            ->values();
    }

    public function filterVisibleMainsTasks(Collection $tasks, ?User $user): Collection
    {
        if (! $this->userHasConfiguredRole($user, 'roles_hide_task_names_in_mains')) {
            return $tasks->values();
        }

        $hiddenTaskNames = $this->taskNames('hidden_task_names_in_mains');

        if ($hiddenTaskNames === []) {
            return $tasks->values();
        }

        return $tasks
            ->reject(fn (Task $task): bool => in_array($task->name, $hiddenTaskNames, true))
            ->values();
    }

    public function addListCompletion(Builder $query, ?User $user): void
    {
        if ($user && $this->usesSubmissionDatesOnly($user)) {
            $query->withExists(['main as final_inspection_submitted' => fn (Builder $main) => $this->constrainToFinalSubmission($main)]);
        }
    }

    public function applyActiveFilterForUser(Builder $query, ?User $user): void
    {
        $query->notCompleted();

        if ($user && $this->usesSubmissionDatesOnly($user)) {
            $query->whereDoesntHave('main', fn (Builder $main) => $this->constrainToFinalSubmission($main));
        }
    }

    private function constrainToFinalSubmission(Builder $main): void
    {
        $taskNames = $this->taskNames('submitted_final_inspection_task_names');
        $containsAllGroups = $this->taskContainsAllGroups('submitted_final_inspection_task_contains_all');
        if ($taskNames === [] && $containsAllGroups === []) {
            $main->whereRaw('1 = 0');
            return;
        }

        $main->where(fn (Builder $row) => $row->where('ignore_row', false)->orWhereNull('ignore_row'))
            ->whereNotNull('date_finish')
            ->where('mains.id', function (QueryBuilder $current) use ($taskNames, $containsAllGroups): void {
                $current->select('submission.id')
                    ->from('mains as submission')
                    ->join('tasks as submission_task', 'submission_task.id', '=', 'submission.task_id')
                    ->whereColumn('submission.workorder_id', 'mains.workorder_id')
                    ->where(function (QueryBuilder $matches) use ($taskNames, $containsAllGroups): void {
                        if ($taskNames !== []) {
                            $matches->whereIn('submission_task.name', $taskNames);
                        }

                        foreach ($containsAllGroups as $words) {
                            $matches->orWhere(function (QueryBuilder $containsAll) use ($words): void {
                                foreach ($words as $word) {
                                    $containsAll->where('submission_task.name', 'like', '%' . $word . '%');
                                }
                            });
                        }
                    })
                    // A cleared current submission overrides an older archived date.
                    ->orderByRaw('submission_task.archived_at IS NOT NULL')
                    ->orderBy('submission_task.sort_order')
                    ->orderBy('submission_task.name')
                    ->orderBy('submission_task.id')
                    ->orderByDesc('submission.id')
                    ->limit(1);
            });
    }

    private function taskNames(string $configKey): array
    {
        return collect(config('workorders.' . $configKey, []))
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function taskContainsAllGroups(string $configKey): array
    {
        return collect(config('workorders.' . $configKey, []))
            ->map(function ($words): array {
                return collect((array) $words)
                    ->map(fn ($word): string => trim((string) $word))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter()
            ->values()
            ->all();
    }

    private function userHasConfiguredRole(?User $user, string $configKey): bool
    {
        if (! $user) {
            return false;
        }

        $roles = collect(config('workorders.' . $configKey, []))
            ->map(fn ($role): string => trim((string) $role))
            ->filter()
            ->values()
            ->all();

        return $roles !== [] && $user->roleIs($roles);
    }
}

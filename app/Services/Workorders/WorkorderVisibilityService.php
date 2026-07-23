<?php

namespace App\Services\Workorders;

use App\Models\GeneralTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WorkorderVisibilityService
{
    public function visibleGeneralTasksFor(?User $user): Collection
    {
        $generalTasks = GeneralTask::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->filterVisibleGeneralTasks($generalTasks, $user);
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

    public function applyActiveFilterForUser(Builder $query, ?User $user): void
    {
        $this->excludeFinishedTaskNames($query, $this->taskNames('completed_task_names'));

        if ($this->userHasConfiguredRole($user, 'roles_hide_submitted_final_inspection_from_active')) {
            $this->excludeFinishedTasks(
                $query,
                $this->taskNames('submitted_final_inspection_task_names'),
                $this->taskContainsAllGroups('submitted_final_inspection_task_contains_all')
            );
        }
    }

    private function excludeFinishedTaskNames(Builder $query, array $taskNames): void
    {
        $this->excludeFinishedTasks($query, $taskNames);
    }

    private function excludeFinishedTasks(Builder $query, array $taskNames, array $containsAllGroups = []): void
    {
        if ($taskNames === []) {
            $containsAllGroups = array_values(array_filter($containsAllGroups));

            if ($containsAllGroups === []) {
                return;
            }
        }

        $query->whereDoesntHave('main', function (Builder $main) use ($taskNames, $containsAllGroups): void {
            $main->whereNotNull('task_id')
                ->where(function (Builder $notIgnored): void {
                    $notIgnored->where('ignore_row', false)
                        ->orWhereNull('ignore_row');
                })
                ->whereNotNull('date_finish')
                ->whereHas('task', function (Builder $task) use ($taskNames, $containsAllGroups): void {
                    $task->where(function (Builder $matches) use ($taskNames, $containsAllGroups): void {
                        if ($taskNames !== []) {
                            $matches->whereIn('name', $taskNames);
                        }

                        foreach ($containsAllGroups as $words) {
                            $matches->orWhere(function (Builder $containsAll) use ($words): void {
                                foreach ($words as $word) {
                                    $containsAll->where('name', 'like', '%' . $word . '%');
                                }
                            });
                        }
                    });
                });
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

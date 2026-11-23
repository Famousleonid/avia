<?php

namespace App\Services\Workorders;

use App\Models\Main;
use App\Models\Task;
use App\Models\Workorder;
use App\Models\WorkStage;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WorkStageService
{
    // Evaluate in workflow order from last to first. Older workorders can be missing intermediate dates.
    private const MILESTONES = [
        'final_inspection' => ['final inspection'],
        'final_assembly_test' => ['wo submitted for final inspection', 'submitted for final inspection'],
        'ovh_in_process' => [], // Uses workorders.approve_at.
        'wait_approval' => ['in quotation'],
        'in_quotation' => ['wait records'],
        'wait_records' => ['post disassembly inspection'],
    ];

    public function workflowStages(): Collection
    {
        return WorkStage::whereNotNull('workflow_key')->orderBy('sort_order')->orderBy('id')
            ->get(['workflow_key', 'name', 'text_color'])->keyBy('workflow_key');
    }

    /** @param Collection<int, Workorder> $workorders */
    public function keysForWorkorders(Collection $workorders): Collection
    {
        if ($workorders->isEmpty()) {
            return collect();
        }

        $stageByTaskName = [];
        foreach (self::MILESTONES as $stage => $names) {
            foreach ($names as $name) {
                $stageByTaskName[$name] = $stage;
            }
        }

        $taskStages = Task::query()->inDisplayOrder()->get(['id', 'name', 'archived_at'])
            ->sortBy(fn (Task $task) => $task->archived_at !== null)
            ->mapWithKeys(function (Task $task) use ($stageByTaskName): array {
                $name = strtolower(preg_replace('/^\d+[.)]\s*/', '', trim($task->name)));
                $stage = $stageByTaskName[preg_replace('/\s+/', ' ', $name)] ?? null;

                return $stage === null ? [] : [$task->id => $stage];
            });

        $rows = $taskStages->isEmpty() ? collect() : Main::query()
            ->whereIntegerInRaw('workorder_id', $workorders->pluck('id')->all())
            ->whereIn('task_id', $taskStages->keys())
            ->orderByDesc('id')->get(['workorder_id', 'task_id', 'date_finish', 'ignore_row'])
            ->groupBy('workorder_id');

        $keys = $workorders->mapWithKeys(function (Workorder $workorder) use ($taskStages, $rows): array {
            $byTask = $rows->get($workorder->id, collect())->unique('task_id')->keyBy('task_id');
            $finished = ['ovh_in_process' => $workorder->approve_at !== null];
            foreach ($taskStages as $taskId => $stage) {
                $row = $byTask->get($taskId);
                if ($row && !array_key_exists($stage, $finished)) {
                    // A cleared current row takes precedence over an archived row for the same milestone.
                    $finished[$stage] = !$row->ignore_row && $row->date_finish !== null;
                }
            }

            foreach (array_keys(self::MILESTONES) as $stage) {
                if ($finished[$stage] ?? false) {
                    return [$workorder->id => $stage];
                }
            }

            return [$workorder->id => null];
        });

        $withoutStage = $keys->filter(fn (?string $stage) => $stage === null);
        if ($withoutStage->isNotEmpty()) {
            $photoCollections = array_diff(array_keys(config('workorder_media.groups', ['photos' => 'Photos'])), ['received']);
            $withPhotos = Media::query()
                ->where('model_type', $workorders->first()->getMorphClass())
                ->whereIntegerInRaw('model_id', $withoutStage->keys()->all())
                ->whereIn('collection_name', $photoCollections)
                ->where('mime_type', 'like', 'image/%')
                ->distinct()
                ->pluck('model_id');

            foreach ($withPhotos as $id) {
                $keys->put($id, 'disassembly');
            }
        }

        return $keys;
    }
}

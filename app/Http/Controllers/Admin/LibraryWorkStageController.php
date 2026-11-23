<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Task;
use App\Models\WorkStage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryWorkStageController extends Controller
{
    public function index(Request $request)
    {
        $section = $request->query('section');
        abort_unless(in_array($section, [null, 'tasks', 'work-stages'], true), 404);
        $data = [];

        if ($section !== 'work-stages') {
            $groups = GeneralTask::inCatalog()->orderBy('sort_order')->orderBy('id')->get();
            $tasks = Task::where(function ($query) use ($groups): void {
                $query->whereIn('general_task_id', $groups->modelKeys())->orWhereNull('general_task_id');
            })->withExists('mainRows')->inDisplayOrder()->get();
            $data['groups'] = $groups;
            $data['tasksByGroup'] = $tasks->whereNull('archived_at')->groupBy('general_task_id');
            $data['archivedTasks'] = $tasks->whereNotNull('archived_at');
        }
        if ($section !== 'tasks') {
            $data['workStages'] = WorkStage::orderBy('sort_order')->orderBy('id')->get();
        }

        $view = $section === null ? 'index' : $section;

        return response()->view('admin.library.work-stages.'.$view, $data)->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        WorkStage::create($this->validatedStage($request));

        return response()->noContent();
    }

    public function update(Request $request, WorkStage $workStage)
    {
        $workStage->update($this->validatedStage($request, $workStage));

        return response()->noContent();
    }

    public function destroy(WorkStage $workStage)
    {
        abort_if($workStage->workflow_key !== null, 409, 'This stage is used by the workorder workflow.');
        $workStage->delete();

        return response()->noContent();
    }

    private function validatedStage(Request $request, ?WorkStage $workStage = null): array
    {
        $rules = ['text_color' => ['nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/']];
        $colorOnly = $workStage && $request->has('text_color') && !$request->hasAny(['name', 'sort_order']);
        if (!$colorOnly) {
            $rules['name'] = ['required', 'string', 'max:250', Rule::unique('work_stages')->ignore($workStage?->id)];
            $rules['sort_order'] = ['required', 'integer', 'between:0,4294967295'];
        }

        return $request->validate($rules);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{

    public function index()
    {
        return redirect()->route('library.work-stages.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name'            => ['required', 'string', 'max:255'],
                'general_task_id' => ['required', Rule::exists('general_tasks', 'id')->whereNull('catalog_removed_at')],
                'task_has_start_date' => ['nullable', 'boolean'],
                'sort_order' => ['sometimes', 'required', 'integer', 'between:0,4294967295'],
            ],
            [
                'general_task_id.required' => 'Please select a general task.',
                'general_task_id.exists'   => 'Selected general task does not exist.',
            ]
        );

        $validated['task_has_start_date'] = $request->boolean('task_has_start_date');
        DB::transaction(function () use ($validated): void {
            $group = GeneralTask::inCatalog()->lockForUpdate()->findOrFail($validated['general_task_id']);
            $validated['sort_order'] ??= min(4294967295, (int) $group->tasks()->max('sort_order') + 10);
            Task::create($validated);
        });

        if ($request->expectsJson()) return response()->noContent();

        return redirect()->route('tasks.index')->with('status', 'Task created');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name'              => ['required','string','max:255'],
            'general_task_id'   => ['sometimes', 'required', Rule::exists('general_tasks', 'id')->whereNull('catalog_removed_at')],
            'task_has_start_date' => ['nullable', 'boolean'],
            'sort_order' => ['sometimes', 'required', 'integer', 'between:0,4294967295'],
        ]);

        $validated['task_has_start_date'] = $request->boolean('task_has_start_date');

        DB::transaction(function () use ($task, $validated): void {
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_if($task->archived_at !== null, 409, 'Restore this task before editing.');
            if (array_key_exists('general_task_id', $validated) && (int) $task->general_task_id !== (int) $validated['general_task_id']) {
                abort_if($task->mainRows()->exists(), 409, 'Tasks with workorder history must stay in their original general task.');
                GeneralTask::inCatalog()->lockForUpdate()->findOrFail($validated['general_task_id']);
            }
            $task->update($validated);
        });

        if ($request->expectsJson()) return response()->noContent();

        return redirect()->route('tasks.index')->with('status', 'Task updated');
    }

    public function destroy(Request $request, Task $task)
    {
        DB::transaction(function () use ($task): void {
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_if($task->isRequiredByWorkflow(), 409, 'This task is used by the workorder workflow.');
            abort_if($task->mainRows()->exists(), 409, 'This task has workorder history.');
            $task->delete();
        });

        if ($request->expectsJson()) return response()->noContent();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function archive(Task $task)
    {
        DB::transaction(function () use ($task): void {
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_if($task->isRequiredByWorkflow(), 409, 'This task is used by the workorder workflow.');
            if ($task->archived_at === null) {
                $task->forceFill(['archived_at' => now()])->save();
            }
        });

        return response()->noContent();
    }

    public function restore(Task $task)
    {
        DB::transaction(function () use ($task): void {
            if ($task->general_task_id !== null) {
                GeneralTask::inCatalog()->lockForUpdate()->findOrFail($task->general_task_id);
            }
            Task::whereKey($task->id)->whereNotNull('archived_at')->update(['archived_at' => null]);
        });

        return response()->noContent();
    }
}

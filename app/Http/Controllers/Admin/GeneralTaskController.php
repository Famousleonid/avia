<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\WorkorderGeneralTaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GeneralTaskController extends Controller
{
    public function index()
    {
        return redirect()->route('library.work-stages.index');
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:250', Rule::unique('general_tasks')->whereNull('catalog_removed_at')],
            'sort_order' => ['required', 'integer', 'between:0,4294967295'],
        ]);

        $generalTask = new GeneralTask($validated);
        $stageKey = strtolower($validated['name']);
        if (in_array($stageKey, GeneralTask::TECHNICIAN_STAGES, true)
            && !GeneralTask::where('technician_stage', $stageKey)->exists()) {
            $generalTask->technician_stage = $stageKey;
        }
        $generalTask->save();

        if ($request->expectsJson()) return response()->noContent();

        return redirect()->route('general-tasks.index')->with('success', 'General task created successfully.');
    }


    public function update(Request $request, GeneralTask $generalTask)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:250', Rule::unique('general_tasks')->whereNull('catalog_removed_at')->ignore($generalTask->id)],
            'sort_order' => ['required', 'integer', 'between:0,4294967295'],
        ]);

        DB::transaction(function () use ($generalTask, $validated): void {
            GeneralTask::inCatalog()->lockForUpdate()->findOrFail($generalTask->id)->update($validated);
        });

        if ($request->expectsJson()) return response()->noContent();

        return redirect()->route('general-tasks.index')->with('success', 'General task updated successfully.');
    }

    public function destroy(Request $request, GeneralTask $generalTask)
    {
        DB::transaction(function () use ($generalTask): void {
            $generalTask = GeneralTask::lockForUpdate()->findOrFail($generalTask->id);
            abort_if($generalTask->technician_stage !== null, 409, 'This stage is used in Technician Main.');
            abort_if($generalTask->tasks()->active()->exists(), 409, 'Archive or remove the active tasks in this group first.');
            if ($generalTask->tasks()->exists() || $generalTask->main()->exists()
                || WorkorderGeneralTaskStatus::where('general_task_id', $generalTask->id)->exists()) {
                if ($generalTask->catalog_removed_at === null) {
                    $generalTask->forceFill(['catalog_removed_at' => now()])->save();
                }
                return;
            }
            $generalTask->delete();
        });

        if ($request->expectsJson()) return response()->noContent();

        return redirect()->route('general-tasks.index')->with('success', 'General task deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }


    public function index()
    {
        $general_tasks = GeneralTask::orderBy('id')->get();
        $tasks = Task::with('generalTask')->orderBy('name')->get();
        $tasksByGeneral = $tasks->groupBy('general_task_id');

        $groups = $general_tasks->map(function ($gt) use ($tasksByGeneral) {
            return (object)[
                'id'    => $gt->id,
                'name'  => $gt->name,
                'tasks' => $tasksByGeneral->get($gt->id, collect()),
            ];
        });

        return view('admin.tasks.index', compact('groups','general_tasks','tasks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required','string','max:255'],
            'general_task_id'   => ['nullable','exists:general_tasks,id'],
        ]);

        Task::create($validated);

        return redirect()->route('tasks.index')->with('status', 'Task created');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name'              => ['required','string','max:255'],
            'general_task_id'   => ['nullable','exists:general_tasks,id'],
        ]);

        $task->update($validated);

        return redirect()->route('tasks.index')->with('status', 'Task updated');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}

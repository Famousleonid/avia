<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Task;
use App\Models\Workorder;
use Illuminate\Http\Request;

class MobileTaskController extends Controller
{
    public function tasks($workorder_id, Request $request)
    {

        $workorder = Workorder::with('generalTaskStatuses')->findOrFail($workorder_id);

        $general_tasks = GeneralTask::orderBy('sort_order')->orderBy('id')->get();

        $tasks = Task::whereIn('general_task_id', $general_tasks->pluck('id'))
            ->orderBy('general_task_id')
            ->orderBy('name')
            ->get();

        $tasksByGeneral = $tasks->groupBy('general_task_id');

        $mains = Main::with(['user', 'task'])
            ->where('workorder_id', $workorder_id)
            ->get();

        $mainsByTask = $mains->keyBy('task_id');

        $gtDoneMap = $workorder->generalTaskStatuses
            ->keyBy('general_task_id')
            ->map(fn($s) => (bool)$s->is_done)
            ->toArray();

        return view('mobile.pages.tasks', compact(
            'workorder',
            'general_tasks',
            'tasksByGeneral',
            'mainsByTask',
            'gtDoneMap'
        ));
    }

    public function getTasksByWorkorder(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],
        ]);

        $mains = Main::with(['user', 'task.generalTask'])
            ->where('workorder_id', $validated['workorder_id'])
            ->orderByRaw('date_start IS NULL')   // сначала с датой, потом пустые
            ->orderBy('date_start')
            ->orderBy('user_id')
            ->get();

        if ($mains->isEmpty()) {
            return response()->json([
                'html' => '<p class="text-secondary small mb-0">No tasks for this workorder yet.</p>',
            ]);
        }

        $rows = '';

        foreach ($mains as $main) {
            $technik = e($main->user->name ?? '-');
            $category = e($main->generalTask->name ?? '-');
            $taskName = e($main->task->name ?? '-');

            $startDisplay = $main->date_start ? $main->date_start->format('d-M-y') : '...';
            $finishDisplay = $main->date_finish ? $main->date_finish->format('d-M-y') : '...';

            $startValue = $main->date_start ? $main->date_start->format('Y-m-d') : '';
            $finishValue = $main->date_finish ? $main->date_finish->format('Y-m-d') : '';

            $startFilled = $main->date_start ? 'date-cell-filled' : 'date-cell-empty';
            $finishFilled = $main->date_finish ? 'date-cell-filled' : 'date-cell-empty';

            $startCheck = $main->date_start ? "<i class='bi bi-check2'></i>" : '';
            $finishCheck = $main->date_finish ? "<i class='bi bi-check2'></i>" : '';

            $rows .= "
            <tr>
                <td>{$technik}</td>
                <td>{$category} &rarr; {$taskName}</td>

                <td class='text-center'>
                    <div class='date-cell {$startFilled}'>
                        <span class='date-text'>{$startDisplay}</span>

                        <button type='button'
                                class='btn btn-sm date-calendar'
                                data-id='{$main->id}'
                                data-field='date_start'>
                            <i class='bi bi-calendar3'></i>
                        </button>
                        <input type='date'
                               class='date-picker-input'
                               data-id='{$main->id}'
                               data-field='date_start'
                               value='{$startValue}'>
                    </div>
                </td>

                <td class='text-center'>
                    <div class='date-cell {$finishFilled}'>
                        <span class='date-text'>{$finishDisplay}</span>

                        <button type='button'
                                class='btn btn-sm date-calendar'
                                data-id='{$main->id}'
                                data-field='date_finish'>
                            <i class='bi bi-calendar3'></i>
                        </button>
                        <input type='date'
                               class='date-picker-input'
                               data-id='{$main->id}'
                               data-field='date_finish'
                               value='{$finishValue}'>
                    </div>
                </td>
            </tr>
        ";
        }

        $html = "
        <div class='table-responsive mt-3'>
            <table class='table table-sm table-dark align-middle mb-0 tasks-table'>
                <thead>
                <tr>
                    <th>Technik</th>
                    <th>Task</th>
                    <th class='text-center'>Start</th>
                    <th class='text-center'>Finish</th>
                </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
        </div>
    ";

        return response()->json(['html' => $html]);
    }

    public function storeMain(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],
            'user_id' => ['required', 'exists:users,id'],
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        $task = Task::findOrFail($validated['task_id']);

        Main::create([
            'user_id' => $validated['user_id'],
            'workorder_id' => $validated['workorder_id'],
            'task_id' => $task->id,
            'general_task_id' => $task->general_task_id,
            'date_start' => null,
            'date_finish' => null,
            'description' => null,
        ]);

        return response()->json(['success' => true]);
    }
}

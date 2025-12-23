<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Material;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class MobileController extends Controller
{
    public function index()
    {

        $userId = Auth::id();

        $workorders = Workorder::with(['unit', 'media'])
            ->orderBy('number', 'desc')
            ->get();

        return view('mobile.pages.index',compact('workorders', 'userId'));
    }

    public function show(Workorder $workorder)
    {
        $workorder->load(['unit', 'media']);

        return view('mobile.pages.show', compact('workorder'));
    }

    public function profile()
    {
        $user = Auth::user();
        $teams = Team::all();

        return view('mobile.pages.profile', compact('user','teams'));
    }

    public function update_profile(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'phone' => 'nullable',
            'stamp' => 'required',
            'team_id' => 'required|exists:teams,id',
            'file' => 'nullable|image',
        ]);

        $user->update($request->only(['name', 'phone', 'stamp', 'team_id']));

        if ($request->hasFile('file')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($request->file('file'))->toMediaCollection('avatar');
        }

        return redirect()->route('mobile.profile')->with('success', 'Changes saved');
    }

    public function materials()
    {
        $user = Auth::user();
        $materials = Material::all();

        return view('mobile.pages.materials', compact('user', 'materials'));
    }

    public function updateMaterialDescription(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $material->description = $request->input('description', '');
        $material->save();

        return response()->json(['success' => true]);
    }

    public function components(Workorder $workorder)
    {
        $workorder->load([
            'unit',
            'media',
            'tdrs.component.media',        // ✅ компонент у Tdr
            'tdrs.tdrProcesses.processName',
        ]);

        // Собираем компоненты для этого воркордера
        $components = $workorder->tdrs
            ->filter(fn ($tdr) => $tdr->component)       // только Tdr с компонентом
            ->groupBy('component_id')                    // группируем по компоненту
            ->map(function ($group) {
                $first = $group->first();
                $component = $first->component;
                $component->processesForWorkorder = $group
                    ->flatMap->tdrProcesses           // собираем все tdrProcesses из группы
                    ->values();

                return $component;
            })
            ->values();

        return view('mobile.pages.components', compact('workorder', 'components'));
    }


    public function componentStore(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'ipl_num' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'eff_code' => 'nullable|string|max:100',
            'photo' => 'image|max:5120',
            'name' => 'required|string|max:255',
        ]);

        $workorder = Workorder::with('unit')->findOrFail($validated['workorder_id']);
        $manualId = optional($workorder->unit)->manual_id;

        if (!$manualId) {
            return redirect()->back()->withErrors(['manual' => 'Manual not found for selected workorder.']);
        }

        $component = new Component();
        $component->manual_id = $manualId;
        $component->ipl_num = $validated['ipl_num'];
        $component->part_number = $validated['part_number'];
        $component->eff_code = $validated['eff_code'];
        $component->name = $validated['name'];
        $component->save();

        if ($request->hasFile('photo')) {
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        return redirect()->back()->with('success', 'Component added successfully.');
    }


   public function changePassword(Request $request, $id)
   {
       $request->validate([
           'old_pass' => 'required',
           'password' => 'required|confirmed|min:3',
       ]);

       $user = User::findOrFail($id);

       if (!Hash::check($request->old_pass, $user->password)) {
           return redirect()->back()->with('error', 'The current password is incorrect');
       }

       $user->password = Hash::make($request->password);
       $user->save();

       return redirect()->back()->with('success', 'New password saved');
   }

    public function tasks($workorder_id, Request $request)
    {
        // ВАЖНО: в blade используется $workorder, значит передаём именно $workorder
        $workorder = Workorder::findOrFail($workorder_id);

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

        // зелёные/красные кнопки general_task
        $gtAllFinished = [];

        foreach ($general_tasks as $gt) {
            $taskIds = ($tasksByGeneral[$gt->id] ?? collect())->pluck('id');

            if ($taskIds->isEmpty()) {
                $gtAllFinished[$gt->id] = false;
                continue;
            }

            $gtAllFinished[$gt->id] = $taskIds->every(function ($taskId) use ($mainsByTask) {
                $main = $mainsByTask->get($taskId);
                if (!$main) return false;
                if ($main->ignore_row) return true;
                return !empty($main->date_finish);
            });
        }

        return view('mobile.pages.tasks', compact(
            'workorder',
            'general_tasks',
            'tasksByGeneral',
            'mainsByTask',
            'gtAllFinished'
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
            $technik  = e($main->user->name ?? '-');
            $category = e($main->generalTask->name ?? '-');
            $taskName = e($main->task->name ?? '-');

            $startDisplay  = $main->date_start  ? $main->date_start->format('d-M-y')  : '...';
            $finishDisplay = $main->date_finish ? $main->date_finish->format('d-M-y') : '...';

            $startValue  = $main->date_start  ? $main->date_start->format('Y-m-d')  : '';
            $finishValue = $main->date_finish ? $main->date_finish->format('Y-m-d') : '';

            $startFilled  = $main->date_start  ? 'date-cell-filled' : 'date-cell-empty';
            $finishFilled = $main->date_finish ? 'date-cell-filled' : 'date-cell-empty';

            $startCheck  = $main->date_start  ? "<i class='bi bi-check2'></i>"  : '';
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
            'user_id'      => ['required', 'exists:users,id'],
            'task_id'      => ['required', 'exists:tasks,id'],
        ]);

        $task = Task::findOrFail($validated['task_id']);

        Main::create([
            'user_id'         => $validated['user_id'],
            'workorder_id'    => $validated['workorder_id'],
            'task_id'         => $task->id,
            'general_task_id' => $task->general_task_id, // поле есть в таблице tasks
            'date_start'      => null,
            'date_finish'     => null,
            'description'     => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateMainDates(Request $request)
    {
        $validated = $request->validate([
            'main_id' => ['required', 'exists:mains,id'],
            'field'   => ['required', 'in:date_start,date_finish'],
            'value'   => ['nullable', 'date'],
        ]);

        $main = Main::findOrFail($validated['main_id']);

        $main->{$validated['field']} = $validated['value'] ?: null;
        $main->save();

        return response()->json(['success' => true]);
    }

}

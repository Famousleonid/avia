<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Necessary;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\Training;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;


class MainController extends Controller
{
    public function index()
    {
        return 1;
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],
            'task_id' => ['required', 'exists:tasks,id'],
            'date_start' => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
            'ignore_row' => ['nullable', 'boolean'],
        ]);

        $task = Task::with('generalTask')->findOrFail($data['task_id']);
        $hasStart = (bool)$task->task_has_start_date;
        $ignoreRow = $request->boolean('ignore_row');
        $hasStart = $request->has('date_start');
        $hasFinish = $request->has('date_finish');

        [$dateStart, $dateFinish] = (function () use ($data, $task, $ignoreRow, $hasStart, $hasFinish) {
            $resolved = Main::validateAndResolveDates($data, $task, null, $ignoreRow, $hasStart, $hasFinish);
            return [$resolved['date_start'], $resolved['date_finish']];
        })();


        $main = new Main();
        $main->workorder_id = $data['workorder_id'];
        $main->task_id = $task->id;
        $main->general_task_id = $task->general_task_id;
        $main->user_id = auth()->id();

        $main->date_start = $dateStart;
        $main->date_finish = $dateFinish;
        $main->ignore_row = $ignoreRow;

        $main->save();


        $wo = $main->workorder ?: Workorder::find($main->workorder_id);

        if ($wo) {
            $wo->recalcGeneralTaskStatuses($main->general_task_id);
            $wo->syncDoneByCompletedTask();
        }


        return back()->with('success', 'Record created.');
    }

    public function show(Workorder $workorder, Request $request)
    {
        // Если binding уже возвращает Workorder::withDrafts(), то withDrafts() ниже можно убрать.
        // Но оставляем безопасно.
        $current_workorder = Workorder::withDrafts()
            ->with([
                'customer', 'user', 'instruction',
                'unit.manual.media',
                'unit.manual.components',
            ])
            ->findOrFail($workorder->id);

        $this->syncWaitingApproveMain($current_workorder);

        $users = User::all();

        $general_tasks = GeneralTask::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tasks = Task::whereIn('general_task_id', $general_tasks->pluck('id'))
            ->orderBy('general_task_id')
            ->orderBy('name')
            ->get();

        $tasksByGeneral = $tasks->groupBy('general_task_id');

        $showAll = $request->has('show_all')
            ? $request->get('show_all') === '1'
            : true;

        // mains by task
        $mains = Main::with(['user', 'task'])
            ->where('workorder_id', $current_workorder->id)
            ->get();

        $mainsByTask = $mains->keyBy('task_id');

        // Components & TDRs
        $components = collect();

        if ($current_workorder->unit?->manual) {
            $components = $current_workorder->unit->manual
                ->components()
                ->whereHas('tdrs', function ($q) use ($current_workorder, $showAll) {
                    $q->where('workorder_id', $current_workorder->id)
                        ->whereHas('tdrProcesses', function ($qq) use ($showAll) {
                            if (!$showAll) {
                                $qq->whereNull('date_finish');
                            }
                        });
                })
                ->with(['tdrs' => function ($q) use ($current_workorder, $showAll) {
                    $q->where('workorder_id', $current_workorder->id)
                        ->with(['tdrProcesses' => function ($qq) use ($showAll) {
                            if (!$showAll) {
                                $qq->whereNull('date_finish');
                            }
                            $qq->with('processName')->orderBy('id');
                        }])
                        ->orderBy('id');
                }])
                ->orderBy('name')
                ->get();
        }

        // Manual images (thumb/big)
        $manual = optional($current_workorder->unit)->manual;
        $imgThumb = asset('img/noimage.png');
        $imgFull = null;

        if ($manual) {
            $m = $manual->getFirstMedia('manuals');

            if ($m) {
                $imgFull = route('image.show.big', [
                    'mediaId' => $m->id,
                    'modelId' => $manual->id,
                    'mediaName' => 'manuals',
                ]);

                $imgThumb = route('image.show.thumb', [
                    'mediaId' => $m->id,
                    'modelId' => $manual->id,
                    'mediaName' => 'manuals',
                ]);
            }
        }

        $imgThumb = $imgThumb ?? asset('img/noimage.png');

        // TDR ids
        $tdrIds = Tdr::where('workorder_id', $current_workorder->id)->pluck('id');

        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        $ordersPartsNew = collect();
        $prl_parts = collect();
        $orderedQty = 0;
        $receivedQty = 0;

        if ($necessary) {
            $ordersPartsNew = Tdr::where('workorder_id', $current_workorder->id)
                ->where('necessaries_id', $necessary->id)
                ->with([
                    'codes',
                    'orderComponent' => function ($query) {
                        $query->select('id', 'name', 'part_number', 'ipl_num');
                    }
                ])
                ->get();

            $prl_parts = Tdr::where('workorder_id', $current_workorder->id)
                ->where('necessaries_id', $necessary->id)
                ->with([
                    'component' => function ($query) {
                        $query->select('id', 'name', 'part_number', 'ipl_num');
                    },
                    'orderComponent' => function ($query) {
                        $query->select('id', 'name', 'part_number', 'ipl_num');
                    }
                ])
                ->get();

            $orderedQty = $prl_parts->sum('qty');
            $receivedQty = $prl_parts->whereNotNull('received')->sum('qty');
        }

        // Training
        $user = Auth::user();
        $user_wo = $current_workorder->user_id;
        $manual_id = $current_workorder->unit->manual_id ?? null;

        $trainings = null;
        if ($manual_id) {
            $form_type = 112;
            $trainings = Training::where('manuals_id', $manual_id)
                ->where('user_id', $user_wo)
                ->where('form_type', $form_type)
                ->orderBy('date_training', 'desc')
                ->first();
        }

        // General mains (task_id null)
        $generalMains = Main::with('user')
            ->where('workorder_id', $current_workorder->id)
            ->whereNull('task_id')
            ->get()
            ->keyBy('general_task_id');

        // calculate finished by general task
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

        return view('admin.mains.main', compact(
            'users',
            'current_workorder',
            'mains',
            'general_tasks',
            'tasks',
            'tasksByGeneral',
            'imgThumb',
            'imgFull',
            'manual',
            'components',
            'showAll',
            'ordersPartsNew',
            'prl_parts',
            'orderedQty',
            'receivedQty',
            'trainings',
            'user_wo',
            'manual_id',
            'user',
            'generalMains',
            'mainsByTask',
            'gtAllFinished'
        ));
    }


    protected function syncWaitingApproveMain(Workorder $workorder): void
    {
        // === Если апрува нет — чистим старую запись ===
        if (!$workorder->approve_at) {

            $waitingTaskId = Task::where('name', 'Approved')->value('id');

            if (!$waitingTaskId) {
                return;
            }

            $main = Main::where('workorder_id', $workorder->id)
                ->where('task_id', $waitingTaskId)
                ->first();

            if ($main) {
                $main->user_id = null;
                $main->date_finish = null;
                $main->save();
            }

            return;
        }

        // === Получаем задачу Approved ===
        $waitingTask = Task::where('name', 'Approved')->first();
        if (!$waitingTask) {
            return;
        }

        // === Находим или создаём Main ===
        $main = Main::firstOrNew([
            'workorder_id' => $workorder->id,
            'task_id' => $waitingTask->id,
        ]);

        if (
            $main->exists &&
            $main->date_finish === $workorder->approve_at->toDateString()
        ) {
            return; // уже синхронизировано → выходим
        }

        // === Проставляем general_task_id ===
        $main->general_task_id = $waitingTask->general_task_id;

        // === Проставляем user_id ===
        $userId = null;
        if ($workorder->approve_name) {
            $userId = User::where('name', $workorder->approve_name)->value('id');
        }
        $main->user_id = $userId;

        // === Проставляем дату ===
        $main->date_finish = $workorder->approve_at->toDateString();

        $main->save();
    }

    public function edit($id)
    {
        return 1;
    }

    public function update(Request $request, Main $main)
    {

        $oldIgnore = (int)$main->ignore_row;

        $main->loadMissing(['task.generalTask']);
        $task = $main->task;

        $data = $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
            'ignore_row' => ['nullable', 'boolean'],
        ]);

        $taskName = $main->task?->name;

        if ($taskName === 'Approved') {
            return back()->withErrors([
                'date_finish' => 'This task is locked and cannot be edited.',
            ]);
        }

        $isRestrictedFinish = in_array($taskName, ['Approved', 'Completed'], true);

        $ignoreRow = $request->boolean('ignore_row');
        $hasStart = $request->has('date_start');
        $hasFinish = $request->has('date_finish');

        if ($isRestrictedFinish && !auth()->user()->hasAnyRole('Admin|Manager')) {
            unset($data['date_finish']);
            $hasFinish = false; // важно: поле "как будто не приходило"
        }


        if (!$hasStart && !$hasFinish && $oldIgnore === (int)$ignoreRow) {
            return back();
        }

        $resolved = Main::validateAndResolveDates($data, $task, $main, $ignoreRow, $hasStart, $hasFinish);

        if ($hasStart) $main->date_start = $resolved['date_start'];
        if ($hasFinish) $main->date_finish = $resolved['date_finish'];

        // user_id логика — как у тебя
        $afterStart = $main->date_start;
        $afterFinish = $main->date_finish;

        $main->user_id = (empty($afterStart) && empty($afterFinish)) ? null : auth()->id();
        $main->ignore_row = $ignoreRow;

        $main->save();

        $wo = $main->workorder ?: Workorder::find($main->workorder_id);

        if ($wo) {
            $wo->recalcGeneralTaskStatuses($main->general_task_id); // пересчёт только одного этапа
            $wo->syncDoneByCompletedTask();                         // DONE строго по Completed
        }


        return back()->with('success', 'Record updated.');
    }

    public function destroy($id)
    {

        return redirect()->back();
    }

    public function updateRepairOrder(Request $request, \App\Models\TdrProcess $tdrProcess)
    {
        $request->validate([
            'repair_order' => 'nullable|string|max:255',
        ]);

        $tdrProcess->repair_order = $request->repair_order;
        $tdrProcess->user_id = auth()->id();
        $tdrProcess->save();

        return response()->json([
            'success' => true,
            'user' => auth()->user()?->name ?? 'system',
            'updated_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function activity(Main $main)
    {
        $logs = Activity::query()
            ->where('subject_type', Main::class)
            ->where('subject_id', $main->id)
            ->latest()
            ->take(200)
            ->get();

        $html = view('admin.mains.partials.activity_list', compact('main', 'logs'))->render();

        return response()->json(['html' => $html]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Training;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'task_id'      => ['required', 'exists:tasks,id'],
            'date_start'   => ['nullable', 'date'],
            'date_finish'  => ['nullable', 'date'],
        ]);

        // один запрос вместо двух
        $task = Task::with('generalTask')->findOrFail($data['task_id']);
        $hasStart = (bool) $task->task_has_start_date;

        // ✅ проверки только если у general_task есть start
        if ($hasStart) {

            // finish нельзя без start
            if (!empty($data['date_finish']) && empty($data['date_start'])) {
                return back()
                    ->withErrors(['date_finish' => 'Set start date first'])
                    ->withInput();
            }

            // finish не раньше start
            if (!empty($data['date_start']) && !empty($data['date_finish'])) {
                if (\Carbon\Carbon::parse($data['date_finish'])
                    ->lt(\Carbon\Carbon::parse($data['date_start']))) {

                    return back()
                        ->withErrors(['date_finish' => 'Finish date cannot be earlier than start date'])
                        ->withInput();
                }
            }

        } else {
            // ✅ если start не нужен — не сохраняем его вообще
            $data['date_start'] = null;
        }

        $main = new Main();
        $main->workorder_id    = $data['workorder_id'];
        $main->task_id         = $task->id;
        $main->general_task_id = $task->general_task_id;
        $main->user_id         = auth()->id();

        $main->date_start  = $data['date_start'] ?? null;
        $main->date_finish = $data['date_finish'] ?? null;

        $main->save();
        $main->loadMissing(['task.generalTask']);

        activity('mains')
            ->performedOn($main)
            ->causedBy(auth()->user())
            ->event('created')
            ->withProperties([
                'task' => [
                    'general' => $main->task?->generalTask?->name,
                    'name'    => $main->task?->name,
                ],
                'before' => [
                    'date_start'  => null,
                    'date_finish' => null,
                ],
                'after' => [
                    'date_start'  => $main->date_start?->format('Y-m-d'),
                    'date_finish' => $main->date_finish?->format('Y-m-d'),
                ],
                'main_id' => $main->id,
            ])
            ->log('created');

        return back()->with('success', 'Record created.');
    }

    public function show($workorder_id, Request $request)
    {

        $current_workorder = Workorder::with([
            'customer','user','instruction',
            'unit.manual.media',
            'unit.manual.components',
        ])->findOrFail($workorder_id);

        $this->syncWaitingApproveMain($current_workorder);

        $users          = User::all();
        $general_tasks = GeneralTask::orderBy('sort_order')->orderBy('id')->get();
        $tasks       = Task::whereIn('general_task_id', $general_tasks->pluck('id'))->get();
        $tasksByGeneral = $tasks->groupBy('general_task_id');

        $showAll = request()->has('show_all')
            ? request('show_all') === '1'
            : true;

        $mains = Main::with(['user','task'])
            ->where('workorder_id', $workorder_id)
            ->get();
        $mainsByTask = $mains->keyBy('task_id');

//--------------------------------------------------------------------------------------------------

        // Components & TDRs (логика "open only" / "all" управляется show_all)
        $components = collect();
        if ($current_workorder->unit?->manual) {
            $components = $current_workorder->unit->manual
                ->components()
                ->whereHas('tdrs', function ($q) use ($workorder_id, $showAll) {
                    $q->where('workorder_id', $workorder_id)
                        ->whereHas('tdrProcesses', function ($qq) use ($showAll) {
                            if (!$showAll) {
                                $qq->whereNull('date_finish');
                            }
                        });
                })
                ->with(['tdrs' => function ($q) use ($workorder_id, $showAll) {
                    $q->where('workorder_id', $workorder_id)
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
        if ($manual) {
            if (method_exists($manual, 'getFirstMediaThumbnailUrl')) {
                $imgThumb = $manual->getFirstMediaThumbnailUrl('manuals'); // из HasMediaHelpers
                $imgFull  = $manual->getFirstMediaBigUrl('manuals');
            } else {
                $imgThumb = $manual->getFirstMediaUrl('manuals', 'thumb');
                $imgFull  = $manual->getFirstMediaUrl('manuals', 'big');
            }
        }
        $imgThumb = $imgThumb ?? asset('img/placeholder-160x160.png');

        $tdrIds = Tdr::where('workorder_id', $workorder_id)->pluck('id');

        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        $ordersPartsNew = Tdr::where('workorder_id', $workorder_id)
            ->where('necessaries_id', $necessary->id)

            ->with(['codes', 'orderComponent' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
            ->get();

        $prl_parts=Tdr::where('workorder_id', $workorder_id)
            ->where('necessaries_id', $necessary->id)
            ->with([
                'component' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num');
                },
                'orderComponent' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num');
                }
            ])
            ->get();
        // Подсчет заказанных деталей (сумма QTY всех деталей в prl_parts)
        $orderedQty = $prl_parts->sum('qty');

        // Подсчет полученных деталей (сумма QTY деталей с заполненным полем received)
        $receivedQty = $prl_parts->whereNotNull('received')->sum('qty');

        // Training logic (same as in TdrController)
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

 //--------------------------------------------------------------------------------------------------

        $generalMains = Main::with('user')
            ->where('workorder_id', $workorder_id)
            ->whereNull('task_id')
            ->get()
            ->keyBy('general_task_id');

//--------------------------------------------------------------------------------------------------

        $gtAllFinished = [];

        foreach ($general_tasks as $gt) {

            $taskIds = ($tasksByGeneral[$gt->id] ?? collect())->pluck('id');

            // Если у general нет задач – считаем не завершённым
            if ($taskIds->isEmpty()) {
                $gtAllFinished[$gt->id] = false;
                continue;
            }

            $gtAllFinished[$gt->id] = $taskIds->every(function ($taskId) use ($mainsByTask) {

                $main = $mainsByTask->get($taskId);

                // нет строки main → задача точно не завершена
                if (!$main) {
                    return false;
                }

                // если ignore_row = 1, строка игнорируется и НЕ мешает стать зелёным
                if ($main->ignore_row) {
                    return true;
                }

                // обычная проверка: есть ли дата финиша
                return !empty($main->date_finish);
            });
        }

        return view('admin.mains.main', compact(
            'users','current_workorder','mains','general_tasks','tasks','tasksByGeneral',
            'imgThumb','imgFull','manual','components','showAll',
            'ordersPartsNew','prl_parts','orderedQty', 'receivedQty',
            'trainings','user_wo','manual_id','user','generalMains', 'mainsByTask','gtAllFinished'
        ));
    }

    protected function syncWaitingApproveMain(Workorder $workorder): void
    {
        // === Если апрува нет — чистим старую запись ===
        if (!$workorder->approve_at) {

            $waitingTaskId = Task::where('name', 'Waiting approve')->value('id');

            if (!$waitingTaskId) {
                return;
            }

            $main = Main::where('workorder_id', $workorder->id)
                ->where('task_id', $waitingTaskId)
                ->first();

            if ($main) {
                $main->user_id     = null;
                $main->date_finish = null;
                $main->save();
            }

            return;
        }

        // === Получаем задачу Waiting approve ===
        $waitingTask = Task::where('name', 'Waiting approve')->first();
        if (!$waitingTask) {
            return;
        }

        // === Находим или создаём Main ===
        $main = Main::firstOrNew([
            'workorder_id' => $workorder->id,
            'task_id'      => $waitingTask->id,
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

        $oldIgnore = (int) $main->ignore_row;

        $beforeStart  = $main->date_start
            ? Carbon::parse($main->date_start)->format('Y-m-d')
            : null;

        $beforeFinish = $main->date_finish
            ? Carbon::parse($main->date_finish)->format('Y-m-d')
            : null;

        // сразу подгрузим связи, чтобы был доступ к task/generalTask
        $main->loadMissing(['task.generalTask']);

        $task = $main->task;

        // флаг: нужен ли вообще старт для этого таска
        $requiresStart = (bool) ($task?->has_start_date ?? false);


        $data = $request->validate([
            'date_start'  => ['nullable','date'],
            'date_finish' => ['nullable','date'],
            'ignore_row'  => ['nullable', 'boolean'],
        ]);

        $taskName = $main->task?->name;
        $isRestrictedFinish = in_array($taskName, ['Waiting approve', 'Completed'], true);

        $ignoreRow = $request->boolean('ignore_row');
        $hasStart  = $request->has('date_start');
        $hasFinish = $request->has('date_finish');

        if ($isRestrictedFinish && !auth()->user()->hasAnyRole('Admin|Manager')) {
            unset($data['date_finish']);
        }

        // если не менялись ни даты, ни ignore_row – выходим
        if (!$hasStart && !$hasFinish && $oldIgnore === (int) $ignoreRow) {
            return back();
        }

        // считаем новые значения (учитываем, что поле могло не прийти)
        $newStart = $hasStart
            ? (!empty($data['date_start'])
                ? Carbon::parse($data['date_start'])
                : null)
            : ($main->date_start
                ? Carbon::parse($main->date_start)
                : null);

        $newFinish = $hasFinish
            ? (!empty($data['date_finish'])
                ? Carbon::parse($data['date_finish'])
                : null)
            : ($main->date_finish
                ? Carbon::parse($main->date_finish)
                : null);

               /*
         * Валидацию дат делаем ТОЛЬКО если:
         *  - строка не игнорируется
         *  - для этого таска вообще нужен start (has_start_date = 1)
         */
        if (!$ignoreRow && $requiresStart) {

            // finish нельзя без start
            if ($hasFinish && $newFinish && !$newStart) {
                return back()
                    ->withErrors(['date_finish' => 'Set the start date before the finish date.'])
                    ->withInput();
            }

            // finish не раньше start
            if ($newStart && $newFinish && $newFinish->lt($newStart)) {
                return back()
                    ->withErrors(['date_finish' => 'Finish cannot be before Start'])
                    ->withInput();
            }
        }

        // применяем изменения только если поле реально приходило
        if ($hasStart) {
            $main->date_start = $newStart;
        }

        if ($hasFinish) {
            $main->date_finish = $newFinish;
        }


        // логика user_id после применения дат
        $afterStart  = $main->date_start;
        $afterFinish = $main->date_finish;

        if (empty($afterStart) && empty($afterFinish)) {
            $main->user_id = null;
        } else {
            $main->user_id = auth()->id();
        }

        $main->ignore_row = $ignoreRow;
        $main->save();

        // ==== ЛОГИ ====

        $afterStart  = $main->date_start
            ? Carbon::parse($main->date_start)->format('Y-m-d')
            : null;

        $afterFinish = $main->date_finish
            ? Carbon::parse($main->date_finish)->format('Y-m-d')
            : null;

        // лог по ignore_row — только если флаг реально поменялся
        if ($oldIgnore !== (int) $main->ignore_row) {
            activity('ignore')
                ->performedOn($main)
                ->causedBy(auth()->user())
                ->withProperties([
                    'attribute'    => 'ignore_row',
                    'from'         => $oldIgnore,
                    'to'           => (int) $main->ignore_row,
                    'workorder_id' => $main->workorder_id,
                    'task_id'      => $main->task_id,
                ])
                ->event('ignore_row_toggled')
                ->log('Toggle ignore_row on Main row');
        }

        // лог по датам — только если даты изменились
        if ($beforeStart !== $afterStart || $beforeFinish !== $afterFinish) {

            $clearedStart  = !empty($beforeStart)  && empty($afterStart);
            $clearedFinish = !empty($beforeFinish) && empty($afterFinish);

            $action = ($clearedStart || $clearedFinish) ? 'cleared' : 'updated';

            activity('mains')
                ->performedOn($main)
                ->causedBy(auth()->user())
                ->event($action)
                ->withProperties([
                    'action'  => $action,
                    'cleared' => [
                        'start'  => $clearedStart,
                        'finish' => $clearedFinish,
                    ],
                    'task' => [
                        'general' => $main->task?->generalTask?->name,
                        'name'    => $main->task?->name,
                    ],
                    'before' => [
                        'date_start'  => $beforeStart,
                        'date_finish' => $beforeFinish,
                    ],
                    'after' => [
                        'date_start'  => $afterStart,
                        'date_finish' => $afterFinish,
                    ],
                ])
                ->log($action);
        }

        return back()->with('success', 'Record updated.');
    }

    public function destroy($id)
    {

        return redirect()->back();
    }

    public function updateRepairOrder(Request $request, \App\Models\TdrProcess $tdrProcess)
    {
        $data = $request->validate([
            'repair_order' => ['nullable', 'string', 'max:50'],
        ]);

        $tdrProcess->update([
            'repair_order' => $data['repair_order'] ?? null,
        ]);

        return back();
    }

    private function prevGeneralTask(GeneralTask $gt): ?GeneralTask
    {
        $order = $this->generalTaskOrder();
        $i = array_search($gt->name, $order, true);
        if ($i === false || $i === 0) return null;

        return GeneralTask::where('name', $order[$i - 1])->first();
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

        return response()->json([
            'html' => $html,
        ]);
    }

    public function updateGeneralTaskDates(Request $request, Workorder $workorder, GeneralTask $generalTask)
    {

        $prev = GeneralTask::where('sort_order', '<', $generalTask->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($prev) {
            $prevMain = Main::where('workorder_id', $workorder->id)
                ->where('general_task_id', $prev->id)
                ->whereNull('task_id')
                ->first();

            if (empty($prevMain?->date_finish)) {
                return back()->with('error', "First complete the previous step:  {$prev->name}");
            }
        }

        $data = $request->validate([
            'date_start'  => ['nullable', 'date_format:Y-m-d'],
            'date_finish' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_start'],
        ]);

        if (!$generalTask->has_start_date) {
            unset($data['date_start']);
        }

        $main = Main::firstOrNew([
            'workorder_id'    => $workorder->id,
            'general_task_id' => $generalTask->id,
            'task_id'         => null,
        ]);

        $beforeStart  = $main->date_start?->format('Y-m-d');
        $beforeFinish = $main->date_finish?->format('Y-m-d');
        $beforeUserId = $main->user_id;

        if ($request->has('date_start')) {
            $main->date_start = $data['date_start'] ?? null;   // clear -> null
        }

        if ($request->has('date_finish')) {
            $main->date_finish = $data['date_finish'] ?? null; // clear -> null
        }

        $anchorDate = $generalTask->has_start_date ? $main->date_start : $main->date_finish;

        if (empty($anchorDate)) {
            $main->user_id = null;
        } elseif (empty($main->user_id)) {
            $main->user_id = auth()->id();
        }

        $main->save();

        //  ЛОГИРОВАНИЕ УДАЛЕНИЯ ДАТ

        $afterStart  = $main->date_start?->format('Y-m-d');
        $afterFinish = $main->date_finish?->format('Y-m-d');

        $deletedStart  = $request->has('date_start')  && !empty($beforeStart)  && empty($afterStart);
        $deletedFinish = $request->has('date_finish') && !empty($beforeFinish) && empty($afterFinish);

        if ($deletedStart || $deletedFinish) {
            $what = [];
            if ($deletedStart)  $what[] = "date_start {$beforeStart} → NULL";
            if ($deletedFinish) $what[] = "date_finish {$beforeFinish} → NULL";

            activity('mains')
                ->performedOn($main)
                ->causedBy(auth()->user())
                ->withProperties([
                    'workorder_id'     => $workorder->id,
                    'general_task_id'  => $generalTask->id,
                    'general_task'     => $generalTask->name,
                    'changes'          => $what,
                    'user_id_before'   => $beforeUserId,
                    'user_id_after'    => $main->user_id,
                ])
                ->log('Deleted date(s) in general task row');
        }

        return back()->with('success', 'Saved');
    }


}

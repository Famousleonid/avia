<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class MainController extends Controller
{
    public function index()
    {
        return 1;
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'workorder_id' => ['required','exists:workorders,id'],
            'task_id'      => ['required','exists:tasks,id'],
            'user_id'      => ['nullable','exists:users,id'],
            'date_start'   => ['nullable','date'],
            'date_finish'  => ['nullable','date','after_or_equal:date_start'],
            'description'  => ['nullable','string','max:1000'],
        ]);

        $task = Task::select('id','general_task_id')->findOrFail($data['task_id']);

        $main = new \App\Models\Main();
        $main->workorder_id     = $data['workorder_id'];
        $main->task_id          = $task->id;
        $main->general_task_id  = $task->general_task_id; // ← подставляем сюда
        $main->user_id          = $data['user_id'] ?? auth()->id();
        $main->description      = $data['description'] ?? null;
        $main->date_start       = $data['date_start'] ?? now()->toDateString();
        $main->date_finish      = $data['date_finish'] ?? null;
        $main->save();

        return redirect()->back()->with('success', 'Created success');
    }

    public function show($workorder_id, Request $request)
    {

        $users = User::all();
        $general_tasks = GeneralTask::orderBy('id')->get();
        $tasks = Task::orderBy('name')->get();
        $tasksByGeneral = $tasks->groupBy('general_task_id');
        $showAll = $request->boolean('show_all', false);

        $mains = Main::with(['user','task.generalTask'])
            ->where('workorder_id', $workorder_id)
            ->orderBy('date_start')
            ->orderBy('id')
            ->get();

        $current_workorder = Workorder::with([
            'customer','user','instruction',
            'unit.manual.media',
            'unit.manual.components',
        ])->findOrFail($workorder_id);

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


        $manual = optional($current_workorder->unit)->manual;

        if ($manual) {
            if (method_exists($manual, 'getFirstMediaThumbnailUrl')) {
                $imgThumb = $manual->getFirstMediaThumbnailUrl('manuals');   // из HasMediaHelpers
                $imgFull  = $manual->getFirstMediaBigUrl('manuals');
            } else {
                $imgThumb = $manual->getFirstMediaUrl('manuals', 'thumb');
                $imgFull  = $manual->getFirstMediaUrl('manuals', 'big');
            }
        }

        $imgThumb = $imgThumb ?? asset('img/placeholder-160x160.png');

        $tdrIds = Tdr::where('workorder_id', $workorder_id)
            ->whereNull('deleted_at')
            ->pluck('id');

        $tdrProcessesTotal = TdrProcess::whereIn('tdrs_id', $tdrIds)->count();
        $tdrProcessesOpen  = TdrProcess::whereIn('tdrs_id', $tdrIds)
            ->whereNull('date_finish')
            ->count();




 //     dd($total);


        return view('admin.mains.main', compact(
            'users','current_workorder','mains','general_tasks','tasks','tasksByGeneral',
            'imgThumb','imgFull','manual','components','showAll','tdrProcessesTotal','tdrProcessesOpen'
        ));
    }




    public function edit($id)
    {
        return 1;
    }

    public function update(Request $request, Main $main)
    {
        // 1) Базовая валидация формата (flatpickr присылает Y-m-d)
        $data = $request->validate([
            'date_start'  => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
        ]);

        // Преобразуем строки в Carbon (если пришли)
        $newStart  = isset($data['date_start'])  && $data['date_start']  !== null ? Carbon::parse($data['date_start'])  : null;
        $newFinish = isset($data['date_finish']) && $data['date_finish'] !== null ? Carbon::parse($data['date_finish']) : null;

        // Текущие значения из БД
        $dbStart  = $main->date_start ? Carbon::parse($main->date_start) : null;
        $dbFinish = $main->date_finish ? Carbon::parse($main->date_finish) : null;

        /**
         * 2) Правила:
         * - Нельзя ставить дату финиша раньше старта.
         * - Если меняем только финиш (start не прислали), сравниваем с БД.
         * - Если финиш прислали, а старта нет ни в запросе, ни в БД — запретить (нужен старт).
         */

        // Меняем ТОЛЬКО финиш?
        $onlyFinishChanging = $request->has('date_finish') && !$request->has('date_start');

        if ($onlyFinishChanging) {
            if ($newFinish) {
                if (!$dbStart) {
                    return back()
                        ->withErrors(['date_finish' => 'Set the start date before the finish date.'])
                        ->withInput();
                }
                if ($newFinish->lt($dbStart)) {
                    return back()
                        ->withErrors(['date_finish' => 'Finish date cannot be earlier than the start date ('. $dbStart->format('d-M-y') .').'])
                        ->withInput();
                }
            }

            // Валидно — обновляем только финиш
            $main->date_finish = $newFinish;
            $main->save();

            return back()->with('success', 'Finish date updated.');
        }

        // Меняем обе даты ИЛИ только старт
        // Если обе пришли — проверяем порядок
        if ($newStart && $newFinish && $newFinish->lt($newStart)) {
            return back()
                ->withErrors(['date_finish' => 'Finish date cannot be earlier than the start date ('. $newStart->format('d-M-y') .').'])
                ->withInput();
        }

        // Если пришёл финиш, но старт НЕ пришёл, используем БД для сравнения
        if ($newFinish && !$newStart && $dbStart && $newFinish->lt($dbStart)) {
            return back()
                ->withErrors(['date_finish' => 'Finish date cannot be earlier than the start date ('. $dbStart->format('d-M-y') .').'])
                ->withInput();
        }

        // Если пришёл финиш, а старта нет ни в запросе, ни в БД — не даём сохранить
        if ($newFinish && !$newStart && !$dbStart) {
            return back()
                ->withErrors(['date_finish' => 'Set the start date before the finish date.'])
                ->withInput();
        }

        // 3) Обновляем то, что пришло
        if ($request->has('date_start')) {
            $main->date_start = $newStart;
        }
        if ($request->has('date_finish')) {
            $main->date_finish = $newFinish;
        }
        $main->save();

        return back()->with('success', 'Record updated.');
    }

    public function destroy($id)
    {

        Main::destroy($id);

        return redirect()->back()->with('success', 'General row deleted');
    }


    public function progress()
    {

        $user = Auth::user()->load('team');

        $mains = Main::where(['user_id' => $user->id])->with('workorder')->get();
        $wos = $mains->unique('workorder_id')->sortByDesc('workorder_id');
        $team_techniks = collect();
        if ($user->team) {
            $team_techniks = User::where('team_id', $user->team->id)->get();
        }

        return view('admin.mains.progress', compact('mains', 'wos', 'team_techniks', 'user'));

    }

}

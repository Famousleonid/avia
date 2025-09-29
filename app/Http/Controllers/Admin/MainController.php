<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Task;
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

    public function show($workorder_id)
    {
        $users = User::all();
        $general_tasks = GeneralTask::orderBy('id')->get();
        $tasks = Task::orderBy('name')->get();
        $tasksByGeneral = $tasks->groupBy('general_task_id');

        $mains = Main::with(['user','task.generalTask'])
            ->where('workorder_id', $workorder_id)
            ->orderBy('date_start')
            ->orderBy('id')
            ->get();

        // Подтянем unit.manual и медиа manual заодно
        $current_workorder = Workorder::with([
            'customer','user','instruction',
            'unit.manual.media', // <— важно
        ])->findOrFail($workorder_id);

        $manual = optional($current_workorder->unit)->manual; // может быть null

        // Готовим URL-ы для Blade (ТОЛЬКО из Manual.manuals)
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
        // $imgFull может остаться null — учтём в Blade

        return view('admin.mains.main', compact(
            'users', 'current_workorder', 'mains',
            'general_tasks','tasks','tasksByGeneral',
            'imgThumb','imgFull'
        ));
    }

    public function edit($id)
    {
        return 1;
    }

    public function update(Request $request, Main $main)
    {
        // 1) Базовая валидация формата
        $data = $request->validate([
            'date_finish' => ['nullable','date'],
        ]);

        // 2) Бизнес-проверка: finish >= start (если обе заданы)
        if (!empty($data['date_finish']) && $main->date_start) {
            $finish = Carbon::parse($data['date_finish'])->startOfDay();
            $start  = Carbon::parse($main->date_start)->startOfDay();

            if ($finish->lt($start)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Finish date must be after or equal to start date',
                ], 422);
            }
        }

        // 3) Сохранение
        $main->date_finish = $data['date_finish'] ?? null;
        $main->save();

        return response()->json([
            'ok'          => true,
            'date_finish' => optional($main->date_finish)->format('Y-m-d'),
        ]);
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

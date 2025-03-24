<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class MainController extends Controller
{
    public function index()
    {
        return 1;
    }

    public function create(Request $request)
    {
        $request->validate([
            "general_task_id" => 'required',
        ]);

        $main = new Main([
            'workorder_id' => $request->workorder_id,
            'general_task_id' => $request->general_task_id,
            'user_id' => $request->user_id,
            'description' => $request->description,
            'date_start' => $request->date_start,
        ]);

        $main->save();

        return redirect()->back()->with('success', 'Created success');
    }

    public function show($workorder_id)
    {
        $users = User::all();
        $general_tasks = GeneralTask::all();
        $mains = Main::where('workorder_id', $workorder_id)->get();
        $current_workorder = Workorder::find($workorder_id);



        return view('admin.mains.main', compact('users', 'current_workorder', 'mains', 'general_tasks'));

    }

    public function edit($id)
    {
        return 1;
    }

    public function update(Request $request, $id)
    {

        $main = Main::find($id);
        $main->update(['date_finish' => $request->date_finish]);

        return redirect()->back()->with('success', 'Update save success');
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

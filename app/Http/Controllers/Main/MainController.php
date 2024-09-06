<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;


class MainController extends Controller
{
    public function index($workorder_id)
    {
        $users = User::all();
        $general_tasks = GeneralTask::all();
        $mains = Main::where('workorder_id', $workorder_id)->get();
        $current_workorder = Workorder::find($workorder_id);

        return view('cabinet.pages.main', compact('users', 'current_workorder', 'mains', 'general_tasks'));

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

}

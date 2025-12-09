<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Component_main;
use App\Models\Task;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComponentMainController extends Controller
{

    public function index($workorder_id)
    {
        $users = User::all();
        $tasks = Task::all();
        $current_workorder = Workorder::find($workorder_id);
        $components = Component::all();
        $component_mains = Component_main::where('workorder_id', $workorder_id)->get();

        return view('cabinet.pages.component_main', compact('users', 'current_workorder', 'component_mains', 'tasks', 'components'));

    }

    public function create(Request $request)
    {

        $request['date_start'] = Carbon::now();

        Component_main::create($request->all());

        return redirect()->back()->with('success', 'Created success');
    }

    public function update(Request $request, $id)
    {

        $component_main = Component_main::find($id);
        $component_main->update(['date_finish' => $request->date_finish]);

        return redirect()->back()->with('success', 'Update save success');
    }

    public function destroy($id)
    {

        Component_main::destroy($id);

        return redirect()->back()->with('success', 'Component row deleted');
    }
}

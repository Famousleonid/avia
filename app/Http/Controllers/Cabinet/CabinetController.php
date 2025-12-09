<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Main;
use App\Models\Material;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Facades\Auth;

class CabinetController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('team');
        $team = optional($user->team)->id;
        $mains = Main::all();
        $tasks = Task::all();
        $components = Component::all();
        $workorders = Workorder::with(['customer', 'main', 'user'])->get();


        $userMains = Main::where('user_id', $user->id)->get()->keyBy('workorder_id');

        $workorders = $workorders->map(function ($workorder) use ($user, $team, $userMains) {

            $isMyOnly = $userMains->has($workorder->id);
            $isMyTeam = $workorder->user->team == $team;

            $workorder->class = [
                'approve' => $workorder->approve ? 'row-approve' : 'row-non-approve',
                'my_workorder' => $isMyOnly ? 'row-my' : ($workorder->user_id == $user->id ? 'row-my' : 'row-no-my'),
                'my_team' => $isMyTeam ? 'row-my-team' : ($workorder->user->team == $team ? 'row-my-team' : 'row-no-my-team'),
            ];

            return $workorder;
        });


        if ($user->email_verified_at) {
            return view('cabinet.pages.index', compact('user', 'workorders', 'mains', 'tasks', 'components'));
        } else {
            return view('cabinet.master_none_verification', compact('user'));
        }
    }

    public function workorders()
    {
        $user = Auth::user();
        $workorders = Workorder::with('customer')->get();
        $mains = Main::all();


        if ($user->email_verified_at) {
            return view('cabinet.pages.workorders', compact('user', 'workorders', 'mains'));
        } else {
            return view('cabinet.master_none_verification', compact('user'));
        }

    }

    public function profile()
    {
        $user = Auth::user()->load('team');
        $avatar = $user->getMedia('avatar')->first();
        $teams = Team::all();

        return view('cabinet.pages.profile', compact('user', 'avatar', 'teams'));
    }

    public function techniks()
    {
        $users = User::all();
        // $avatar = $user->getMedia('avatar')->first();

        return view('cabinet.pages.techniks', compact('users'));
    }

    public function approve($id)
    {
        $current = Workorder::find($id);
        if ($current->approve == 0) {
            $current->approve = 1;
            $current->approve_at = now();
            $current->save();
        } else {
            $current->approve = 0;
            $current->approve_at = NULL;
            $current->save();
        }
        return redirect()->back();

    }

    public function paper($id)
    {

        $current_workorder = Workorder::find($id);

        return view('cabinet.pages.paper', compact('current_workorder'));

    }

    public function materials()
    {
        $materials = Material::all();

        return view('cabinet.pages.materials', compact('materials'));

    }

    public function underway()
    {

        // $base = 1;

        return view('cabinet.pages.underway');

    }

}

<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Component_main;
use App\Models\Main;
use App\Models\User;
use Illuminate\Http\Request;


class UnderwayController extends Controller
{

    public function index()
    {
        $user = Auth()->user();

        $mains = Main::where(['user_id' => $user->id])->with('workorder')->get();
        $wos = $mains->unique('workorder_id')->sortByDesc('workorder_id');
        $components_mains = Component_main::where(['user_id' => $user->id])->get();
        $team_techniks = User::where(['team' => $user->team])->get();


        return view('cabinet.pages.underway', compact('mains', 'components_mains', 'wos', 'team_techniks', 'user'));

    }

    public function technik(Request $request)
    {
        $technikId = $request->input('technik');
        $user = User::find(['id' => $technikId])->first();

        $mains = Main::where(['user_id' => $user->id])->with('workorder')->get();
        $wos = $mains->unique('workorder_id')->sortByDesc('workorder_id');
        $components_mains = Component_main::where(['user_id' => $user->id])->get();
        $team_techniks = User::where(['team' => $user->team])->get();


        return view('cabinet.pages.underway', compact('mains', 'components_mains', 'wos', 'team_techniks', 'user'));

    }

}

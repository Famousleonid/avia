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
        $user = Auth::user();

        if ($user->email_verified_at) {
            return view('cabinet.pages.index', compact('user'));
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



    public function progress()
    {

        // $base = 1;

        return view('cabinet.mains.progress');

    }

}

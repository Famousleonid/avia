<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CabinetController extends Controller implements hasMedia
{
    use InteractsWithMedia;

    public function index()
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return view('admin.index', compact('user'));
        } else {
            return view('admin.auth-verify');
        }





    }

//    public function activity()
//    {
//        $acts = Activity::All();
//
//        foreach ($acts as $act) {
//            $modelClass = $act->subject_type;
//            if (class_exists($modelClass)) {
//                $act->subject = $modelClass::find($act->subject_id);
//            } else {
//                $act->subject = null;
//            }
//        }
//
//
//        return View('admin.log.index', compact('acts'));
//
//
//    }


}


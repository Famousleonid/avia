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

}


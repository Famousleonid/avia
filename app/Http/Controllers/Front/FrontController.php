<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jenssegers\Agent\Facades\Agent;
use Spatie\Valuestore\Valuestore;


class FrontController extends Controller
{
    public function index()
    {
//
//        $pathToFile = Storage::path('private') . "\base.env";
//
//        $valuestore = Valuestore::make($pathToFile);
//
//        $device = Agent::device();
//        $d = Agent::isDesktop();
//        $x = Agent::isMobile();
//        $y = Agent::isTablet();
//        $z = Agent::browser();
//        $zz = Agent::version($z);
//        $n = Agent::platform();
//        $nn = Agent::version($n);
//
//        app('valuestore')->put('mobile', $x);
//        $mobile = app('valuestore')->get('mobile');
//        Log::channel('avia')->info('_______ фронт _________');
//        $page = ($x ? 'mobile' : 'front') . '.pages.index';

        $page = 'front.pages.index';
//
//        if (!Auth::check() && $mobile) return view('auth.login');

        // Log::channel('avia')->info('123' . ' 345 ');


        return View($page);


    }


}

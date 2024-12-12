<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectTo()    // направление по ролям
    {
        $mobile = app('valuestore')->get('mobile');

        if (Auth::check() && $mobile)
            return route('mobile.index');
        if (Auth::check() && Auth::user()->isAdmin())
            return route('admin.index');
        if (Auth::check())
            return route('cabinet.index');

        return route('login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect(route('home'));    // напрввляет после logout
    }


}

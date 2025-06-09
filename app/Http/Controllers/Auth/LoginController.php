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

    protected function redirectTo()
    {
        $agent = new \Jenssegers\Agent\Agent();

        if (Auth::check() && $agent->isMobile()) {
            return route('mobile.index');
        }

        if (Auth::check()) {
            return route('cabinet.index');
        }

        return route('login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect(route('home'));
    }
    public function showMobileLoginForm()
    {
        return view('auth.login');
    }

}

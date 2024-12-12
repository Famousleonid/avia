<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckIsAdmin
{

    public function handle($request, Closure $next)
    {

        if (!Auth::user()->isAdmin()) {
            abort('404');
        }

        return $next($request);
    }

}

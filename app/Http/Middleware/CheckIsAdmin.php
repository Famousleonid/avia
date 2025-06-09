<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckIsAdmin
{

    public function handle($request, Closure $next)
    {

        if (!Auth::user()->isAdmin()) {
            abort(403, 'No access');
        }

        return $next($request);
    }

}

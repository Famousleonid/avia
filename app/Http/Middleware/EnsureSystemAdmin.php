<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isSystemAdmin(), 403);

        return $next($request);
    }
}

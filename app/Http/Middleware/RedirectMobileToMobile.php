<?php

namespace App\Http\Middleware;

use App\Support\Device;
use Closure;
use Illuminate\Http\Request;

class RedirectMobileToMobile
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('image/show/*')) {
            return $next($request);
        }

        if (Device::isMobile($request)) {
            return redirect('/mobile');
        }

        return $next($request);
    }
}

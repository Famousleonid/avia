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

        // Workorder PDF preview/download: same named routes are used on mobile machining pages
        if ($request->is('workorders/*/pdf/*/show', 'workorders/*/pdf/*/download')) {
            return $next($request);
        }

        if (Device::isMobile($request)) {
            return redirect('/mobile');
        }

        return $next($request);
    }
}

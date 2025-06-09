<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class RedirectToMobile
{
    public function handle(Request $request, Closure $next)
    {
        $agent = new Agent();
        $isMobile = $agent->isMobile();
        $user = auth()->user();

        // Игнорируем системные маршруты
        $excludedRoutes = ['login', 'register', 'password/*', 'mobile*'];
        foreach ($excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        if (!$user) {
            return $next($request);
        }

        if ($isMobile && !$request->is('mobile*')) {
            return redirect()->route('mobile.index');
        }

        if (!$isMobile && !$request->is('cabinet*') && !$request->is('login')) {
            return redirect()->route('cabinet.index');
        }


        return $next($request);
    }
}

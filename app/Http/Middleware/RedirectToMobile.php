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

        // Только для авторизованных пользователей
        if (!$user) {
            return $next($request);
        }

        // Если пользователь зашёл с мобильного и он не на /mobile — редиректим
        if ($isMobile && !$request->is('mobile')) {
            return redirect()->route('mobile.index');
        }

        // Если десктоп и пользователь админ — направляем в админку
        if (!$isMobile && $user->isAdmin() && !$request->is('admin*')) {
            return redirect()->route('admin.index'); // пример
        }

        return $next($request);
    }
}

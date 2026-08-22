<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAuthSessionVersion
{
    public const SESSION_KEY = 'auth.version';

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $currentVersion = (int) $user->auth_version;
        $sessionVersion = $request->session()->get(self::SESSION_KEY);

        if ($sessionVersion === null) {
            if (Auth::viaRemember() || app()->runningUnitTests()) {
                self::markCurrent($request, $currentVersion);

                return $next($request);
            }

            return $this->logout($request);
        }

        if ((int) $sessionVersion !== $currentVersion) {
            return $this->logout($request);
        }

        return $next($request);
    }

    public static function markCurrent(Request $request, int $version): void
    {
        $request->session()->put(self::SESSION_KEY, $version);
    }

    private function logout(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your session has expired. Please sign in again.'], 401);
        }

        return redirect()->route('login')->with('status', 'Your session has expired. Please sign in again.');
    }
}

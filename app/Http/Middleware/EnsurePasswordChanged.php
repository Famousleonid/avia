<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordChanged
{
    private array $allowedRoutes = [
        'password.required',
        'password.required.update',
        'profile.password',
        'mobile.profile.changePassword',
        'logout',
        'verification.notice',
        'verification.verify',
        'verification.resend',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->requiresImmediatePasswordChange()) {
            return $next($request);
        }

        if ($request->routeIs(...$this->allowedRoutes)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $user->hasExpiredTemporaryPassword()
                    ? 'Temporary password expired. Change it to continue.'
                    : 'Password change required.',
                'code' => $user->hasExpiredTemporaryPassword()
                    ? 'temporary_password_expired'
                    : 'password_change_required',
            ], 423);
        }

        return redirect()->route('password.required');
    }
}

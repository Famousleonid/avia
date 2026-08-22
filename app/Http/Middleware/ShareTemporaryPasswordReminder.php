<?php

namespace App\Http\Middleware;

use App\Models\UserUiSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ShareTemporaryPasswordReminder
{
    public function handle(Request $request, Closure $next)
    {
        View::share('temporaryPasswordReminder', null);

        $user = $request->user();

        if (
            ! $user
            || ! $request->isMethod('GET')
            || $request->expectsJson()
            || $request->routeIs('password.required')
            || ! $user->hasActiveTemporaryPassword()
        ) {
            return $next($request);
        }

        $today = today()->toDateString();
        $setting = UserUiSetting::query()->firstOrNew([
            'user_id' => $user->id,
            'scope' => UserUiSetting::PASSWORD_SECURITY_SCOPE,
            'key' => UserUiSetting::TEMPORARY_PASSWORD_REMINDER_KEY,
        ]);

        if ($setting->exists && $setting->value === $today) {
            return $next($request);
        }

        $setting->value = $today;
        $setting->save();

        View::share('temporaryPasswordReminder', [
            'expiresAt' => $user->temporary_password_expires_at,
            'profileUrl' => $request->routeIs('mobile.*')
                ? route('mobile.profile')
                : route('profile.edit'),
        ]);

        return $next($request);
    }
}

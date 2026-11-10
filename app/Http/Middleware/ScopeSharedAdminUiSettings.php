<?php

namespace App\Http\Middleware;

use App\Models\UserUiSetting;
use App\Support\UiSettingsDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScopeSharedAdminUiSettings
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->remove(UiSettingsDevice::ATTRIBUTE);
        $user = $request->user();
        if (! UiSettingsDevice::appliesTo($user)) {
            return $next($request);
        }

        // EncryptCookies authenticates the cookie; no IP or fingerprint is collected.
        $token = $request->cookie(UiSettingsDevice::COOKIE);
        if (! is_string($token) || ! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            $token = $request->session()->get(UiSettingsDevice::SESSION);
            if (! is_string($token) || ! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
                $token = bin2hex(random_bytes(32));
            }
        }
        $request->session()->put(UiSettingsDevice::SESSION, $token);
        $key = hash('sha256', $token);
        $this->initialize((int) $user->id, $key);
        $request->attributes->set(UiSettingsDevice::ATTRIBUTE, ['user_id' => (int) $user->id, 'key' => $key]);

        try {
            $response = $next($request);
            $response->headers->setCookie(cookie(UiSettingsDevice::COOKIE, $token, 60 * 24 * 365,
                '/', null, $request->isSecure(), true, false, 'lax'));

            return $response;
        } finally {
            // Request-scoped only; do not leak into later requests or background work.
            $request->attributes->remove(UiSettingsDevice::ATTRIBUTE);
        }
    }

    private function initialize(int $userId, string $key): void
    {
        $identity = ['user_id' => $userId, 'device_key' => $key];
        if (DB::table('user_ui_setting_devices')->where($identity)->exists()) {
            return;
        }

        DB::transaction(function () use ($userId, $key, $identity) {
            // Serialize first requests; a deleted preference must never be re-seeded.
            DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id']);
            if (DB::table('user_ui_setting_devices')->where($identity)->exists()) {
                return;
            }
            $rows = DB::table('user_ui_settings')->where('user_id', $userId)
                ->where('device_key', '')->where('scope', '!=', UserUiSetting::PASSWORD_SECURITY_SCOPE)
                ->get(['user_id', 'scope', 'key', 'value', 'created_at', 'updated_at']);
            foreach ($rows->chunk(200) as $chunk) {
                DB::table('user_ui_settings')->insert($chunk->map(fn ($row) =>
                    array_merge((array) $row, ['device_key' => $key]))->all());
            }
            DB::table('user_ui_setting_devices')->insert($identity + ['created_at' => now()]);
        });
    }
}

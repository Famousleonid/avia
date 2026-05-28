<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateMobileApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $plainToken = (string) $request->bearerToken();

        if ($plainToken === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $token = MobileApiToken::query()
            ->with('user.role', 'user.team')
            ->where('token_hash', MobileApiToken::hashPlainTextToken($plainToken))
            ->first();

        if (! $token || ($token->expires_at && $token->expires_at->isPast()) || ! $token->user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($token->user->trashed() || ! $token->user->hasVerifiedEmail()) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(static fn () => $token->user);
        $request->attributes->set('mobile_api_token', $token);

        return $next($request);
    }
}

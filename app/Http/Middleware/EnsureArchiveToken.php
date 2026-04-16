<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArchiveToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('archive.sync_token', '');
        $actualToken = (string) $request->bearerToken();

        if ($expectedToken === '' || $actualToken === '' || !hash_equals($expectedToken, $actualToken)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

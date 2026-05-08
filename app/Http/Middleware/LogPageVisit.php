<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogPageVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldLog($request, (int) $response->getStatusCode())) {
            PageVisit::create([
                'user_id' => Auth::id(),
                'visited_at' => now(),
                'method' => $request->method(),
                'path' => '/' . ltrim($request->path(), '/'),
                'url' => $request->fullUrl(),
                'route_name' => $request->route()?->getName(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        return $response;
    }

    private function shouldLog(Request $request, int $statusCode): bool
    {
        if (! Auth::check() || $statusCode >= 400) {
            return false;
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        return ! $request->is(
            'stat',
            'session/heartbeat',
            'notifications/*',
            'image/*',
            'storage/*',
            'assets/*',
            'css/*',
            'js/*',
            'img/*'
        );
    }
}

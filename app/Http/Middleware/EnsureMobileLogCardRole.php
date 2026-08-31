<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileLogCardRole
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            $request->user()?->roleIs(['Shipping', 'Paint', 'Machining']),
            403,
            'Log Card is not available for this role.'
        );

        return $next($request);
    }
}

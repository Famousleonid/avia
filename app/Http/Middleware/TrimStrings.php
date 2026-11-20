<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    public function handle($request, \Closure $next)
    {
        // Spaces at styled-run boundaries are part of the note, not padding.
        if ($request->is('workorders/*/notes')) {
            return $next($request);
        }
        return parent::handle($request, $next);
    }

    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'old_pass',
        'password',
        'password_confirmation',
    ];
}

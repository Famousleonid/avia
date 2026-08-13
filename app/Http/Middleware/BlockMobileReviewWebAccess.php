<?php

namespace App\Http\Middleware;

use App\Services\MobileReviewAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockMobileReviewWebAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! app(MobileReviewAccess::class)->isReviewUser($request->user())) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['email' => 'Review accounts cannot access the web version.']);
    }
}

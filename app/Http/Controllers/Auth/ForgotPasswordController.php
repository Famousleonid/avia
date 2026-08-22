<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:5,1')->only('sendResetLinkEmail');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $startedAt = microtime(true);
        $request->validate(['email' => ['required', 'email']]);

        try {
            $this->broker()->sendResetLink($this->credentials($request));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $remainingMicroseconds = 350000 - (int) ((microtime(true) - $startedAt) * 1000000);
        if ($remainingMicroseconds > 0) {
            usleep($remainingMicroseconds);
        }

        $message = 'If an account exists for that email, a password reset link has been sent.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }
}

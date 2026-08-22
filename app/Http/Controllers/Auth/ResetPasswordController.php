<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\UserPasswordService;
use App\Support\UserPasswordPolicy;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:10,1')->only('reset');
    }

    protected function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => UserPasswordPolicy::rules(),
        ];
    }

    protected function resetPassword($user, $password): void
    {
        app(UserPasswordService::class)->storePermanentPassword($user, (string) $password);
        event(new PasswordReset($user));
    }

    protected function sendResetResponse(Request $request, $response)
    {
        $message = 'Your password has been reset. Sign in with your new password.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('login')->with('status', $message);
    }
}

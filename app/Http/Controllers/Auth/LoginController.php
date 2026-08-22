<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAuthSessionVersion;
use App\Services\Auth\CredentialRateLimiter;
use App\Services\MobileReviewAccess;
use App\Support\Device;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }


    protected function authenticated(Request $request, $user)
    {
        if (app(MobileReviewAccess::class)->isReviewUser($user)) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Review accounts cannot access the web version.'])
                ->withInput($request->only('email'));
        }

        EnsureAuthSessionVersion::markCurrent($request, (int) $user->auth_version);

        if ($user->requiresImmediatePasswordChange()) {
            return redirect()->route('password.required');
        }

        return redirect()->intended(Device::homePath($request));
    }

    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return app(CredentialRateLimiter::class)->loginLocked(
            (string) $request->input($this->username()),
            $request
        );
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        app(CredentialRateLimiter::class)->hitLogin(
            (string) $request->input($this->username()),
            $request
        );
    }

    protected function clearLoginAttempts(Request $request): void
    {
        app(CredentialRateLimiter::class)->clearLogin(
            (string) $request->input($this->username()),
            $request
        );
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = app(CredentialRateLimiter::class)->loginAvailableIn(
            (string) $request->input($this->username()),
            $request
        );

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ])],
        ])->status(429);
    }


    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showMobileLoginForm()
    {
        return view('auth.login');
    }

}

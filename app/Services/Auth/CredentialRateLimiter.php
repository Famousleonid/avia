<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CredentialRateLimiter
{
    public function __construct(private RateLimiter $limiter)
    {
    }

    public function loginLocked(string $email, Request $request): bool
    {
        return $this->limiter->tooManyAttempts(
            $this->loginAccountKey($email),
            (int) config('security.rate_limits.login_account_attempts', 5)
        ) || $this->limiter->tooManyAttempts(
            $this->loginIpKey($request),
            (int) config('security.rate_limits.login_ip_attempts', 20)
        );
    }

    public function hitLogin(string $email, Request $request): void
    {
        $this->limiter->hit(
            $this->loginAccountKey($email),
            (int) config('security.rate_limits.login_account_decay_seconds', 300)
        );
        $this->limiter->hit(
            $this->loginIpKey($request),
            (int) config('security.rate_limits.login_ip_decay_seconds', 300)
        );
    }

    public function clearLogin(string $email, Request $request): void
    {
        $this->limiter->clear($this->loginAccountKey($email));
    }

    public function loginAvailableIn(string $email, Request $request): int
    {
        return max(
            $this->limiter->availableIn($this->loginAccountKey($email)),
            $this->limiter->availableIn($this->loginIpKey($request))
        );
    }

    public function currentPasswordLocked(User $user): bool
    {
        return $this->limiter->tooManyAttempts(
            $this->currentPasswordKey($user),
            (int) config('security.rate_limits.current_password_attempts', 5)
        );
    }

    public function hitCurrentPassword(User $user): void
    {
        $this->limiter->hit(
            $this->currentPasswordKey($user),
            (int) config('security.rate_limits.current_password_decay_seconds', 600)
        );
    }

    public function clearCurrentPassword(User $user): void
    {
        $this->limiter->clear($this->currentPasswordKey($user));
    }

    public function currentPasswordAvailableIn(User $user): int
    {
        return $this->limiter->availableIn($this->currentPasswordKey($user));
    }

    private function loginAccountKey(string $email): string
    {
        return 'auth:login:account:' . hash('sha256', Str::lower(trim($email)));
    }

    private function loginIpKey(Request $request): string
    {
        return 'auth:login:ip:' . hash('sha256', (string) $request->ip());
    }

    private function currentPasswordKey(User $user): string
    {
        return 'auth:current-password:' . $user->getKey();
    }
}

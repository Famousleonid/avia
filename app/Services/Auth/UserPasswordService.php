<?php

namespace App\Services\Auth;

use App\Models\MobileApiToken;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Support\UserPasswordPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserPasswordService
{
    public function __construct(private CredentialRateLimiter $rateLimiter)
    {
    }

    public function changeUsingCurrentPassword(User $user, string $currentPassword, string $newPassword): void
    {
        if ($this->rateLimiter->currentPasswordLocked($user)) {
            $seconds = $this->rateLimiter->currentPasswordAvailableIn($user);

            throw ValidationException::withMessages([
                'old_pass' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Hash::check($currentPassword, $user->password)) {
            $this->rateLimiter->hitCurrentPassword($user);

            throw ValidationException::withMessages([
                'old_pass' => 'The current password is incorrect.',
            ]);
        }

        if (Hash::check($newPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The new password must be different from the current password.',
            ]);
        }

        $this->rateLimiter->clearCurrentPassword($user);
        $this->storePermanentPassword($user, $newPassword);
    }

    public function storePermanentPassword(User $user, string $newPassword): void
    {
        $this->storePassword($user, $newPassword, false);
        $this->notifyChanged($user);
    }

    public function assignTemporaryPassword(User $user, string $newPassword): void
    {
        $this->storePassword($user, $newPassword, true);
    }

    private function storePassword(User $user, string $newPassword, bool $temporary): void
    {
        DB::transaction(function () use ($user, $newPassword, $temporary): void {
            $user->forceFill([
                'password' => Hash::make($newPassword),
                'remember_token' => Str::random(60),
                'must_change_password' => $temporary,
                'temporary_password_expires_at' => $temporary
                    ? now()->addDays(UserPasswordPolicy::temporaryLifetimeDays())
                    : null,
                'password_changed_at' => $temporary ? null : now(),
                'auth_version' => ((int) $user->auth_version) + 1,
            ])->save();

            MobileApiToken::query()->where('user_id', $user->getKey())->delete();
        });
    }

    private function notifyChanged(User $user): void
    {
        try {
            $user->notify(new PasswordChangedNotification());
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}

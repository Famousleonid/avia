<?php

namespace App\Support;

use App\Rules\SecureUserPassword;

class UserPasswordPolicy
{
    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $confirmed = true, bool $nullable = false): array
    {
        $rules = [
            'bail',
            $nullable ? 'nullable' : 'required',
            'string',
            new SecureUserPassword(),
        ];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    public static function minimum(): int
    {
        return max(8, (int) config('security.user_password_min', 8));
    }

    public static function maximum(): int
    {
        return max(self::minimum(), (int) config('security.user_password_max', 128));
    }

    public static function requiresAtSymbol(): bool
    {
        return (bool) config('security.user_password_requires_at', true);
    }

    public static function temporaryLifetimeDays(): int
    {
        return max(1, (int) config('security.temporary_password_lifetime_days', 7));
    }
}

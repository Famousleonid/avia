<?php

namespace App\Rules;

use App\Support\UserPasswordPolicy;
use Illuminate\Contracts\Validation\Rule;

class SecureUserPassword implements Rule
{
    private string $message = '';

    public function passes($attribute, $value): bool
    {
        if (! is_string($value)) {
            $this->message = 'The password must be a string.';

            return false;
        }

        $length = mb_strlen($value);
        if ($length < UserPasswordPolicy::minimum()) {
            $this->message = 'The password must be at least ' . UserPasswordPolicy::minimum() . ' characters.';

            return false;
        }

        if ($length > UserPasswordPolicy::maximum()) {
            $this->message = 'The password may not be greater than ' . UserPasswordPolicy::maximum() . ' characters.';

            return false;
        }

        if (UserPasswordPolicy::requiresAtSymbol() && ! str_contains($value, '@')) {
            $this->message = 'The password must include the @ symbol.';

            return false;
        }

        $normalized = mb_strtolower(trim($value));
        $blocked = array_map(
            static fn ($password): string => mb_strtolower(trim((string) $password)),
            (array) config('security.common_passwords', [])
        );

        if (in_array($normalized, $blocked, true)) {
            $this->message = 'This password is too common. Choose a different password.';

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->message !== '' ? $this->message : 'The password does not meet the security requirements.';
    }
}

<?php

namespace App\Support;

use App\Models\User;

class UiSettingsDevice
{
    public const COOKIE = 'avia_admin_ui_device';
    public const ATTRIBUTE = 'avia.ui_settings_device';
    public const SESSION = 'avia.ui_settings_device_token';

    public static function appliesTo(?User $user): bool
    {
        $email = trim((string) config('ui_settings.shared_admin_email', 'admin@admin.ca'));

        return $user && $email !== '' && $user->isSystemAdmin()
            && strcasecmp(trim($user->email), $email) === 0;
    }

    public static function context(): ?array
    {
        return app()->bound('request') ? request()->attributes->get(self::ATTRIBUTE) : null;
    }

    public static function keyFor(int $userId): string
    {
        $context = self::context();

        return $context && $context['user_id'] === $userId ? $context['key'] : '';
    }
}

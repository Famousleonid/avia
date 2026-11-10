<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\UiSettingsDevice;
use Illuminate\Database\Eloquent\Builder;

class UserUiSetting extends Model
{
    public const PROJECT_APPEARANCE_SCOPE = 'project.appearance';
    public const PROJECT_BACKGROUND_KEY = 'background_image';
    public const PASSWORD_SECURITY_SCOPE = 'security.password';
    public const TEMPORARY_PASSWORD_REMINDER_KEY = 'temporary-reminder-last-shown-on';

    protected $fillable = [
        'user_id',
        'scope',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    protected static function booted(): void
    {
        // Covers both the JSON API and direct controller/Blade/background reads.
        static::addGlobalScope('ui-device', function (Builder $query) {
            $context = UiSettingsDevice::context();
            if (! $context) {
                $query->where('user_ui_settings.device_key', '');
                return;
            }
            $query->where(function (Builder $q) use ($context) {
                $q->where(function (Builder $account) use ($context) {
                    $account->where('user_ui_settings.device_key', '')
                        ->where(fn (Builder $q) => $q->where('user_ui_settings.user_id', '!=', $context['user_id'])
                            ->orWhere('user_ui_settings.scope', self::PASSWORD_SECURITY_SCOPE));
                })->orWhere(function (Builder $device) use ($context) {
                    $device->where('user_ui_settings.user_id', $context['user_id'])
                        ->where('user_ui_settings.scope', '!=', self::PASSWORD_SECURITY_SCOPE)
                        ->where('user_ui_settings.device_key', $context['key']);
                });
            });
        });
        static::creating(function (self $setting) {
            $setting->device_key = $setting->scope === self::PASSWORD_SECURITY_SCOPE
                ? '' : UiSettingsDevice::keyFor((int) $setting->user_id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function projectBackgroundFor(int $userId): array
    {
        $value = self::query()
            ->where('user_id', $userId)
            ->where('scope', self::PROJECT_APPEARANCE_SCOPE)
            ->where('key', self::PROJECT_BACKGROUND_KEY)
            ->value('value');

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}

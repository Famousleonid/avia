<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends Model
{
    public const PRINT_FORMS_QR_ENABLED = 'print_forms.qr_enabled';
    public const MARKETING_WO_ESTIMATE_EMAIL = 'marketing.wo_estimate_email';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function boolean(string $key, bool $default = false): bool
    {
        try {
            $setting = self::query()->where('key', $key)->first();
        } catch (\Throwable) {
            return $default;
        }

        if (! $setting) {
            return $default;
        }

        return (bool) data_get($setting->value, 'enabled', $default);
    }

    public static function setBoolean(string $key, bool $enabled): self
    {
        return self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['enabled' => $enabled]]
        );
    }

    public static function valueFor(string $key, array $default = []): array
    {
        try {
            $setting = self::query()->where('key', $key)->first();
        } catch (\Throwable) {
            return $default;
        }

        return is_array($setting?->value) ? $setting->value : $default;
    }

    /**
     * @return list<string>
     */
    public static function marketingWoEstimateEmailRecipients(): array
    {
        $value = self::valueFor(self::MARKETING_WO_ESTIMATE_EMAIL);
        $emails = data_get($value, 'emails', []);

        if (! is_array($emails)) {
            return [];
        }

        return collect($emails)
            ->map(fn ($email): string => trim((string) $email))
            ->filter(fn (string $email): bool => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    public static function marketingWoEstimateEmailDelayDays(): int
    {
        $value = self::valueFor(self::MARKETING_WO_ESTIMATE_EMAIL);

        return max(0, min(365, (int) data_get($value, 'delay_days', 5)));
    }

    /**
     * @param  list<string>  $emails
     */
    public static function setMarketingWoEstimateEmailSettings(array $emails, int $delayDays): self
    {
        return self::query()->updateOrCreate(
            ['key' => self::MARKETING_WO_ESTIMATE_EMAIL],
            ['value' => [
                'emails' => array_values($emails),
                'delay_days' => max(0, min(365, $delayDays)),
            ]]
        );
    }
}

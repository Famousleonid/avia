<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends Model
{
    public const PRINT_FORMS_QR_ENABLED = 'print_forms.qr_enabled';

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
}

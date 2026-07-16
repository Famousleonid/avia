<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUiSetting extends Model
{
    public const PROJECT_APPEARANCE_SCOPE = 'project.appearance';
    public const PROJECT_BACKGROUND_KEY = 'background_image';

    protected $fillable = [
        'user_id',
        'scope',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

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

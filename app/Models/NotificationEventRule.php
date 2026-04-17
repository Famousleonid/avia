<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationEventRule extends Model
{
    protected $fillable = [
        'event_key',
        'name',
        'enabled',
        'severity',
        'repeat_policy',
        'repeat_every_minutes',
        'title_template',
        'message_template',
        'respect_user_preferences',
        'exclude_actor',
        'conditions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'respect_user_preferences' => 'boolean',
        'exclude_actor' => 'boolean',
        'conditions' => 'array',
        'repeat_every_minutes' => 'integer',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationEventRuleRecipient::class);
    }

    public function repeatEveryMinutes(?int $eventDefault = null): int
    {
        return match ($this->repeat_policy) {
            'once' => 0,
            'daily' => 60 * 24,
            'minutes' => max(1, (int) $this->repeat_every_minutes),
            default => (int) ($eventDefault ?? 0),
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationEventRuleRecipient extends Model
{
    protected $fillable = [
        'notification_event_rule_id',
        'recipient_type',
        'recipient_value',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationEventRule::class, 'notification_event_rule_id');
    }
}

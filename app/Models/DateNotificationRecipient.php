<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateNotificationRecipient extends Model
{
    protected $fillable = [
        'date_notification_id',
        'recipient_type',
        'recipient_value',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(DateNotification::class, 'date_notification_id');
    }
}

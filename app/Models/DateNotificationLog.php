<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateNotificationLog extends Model
{
    protected $fillable = [
        'date_notification_id',
        'recipient_user_id',
        'sent_on',
    ];

    protected $casts = [
        'sent_on' => 'date',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(DateNotification::class, 'date_notification_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingWoFileRecipient extends Model
{
    protected $fillable = [
        'marketing_wo_file_id',
        'user_id',
        'email_requested',
        'notified_at',
        'email_sent_at',
        'email_next_attempt_at',
        'email_attempts',
        'email_error',
    ];

    protected $casts = [
        'email_requested' => 'boolean',
        'notified_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'email_next_attempt_at' => 'datetime',
        'email_attempts' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(MarketingWoFile::class, 'marketing_wo_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingWoEstimateNotification extends Model
{
    protected $fillable = [
        'workorder_id',
        'customer_id',
        'estimate_date',
        'triggered_at',
        'due_at',
        'sent_at',
        'recipients',
        'mail_error',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'triggered_at' => 'datetime',
        'due_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipients' => 'array',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}

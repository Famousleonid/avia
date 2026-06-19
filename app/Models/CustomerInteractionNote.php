<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInteractionNote extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_id',
        'contact_id',
        'user_id',
        'note',
        'interaction_at',
        'follow_up_at',
        'follow_up_status',
        'reminder_sent_at',
    ];

    protected $casts = [
        'interaction_at' => 'date',
        'follow_up_at' => 'date',
        'reminder_sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php
// app/Models/EventLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $fillable = [
        'event_key',
        'subject_type',
        'subject_id',
        'recipient_user_id',
        'first_sent_at',
        'last_sent_at',
        'sent_count',
    ];

    protected $casts = [
        'first_sent_at' => 'datetime',
        'last_sent_at'  => 'datetime',
    ];

    public function subject()
    {
        return $this->morphTo();
    }
}

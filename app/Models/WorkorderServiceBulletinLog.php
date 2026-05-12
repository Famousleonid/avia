<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderServiceBulletinLog extends Model
{
    use HasFactory;

    public const STATUS_NOT_CARRIED_OUT = 'not_carried_out';
    public const STATUS_PREVIOUSLY_CARRIED_OUT = 'previously_carried_out';
    public const STATUS_AT_CARRIED_OUT = 'at_carried_out';

    protected $fillable = [
        'workorder_id',
        'manual_service_bulletin_id',
        'status',
        'stamp_user_id',
        'stamped_at',
        'notes',
    ];

    protected $casts = [
        'stamped_at' => 'datetime',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function serviceBulletin(): BelongsTo
    {
        return $this->belongsTo(ManualServiceBulletin::class, 'manual_service_bulletin_id');
    }

    public function stampUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stamp_user_id');
    }
}

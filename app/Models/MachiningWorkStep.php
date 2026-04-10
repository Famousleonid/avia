<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MachiningWorkStep extends Model
{
    use LogsActivity;

    protected $fillable = [
        'tdr_process_id',
        'wo_bushing_batch_id',
        'wo_bushing_process_id',
        'step_index',
        'machinist_user_id',
        'date_finish',
    ];

    protected $casts = [
        'date_finish' => 'date',
    ];

    public function tdrProcess(): BelongsTo
    {
        return $this->belongsTo(TdrProcess::class);
    }

    public function woBushingBatch(): BelongsTo
    {
        return $this->belongsTo(WoBushingBatch::class);
    }

    public function woBushingProcess(): BelongsTo
    {
        return $this->belongsTo(WoBushingProcess::class);
    }

    public function machinist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'machinist_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('machining_work_step')
            ->logOnly(['step_index', 'machinist_user_id', 'date_finish'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

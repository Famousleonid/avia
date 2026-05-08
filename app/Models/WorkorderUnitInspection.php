<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class WorkorderUnitInspection extends Model
{
    use LogsActivity;

    protected $fillable = [
        'workorder_id',
        'condition_id',
        'source_tdr_id',
        'notes',
        'qty',
        'serial_number',
        'assy_serial_number',
        'use_tdr',
        'use_process_forms',
        'source_deleted_at',
    ];

    protected $casts = [
        'qty' => 'integer',
        'use_tdr' => 'boolean',
        'use_process_forms' => 'boolean',
        'source_deleted_at' => 'datetime',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(Condition::class);
    }

    public function sourceTdr(): BelongsTo
    {
        return $this->belongsTo(Tdr::class, 'source_tdr_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('workorder_unit_inspection')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if (auth()->check()) {
            $activity->causer()->associate(auth()->user());
        }
    }
}

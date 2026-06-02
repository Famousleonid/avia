<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class WorkorderStdProcess extends Model
{
    use LogsActivity;

    protected $fillable = [
        'workorder_id',
        'std_type',
        'process_name_id',
        'source_tdr_id',
        'source_tdr_process_id',
        'processes',
        'description',
        'notes',
        'repair_order',
        'vendor_id',
        'date_start',
        'date_start_user_id',
        'date_start_user',
        'date_finish',
        'date_finish_user_id',
        'date_finish_user',
        'date_promise',
        'ignore_row',
        'user_id',
    ];

    protected $casts = [
        'processes' => 'array',
        'date_start' => 'date',
        'date_finish' => 'date',
        'date_promise' => 'date',
        'ignore_row' => 'boolean',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function processName(): BelongsTo
    {
        return $this->belongsTo(ProcessName::class, 'process_name_id');
    }

    public function sourceTdr(): BelongsTo
    {
        return $this->belongsTo(Tdr::class, 'source_tdr_id');
    }

    public function sourceTdrProcess(): BelongsTo
    {
        return $this->belongsTo(TdrProcess::class, 'source_tdr_process_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dateStartUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'date_start_user_id');
    }

    public function dateFinishUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'date_finish_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('workorder_std_process')
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

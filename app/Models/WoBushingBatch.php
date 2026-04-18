<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoBushingBatch extends Model
{
    protected $fillable = [
        'workorder_id',
        'process_id',
        'process_column_key',
        'repair_order',
        'vendor_id',
        'date_start',
        'date_finish',
        'working_steps_count',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_finish' => 'date',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function woBushingProcesses(): HasMany
    {
        return $this->hasMany(WoBushingProcess::class, 'batch_id');
    }

    public function machiningWorkSteps(): HasMany
    {
        return $this->hasMany(MachiningWorkStep::class, 'wo_bushing_batch_id')->orderBy('step_index');
    }
}

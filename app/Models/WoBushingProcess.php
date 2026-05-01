<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoBushingProcess extends Model
{
    protected $fillable = [
        'wo_bushing_line_id',
        'process_id',
        'batch_id',
        'qty',
        'date_start',
        'date_finish',
        'date_promise',
        'working_steps_count',
        'repair_order',
        'vendor_id',
    ];

    protected $casts = [
        'qty' => 'integer',
        'date_start' => 'date',
        'date_finish' => 'date',
        'date_promise' => 'date',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(WoBushingLine::class, 'wo_bushing_line_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(WoBushingBatch::class, 'batch_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function machiningWorkSteps(): HasMany
    {
        return $this->hasMany(MachiningWorkStep::class, 'wo_bushing_process_id')->orderBy('step_index');
    }
}

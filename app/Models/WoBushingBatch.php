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
        'date_start',
        'date_finish',
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

    public function woBushingProcesses(): HasMany
    {
        return $this->hasMany(WoBushingProcess::class, 'batch_id');
    }
}

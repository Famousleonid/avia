<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoBushingProcess extends Model
{
    protected $fillable = [
        'wo_bushing_line_id',
        'process_id',
        'batch_id',
        'qty',
        'date_start',
        'date_finish',
        'repair_order',
    ];

    protected $casts = [
        'qty' => 'integer',
        'date_start' => 'date',
        'date_finish' => 'date',
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualRepairProcedureStep extends Model
{
    protected $fillable = [
        'manual_repair_procedure_id',
        'process_name_id',
        'sort_order',
        'notes',
    ];

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ManualRepairProcedure::class, 'manual_repair_procedure_id');
    }

    public function processName(): BelongsTo
    {
        return $this->belongsTo(ProcessName::class);
    }
}

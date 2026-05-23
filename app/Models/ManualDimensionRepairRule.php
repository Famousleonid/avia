<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualDimensionRepairRule extends Model
{
    protected $fillable = [
        'manual_dimension_spec_id',
        'codes_id',
        'trigger',
        'repair_action',
        'manual_repair_procedure_id',
        'notes',
    ];

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'manual_dimension_spec_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ManualRepairProcedure::class, 'manual_repair_procedure_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualRepairStepDim extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'repair_step_id',
        'manual_parameter_id',
        'dim_min',
        'dim_max',
        'after_dim_min',
        'after_dim_max',
    ];

    protected $casts = [
        'dim_min'       => 'decimal:4',
        'dim_max'       => 'decimal:4',
        'after_dim_min' => 'decimal:4',
        'after_dim_max' => 'decimal:4',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(ManualRepairStep::class, 'repair_step_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'manual_parameter_id');
    }
}

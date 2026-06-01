<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualRepairStep extends Model
{
    protected $fillable = [
        'manual_parameter_id',
        'step_no',
        'component_id',
        'dim_min',
        'dim_max',
        'after_dim_min',
        'after_dim_max',
        'sort_order',
    ];

    protected $casts = [
        'dim_min'       => 'decimal:4',
        'dim_max'       => 'decimal:4',
        'after_dim_min' => 'decimal:4',
        'after_dim_max' => 'decimal:4',
    ];

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'manual_parameter_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }
}

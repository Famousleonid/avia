<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualRepairStep extends Model
{
    protected $fillable = [
        'dimension_point_id',
        'step_no',
        'component_id',
        'sort_order',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionPoint::class, 'dimension_point_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }

    public function dims(): HasMany
    {
        return $this->hasMany(ManualRepairStepDim::class, 'repair_step_id');
    }
}

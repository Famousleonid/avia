<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualParameter extends Model
{
    protected $fillable = [
        'manual_id',
        'inspection_component_id',
        'description',
        'is_required',
        'orig_dim_min',
        'orig_dim_max',
        'wear_dim_min',
        'wear_dim_max',
        'repair_dim_min',
        'repair_dim_max',
        'interference_value',
        'flange_clearance',
        'inspection',
        'sort_order',
    ];

    protected $casts = [
        'inspection_component_id' => 'integer',
        'is_required'             => 'boolean',
        'orig_dim_min'            => 'decimal:4',
        'orig_dim_max'            => 'decimal:4',
        'wear_dim_min'            => 'decimal:4',
        'wear_dim_max'            => 'decimal:4',
        'repair_dim_min'          => 'decimal:4',
        'repair_dim_max'          => 'decimal:4',
        'interference_value'      => 'decimal:4',
        'flange_clearance'        => 'decimal:4',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function inspectionComponent(): BelongsTo
    {
        return $this->belongsTo(ManualInspectionComponent::class, 'inspection_component_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ManualParameterCode::class);
    }

    public function repairRules(): HasMany
    {
        return $this->hasMany(ManualParameterRepairRule::class)->orderBy('id');
    }

    public function repairSteps(): HasMany
    {
        return $this->hasMany(ManualRepairStep::class, 'manual_parameter_id')->orderBy('sort_order');
    }

    public function points(): BelongsToMany
    {
        return $this->belongsToMany(
            ManualDimensionPoint::class,
            'manual_parameter_points',
            'manual_parameter_id',
            'manual_dimension_point_id'
        )->withPivot('id', 'is_repair_surface', 'max_repair_depth');
    }

    public function effectiveLimits(bool $useWear): array
    {
        if ($useWear && $this->wear_dim_min !== null) {
            return ['source' => 'wear', 'min' => $this->wear_dim_min, 'max' => $this->wear_dim_max];
        }

        return ['source' => 'orig', 'min' => $this->orig_dim_min, 'max' => $this->orig_dim_max];
    }
}

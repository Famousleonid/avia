<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoMeasurement extends Model
{
    protected $fillable = [
        'workorder_id',
        'manual_parameter_id',
        'manual_dimension_spec_id',
        'stage',
        'replaces_id',
        'actual_value',
        'limits_source',
        'result',
        'codes_id',
        'finding_notes',
        'repair_required',
        'repair_action',
        'manual_parameter_repair_rule_id',
        'manual_dimension_repair_rule_id',
        'calculated_oversize',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'actual_value'        => 'decimal:4',
        'calculated_oversize' => 'decimal:4',
        'repair_required'     => 'boolean',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'manual_parameter_id');
    }

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'manual_dimension_spec_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }

    public function repairRule(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRepairRule::class, 'manual_parameter_repair_rule_id');
    }

    public function legacyRepairRule(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionRepairRule::class, 'manual_dimension_repair_rule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(WoMeasurement::class, 'replaces_id');
    }

    public function replacedBy(): HasMany
    {
        return $this->hasMany(WoMeasurement::class, 'replaces_id');
    }
}

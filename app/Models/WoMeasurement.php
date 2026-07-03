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
        'new_part',
        'replaces_id',
        'actual_value',
        'limits_source',
        'result',
        'repair_step_no',
        'repair_depth_a',
        'repair_depth_b',
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
        'repair_depth_a'      => 'decimal:4',
        'repair_depth_b'      => 'decimal:4',
        'repair_required'     => 'boolean',
        'new_part'            => 'boolean',
        // FK ids as integers — otherwise some PDO/PHP setups return them as
        // strings ("38"), and the JS strict-equality match (m.manual_parameter_id
        // === param.id) fails, so saved measurements never bind to their parameter.
        'workorder_id'                    => 'integer',
        'manual_parameter_id'             => 'integer',
        'manual_dimension_spec_id'        => 'integer',
        'replaces_id'                     => 'integer',
        'codes_id'                        => 'integer',
        'manual_parameter_repair_rule_id' => 'integer',
        'manual_dimension_repair_rule_id' => 'integer',
        'user_id'                         => 'integer',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'manual_parameter_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }

    public function repairRule(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRepairRule::class, 'manual_parameter_repair_rule_id');
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

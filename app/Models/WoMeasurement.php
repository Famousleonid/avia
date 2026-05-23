<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoMeasurement extends Model
{
    protected $fillable = [
        'wo_measurement_session_id',
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
        'manual_repair_procedure_id',
        'calculated_oversize',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'actual_value'       => 'decimal:4',
        'calculated_oversize' => 'decimal:4',
        'repair_required'    => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WoMeasurementSession::class, 'wo_measurement_session_id');
    }

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'manual_dimension_spec_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }

    public function repairProcedure(): BelongsTo
    {
        return $this->belongsTo(ManualRepairProcedure::class, 'manual_repair_procedure_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The measurement this one corrects (initial FAIL in the chain).
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(WoMeasurement::class, 'replaces_id');
    }

    // Subsequent measurements that replaced this one.
    public function replacedBy(): HasMany
    {
        return $this->hasMany(WoMeasurement::class, 'replaces_id');
    }

    public function isPassed(): bool
    {
        return $this->result === 'PASS';
    }

    public function isFailed(): bool
    {
        return $this->result === 'FAIL';
    }
}

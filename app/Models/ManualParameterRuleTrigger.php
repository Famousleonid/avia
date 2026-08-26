<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualParameterRuleTrigger extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'repair_rule_id',
        'trigger',
        'codes_id',
        'min_delta', // dimensional triggers: fire when exceedance > min_delta (null = 0)
        'max_delta', // dimensional triggers: fire when exceedance <= max_delta (null = ∞)
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'repair_rule_id' => 'integer',
        'codes_id'       => 'integer',
        'min_delta'      => 'float',
        'max_delta'      => 'float',
    ];

    /**
     * Does this dimensional trigger accept the given exceedance (how far the
     * value is past the limit)? Band-less triggers accept any exceedance;
     * an unknown exceedance (no value) only matches band-less triggers.
     */
    public function acceptsExceedance(?float $exceedance): bool
    {
        if ($this->min_delta === null && $this->max_delta === null) {
            return true; // band-less trigger → any FAIL (old behavior)
        }
        if ($exceedance === null || $exceedance <= 0) {
            return false; // banded trigger needs a real exceedance on ITS side of the limit
        }

        return $exceedance > (float) ($this->min_delta ?? 0)
            && ($this->max_delta === null || $exceedance <= (float) $this->max_delta);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRepairRule::class, 'repair_rule_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
}

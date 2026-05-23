<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualBushingSpec extends Model
{
    protected $fillable = [
        'hole_spec_id',
        'bushing_od_spec_id',
        'paired_bushing_spec_id',
        'arrangement',
        'interference_value',
        'oversize_step',
        'max_oversize',
        'oversize_rounding',
        'notes',
    ];

    protected $casts = [
        'interference_value' => 'decimal:4',
        'oversize_step'      => 'decimal:4',
        'max_oversize'       => 'decimal:4',
    ];

    public function holeSpec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'hole_spec_id');
    }

    public function bushingOdSpec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'bushing_od_spec_id');
    }

    public function pairedBushingSpec(): BelongsTo
    {
        return $this->belongsTo(ManualBushingSpec::class, 'paired_bushing_spec_id');
    }

    public function oversizeOptions(): HasMany
    {
        return $this->hasMany(ManualBushingOversizeOption::class)->orderBy('oversize_value');
    }

    // calculated_oversize = measured_hole + interference_value, rounded per oversize_rounding.
    public function calculateOversize(float $measuredHole): ?float
    {
        $raw = $measuredHole + (float) $this->interference_value;
        $step = (float) $this->oversize_step;

        if ($step <= 0) {
            return round($raw, 4);
        }

        $result = match ($this->oversize_rounding) {
            'ceil'    => ceil($raw / $step) * $step,
            'nearest' => round($raw / $step) * $step,
            'exact'   => $this->oversizeOptions->firstWhere('oversize_value', round($raw, 4))?->oversize_value,
            default   => null,
        };

        return $result !== null ? round((float) $result, 4) : null;
    }
}

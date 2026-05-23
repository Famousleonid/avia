<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoMeasurementSession extends Model
{
    protected $fillable = [
        'workorder_id',
        'tdr_id',
        'manual_dimension_figure_id',
        'instruction_id',
        'user_id',
        'status',
        'finalized_at',
        'finalized_by',
        'notes',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function tdr(): BelongsTo
    {
        return $this->belongsTo(Tdr::class);
    }

    public function figure(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionFigure::class, 'manual_dimension_figure_id');
    }

    public function instruction(): BelongsTo
    {
        return $this->belongsTo(Instruction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(WoMeasurement::class, 'wo_measurement_session_id');
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    // All is_required specs have a measurement AND every FAIL has repair_action set.
    public function canFinalize(): bool
    {
        $figure = $this->figure()->with('points.specs')->first();

        $requiredSpecIds = $figure->points
            ->flatMap->specs
            ->where('is_required', true)
            ->pluck('id');

        $measurements = $this->measurements()->get()->keyBy('manual_dimension_spec_id');

        foreach ($requiredSpecIds as $specId) {
            $m = $measurements->get($specId);
            if (! $m) {
                return false;
            }
            if ($m->result === 'FAIL' && ! $m->repair_action) {
                return false;
            }
        }

        return true;
    }
}

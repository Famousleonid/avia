<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoMeasurementSession extends Model
{
    protected $fillable = [
        'workorder_id',
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

    public function canFinalize(): bool
    {
        $manual = $this->workorder->unit->manuals;

        $requiredSpecIds = ManualDimensionFigure::where('manual_id', $manual->id)
            ->with('points.specs')
            ->get()
            ->flatMap->points
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

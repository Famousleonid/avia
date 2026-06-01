<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterRulePhaseRuleProcess extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phase_rule_id',
        'manual_process_id',
        'sort_order',
    ];

    public function phaseRule(): BelongsTo
    {
        return $this->belongsTo(MasterRulePhaseRule::class, 'phase_rule_id');
    }

    public function manualProcess(): BelongsTo
    {
        return $this->belongsTo(ManualProcess::class, 'manual_process_id');
    }
}

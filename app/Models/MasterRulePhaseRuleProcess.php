<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MasterRulePhaseRuleProcess extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phase_rule_id',
        'manual_process_id',
        'description',
        'sort_order',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'phase_rule_id'     => 'integer',
        'manual_process_id' => 'integer',
        'sort_order'        => 'integer',
    ];

    public function phaseRule(): BelongsTo
    {
        return $this->belongsTo(MasterRulePhaseRule::class, 'phase_rule_id');
    }

    public function manualProcess(): BelongsTo
    {
        return $this->belongsTo(ManualProcess::class, 'manual_process_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcessDocument::class, 'documentable');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ManualParameterRuleProcess extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'repair_rule_id',
        'manual_process_id',
        'description',
        'is_gate', // EC gate anchor (one per rule) — freeze everything after it on EC
        'sort_order',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'repair_rule_id'    => 'integer',
        'manual_process_id' => 'integer',
        'sort_order'        => 'integer',
        'is_gate' => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRepairRule::class, 'repair_rule_id');
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

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
        'condition', // null=always; {"type":"has_process"|"not_has_process","process_name_ids":[..]} vs merged plan
        'sort_order',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'repair_rule_id'    => 'integer',
        'manual_process_id' => 'integer',
        'sort_order'        => 'integer',
        'is_gate' => 'boolean',
        'condition' => 'array',
    ];

    /**
     * Does this row apply, given the process_name ids the merged plan contains
     * (unconditional rows of all matched rules)? Null condition → always.
     */
    public function conditionMet(array $planNameIds): bool
    {
        $cond = $this->condition;
        if (empty($cond) || empty($cond['type'])) {
            return true;
        }
        $need = array_map('intval', $cond['process_name_ids'] ?? []);
        if (empty($need)) {
            return true;
        }
        $hit = (bool) array_intersect($need, $planNameIds);

        return match ($cond['type']) {
            'has_process'     => $hit,
            'not_has_process' => !$hit,
            default           => true, // unknown condition → don't block
        };
    }

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

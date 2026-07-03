<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualParameterRepairRule extends Model
{
    protected $fillable = [
        'manual_parameter_id',
        'name',
        'order_replacement', // legacy (vestigial) — kept synced for safety
        'action',            // 'repair' | 'order_new' | 'ec'  (orthogonal to triggers)
        'notes',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'manual_parameter_id' => 'integer',
        'sort_order'          => 'integer',
        'order_replacement' => 'boolean',
    ];

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'manual_parameter_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(ManualParameterRuleTrigger::class, 'repair_rule_id');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(ManualParameterRuleProcess::class, 'repair_rule_id')->orderBy('sort_order');
    }
}

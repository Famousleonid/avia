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
        'order_replacement',
        'notes',
    ];

    protected $casts = [
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

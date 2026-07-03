<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRule extends Model
{
    protected $fillable = [
        'manual_id',
        'inspection_component_id',
        'name',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'manual_id'               => 'integer',
        'inspection_component_id' => 'integer',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function inspectionComponent(): BelongsTo
    {
        return $this->belongsTo(ManualInspectionComponent::class, 'inspection_component_id');
    }

    public function phaseRules(): HasMany
    {
        return $this->hasMany(MasterRulePhaseRule::class)->orderBy('phase')->orderBy('sort_order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A component the repair rule orders when its route is applied
 * (docs/repair-routes-and-gates.md §5) — e.g. a new bearing for both
 * bearing-bore routes.
 */
class ManualParameterRulePartOrder extends Model
{
    protected $fillable = [
        'repair_rule_id',
        'component_id',
        'qty',
        'note',
    ];

    protected $casts = [
        'repair_rule_id' => 'integer',
        'component_id'   => 'integer',
        'qty'            => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRepairRule::class, 'repair_rule_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }
}

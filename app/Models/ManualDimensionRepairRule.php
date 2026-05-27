<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualDimensionRepairRule extends Model
{
    protected $fillable = [
        'manual_dimension_spec_id',
        'codes_id',
        'trigger',
        'repair_action',
        'no_repair',
        'order_replacement',
        'notes',
    ];

    protected $casts = [
        'no_repair'         => 'boolean',
        'order_replacement' => 'boolean',
    ];

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'manual_dimension_spec_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(ManualDimensionRepairRuleProcess::class, 'repair_rule_id')
            ->orderBy('sort_order');
    }
}

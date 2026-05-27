<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualDimensionRepairRuleProcess extends Model
{
    protected $fillable = [
        'repair_rule_id',
        'manual_process_id',
        'sort_order',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionRepairRule::class, 'repair_rule_id');
    }

    public function manualProcess(): BelongsTo
    {
        return $this->belongsTo(ManualProcess::class, 'manual_process_id');
    }
}

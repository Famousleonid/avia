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
        'sort_order',
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

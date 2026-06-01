<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRulePhaseRule extends Model
{
    public const PHASE_START  = 'start';
    public const PHASE_FINISH = 'finish';

    protected $fillable = [
        'master_rule_id',
        'phase',
        'name',
        'condition',
        'sort_order',
    ];

    protected $casts = [
        'condition' => 'array',
    ];

    public function masterRule(): BelongsTo
    {
        return $this->belongsTo(MasterRule::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(MasterRulePhaseRuleProcess::class, 'phase_rule_id')->orderBy('sort_order');
    }
}

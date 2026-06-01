<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessDrawing extends Model
{
    protected $fillable = [
        'rule_process_id',
        'drawing_type',
        'title',
        'image_path',
        'image_width',
        'image_height',
    ];

    public function ruleProcess(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRuleProcess::class, 'rule_process_id');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(ProcessDrawingElement::class, 'drawing_id')->orderBy('sort_order');
    }
}

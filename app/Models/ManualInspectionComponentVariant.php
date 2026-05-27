<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualInspectionComponentVariant extends Model
{
    protected $fillable = [
        'inspection_component_id',
        'component_id',
    ];

    public function inspectionComponent(): BelongsTo
    {
        return $this->belongsTo(ManualInspectionComponent::class, 'inspection_component_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}

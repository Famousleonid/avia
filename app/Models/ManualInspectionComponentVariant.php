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

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'inspection_component_id' => 'integer',
        'component_id'            => 'integer',
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

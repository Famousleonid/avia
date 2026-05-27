<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ManualInspectionComponent extends Model
{
    protected $fillable = [
        'manual_id',
        'label',
        'sort_order',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ManualInspectionComponentVariant::class, 'inspection_component_id')
            ->with('component');
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Component::class, 'manual_inspection_component_variants',
            'inspection_component_id', 'component_id')
            ->withTimestamps();
    }
}

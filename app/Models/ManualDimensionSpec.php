<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ManualDimensionSpec extends Model
{
    protected $fillable = [
        'manual_dimension_point_id',
        'spec_type',
        'component_id',
        'codes_id',
        'description',
        'is_required',
        'orig_dim_min',
        'orig_dim_max',
        'wear_dim_min',
        'wear_dim_max',
        'inspection',
        'sort_order',
    ];

    protected $casts = [
        'is_required'       => 'boolean',
        'orig_dim_min'      => 'decimal:4',
        'orig_dim_max'      => 'decimal:4',
        'wear_dim_min'      => 'decimal:4',
        'wear_dim_max'      => 'decimal:4',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionPoint::class, 'manual_dimension_point_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Code::class, 'codes_id');
    }

    public function specCodes(): HasMany
    {
        return $this->hasMany(ManualDimensionSpecCode::class, 'manual_dimension_spec_id');
    }

    public function repairRules(): HasMany
    {
        return $this->hasMany(ManualDimensionRepairRule::class, 'manual_dimension_spec_id');
    }

    public function bushingSpec(): HasOne
    {
        return $this->hasOne(ManualBushingSpec::class, 'hole_spec_id');
    }

    public function effectiveLimits(bool $useWear): array
    {
        if ($useWear && $this->wear_dim_min !== null) {
            return ['source' => 'wear', 'min' => $this->wear_dim_min, 'max' => $this->wear_dim_max];
        }

        return ['source' => 'orig', 'min' => $this->orig_dim_min, 'max' => $this->orig_dim_max];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualDimensionPoint extends Model
{
    protected $fillable = [
        'manual_dimension_figure_id',
        'point_type',
        'child_figure_id',
        'child_ic_id',
        'code',
        'description',
        'is_fits_clearance',
        'x_pct',
        'y_pct',
        'width_pct',
        'height_pct',
        'x2_pct',
        'y2_pct',
        'label_x_pct',
        'label_y_pct',
        'extra_anchors',
        'rotation_deg',
        'sort_order',
    ];

    protected $casts = [
        'is_fits_clearance' => 'boolean',
        'extra_anchors'     => 'array',
        'rotation_deg'      => 'float',
        // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
        // and the figure renderer matches them strictly (inspComponents.find(c =>
        // c.id === pt.child_ic_id)). "25" === 25 → false → the part isn't found and
        // the callout shows the raw fallback code "lbl_25" instead of the name.
        'manual_dimension_figure_id' => 'integer',
        'child_figure_id'            => 'integer',
        'child_ic_id'                => 'integer',
    ];

    public function figure(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionFigure::class, 'manual_dimension_figure_id');
    }

    public function childFigure(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionFigure::class, 'child_figure_id');
    }

    public function childIc(): BelongsTo
    {
        return $this->belongsTo(ManualInspectionComponent::class, 'child_ic_id');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ManualDimensionSpec::class, 'manual_dimension_point_id')->orderBy('sort_order');
    }

    public function parameters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            ManualParameter::class,
            'manual_parameter_points',
            'manual_dimension_point_id',
            'manual_parameter_id'
        )->withPivot('id');
    }

    public function isNavigation(): bool
    {
        return $this->point_type === 'navigation';
    }

    public function isMeasurement(): bool
    {
        return $this->point_type === 'measurement';
    }
}

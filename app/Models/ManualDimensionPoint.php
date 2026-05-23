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
        'sort_order',
    ];

    protected $casts = [
        'is_fits_clearance' => 'boolean',
    ];

    public function figure(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionFigure::class, 'manual_dimension_figure_id');
    }

    public function childFigure(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionFigure::class, 'child_figure_id');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ManualDimensionSpec::class, 'manual_dimension_point_id')->orderBy('sort_order');
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

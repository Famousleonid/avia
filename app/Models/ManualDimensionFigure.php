<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualDimensionFigure extends Model
{
    protected $fillable = [
        'manual_id',
        'parent_figure_id',
        'figure_type',
        'title',
        'image_path',
        'image_width',
        'image_height',
        'sort_order',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'manual_id'        => 'integer',
        'parent_figure_id' => 'integer',
        'image_width'      => 'integer',
        'image_height'     => 'integer',
        'sort_order'       => 'integer',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function parentFigure(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionFigure::class, 'parent_figure_id');
    }

    public function childFigures(): HasMany
    {
        return $this->hasMany(ManualDimensionFigure::class, 'parent_figure_id')->orderBy('sort_order');
    }

    public function points(): HasMany
    {
        return $this->hasMany(ManualDimensionPoint::class, 'manual_dimension_figure_id')->orderBy('sort_order');
    }
}

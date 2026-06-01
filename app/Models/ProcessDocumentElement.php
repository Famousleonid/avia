<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessDocumentElement extends Model
{
    protected $fillable = [
        'page_id',
        'element_type',
        'x_pct',
        'y_pct',
        'x2_pct',
        'y2_pct',
        'label_x_pct',
        'label_y_pct',
        'mask',
        'value_source',
        'static_value',
        'source_parameter_id',
        'placeholder',
        'text',
        'sort_order',
    ];

    protected $casts = [
        'x_pct'        => 'decimal:2',
        'y_pct'        => 'decimal:2',
        'x2_pct'       => 'decimal:2',
        'y2_pct'       => 'decimal:2',
        'label_x_pct'  => 'decimal:2',
        'label_y_pct'  => 'decimal:2',
        'static_value' => 'decimal:4',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(ProcessDocumentPage::class, 'page_id');
    }

    public function sourceParameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'source_parameter_id');
    }
}

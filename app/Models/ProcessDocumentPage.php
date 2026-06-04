<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessDocumentPage extends Model
{
    protected $fillable = [
        'document_id',
        'parameter_id', // EC: the "place" (ManualParameter) this page documents; null = generic
        'page_no',
        'image_path',
        'image_width',
        'image_height',
        'sort_order',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ProcessDocument::class, 'document_id');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(ProcessDocumentElement::class, 'page_id')->orderBy('sort_order');
    }
}

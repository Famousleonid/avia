<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcessDocument extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'doc_type',
        'title',
        'sort_order',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'documentable_id' => 'integer',
        'sort_order'      => 'integer',
    ];

    /** Owning process — ManualParameterRuleProcess (Main) or MasterRulePhaseRuleProcess (Start/Finish). */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ProcessDocumentPage::class, 'document_id')->orderBy('sort_order')->orderBy('page_no');
    }
}

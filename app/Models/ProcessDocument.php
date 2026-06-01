<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessDocument extends Model
{
    protected $fillable = [
        'rule_process_id',
        'doc_type',
        'title',
        'sort_order',
    ];

    public function ruleProcess(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRuleProcess::class, 'rule_process_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ProcessDocumentPage::class, 'document_id')->orderBy('sort_order')->orderBy('page_no');
    }
}

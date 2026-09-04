<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderMediaSequence extends Model
{
    protected $fillable = [
        'workorder_id',
        'collection_name',
        'last_sequence',
    ];

    protected $casts = [
        'workorder_id' => 'integer',
        'last_sequence' => 'integer',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }
}

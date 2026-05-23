<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualRepairProcedure extends Model
{
    protected $fillable = [
        'manual_id',
        'name',
        'description',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ManualRepairProcedureStep::class)->orderBy('sort_order');
    }
}

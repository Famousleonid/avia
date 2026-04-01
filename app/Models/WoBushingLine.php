<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoBushingLine extends Model
{
    protected $fillable = [
        'wo_bushing_id',
        'workorder_id',
        'component_id',
        'qty',
        'qty_remaining',
        'group_key',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'integer',
        'qty_remaining' => 'integer',
        'sort_order' => 'integer',
    ];

    public function woBushing(): BelongsTo
    {
        return $this->belongsTo(WoBushing::class, 'wo_bushing_id');
    }

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(WoBushingProcess::class, 'wo_bushing_line_id');
    }
}

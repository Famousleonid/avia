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
        'codes_id',
        'qty',
        'qty_remaining',
        'do_not_order',
        'group_key',
        'sort_order',
        'selection_active',
    ];

    protected $casts = [
        'qty' => 'integer',
        'codes_id' => 'integer',
        'qty_remaining' => 'integer',
        'do_not_order' => 'boolean',
        'sort_order' => 'integer',
        'selection_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Deselection retains the line and its processes for a later restore.
        static::addGlobalScope('selected', fn ($query) => $query->where('wo_bushing_lines.selection_active', true));
    }

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

    public function codes(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
}

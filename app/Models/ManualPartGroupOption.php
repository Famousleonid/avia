<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManualPartGroupOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'manual_part_group_id',
        'component_id',
        'part_number',
        'ipl_num',
        'label',
        'option_kind',
        'oversize_value',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'component_id' => 'integer',
        'oversize_value' => 'decimal:4',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroup::class, 'manual_part_group_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function coverages(): HasMany
    {
        return $this->hasMany(ManualPartGroupCoverage::class)->orderBy('id');
    }

    public function incomingCoverages(): HasMany
    {
        return $this->hasMany(ManualPartGroupCoverage::class, 'covered_manual_part_group_option_id');
    }
}

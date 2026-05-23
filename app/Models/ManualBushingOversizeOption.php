<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualBushingOversizeOption extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'manual_bushing_spec_id',
        'oversize_value',
        'part_number',
        'description',
    ];

    protected $casts = [
        'oversize_value' => 'decimal:4',
    ];

    public function bushingSpec(): BelongsTo
    {
        return $this->belongsTo(ManualBushingSpec::class, 'manual_bushing_spec_id');
    }
}

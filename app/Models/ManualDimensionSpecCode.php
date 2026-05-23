<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualDimensionSpecCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'manual_dimension_spec_id',
        'codes_id',
    ];

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ManualDimensionSpec::class, 'manual_dimension_spec_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
}

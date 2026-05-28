<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualParameterCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'manual_parameter_id',
        'codes_id',
        'finding_context',
    ];

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'manual_parameter_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
}

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

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'manual_parameter_id' => 'integer',
        'codes_id'            => 'integer',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualParameterRuleTrigger extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'repair_rule_id',
        'trigger',
        'codes_id',
    ];

    // FK ids as integers — some PDO/PHP setups return them as strings ("25"),
    // and the Dimensions/Measurements JS matches ids strictly (===).
    protected $casts = [
        'repair_rule_id' => 'integer',
        'codes_id'       => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ManualParameterRepairRule::class, 'repair_rule_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
}

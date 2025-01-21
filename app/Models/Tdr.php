<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tdr extends Model
{
    use HasFactory;
    protected $fillable = [
        'workorder_id',
        'component_id',
        'serial_number',
        'assy_serial_number',
        'codes_id',
        'conditions_id',
        'necessaries_id',
        'use_tdr',
        'use_process_forms',
        'use_log_card',
        'use_extra_forms',
    ];
    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }
}

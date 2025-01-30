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
        'qty',
        'use_tdr',
        'use_process_forms',
        'use_log_card',
        'use_extra_forms',
    ];
    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }
    public function component()
    {
        return $this->belongsTo(Component::class);
    }
    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }
    public function codes()
    {
        return $this->belongsTo(Code::class);
    }
}

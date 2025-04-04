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
        'description',
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

    public function conditions()
    {
        return $this->belongsTo(Condition::class, 'conditions_id');
    }

    public function necessaries()
    {
        return $this->belongsTo(Necessary::class, 'necessaries_id');
    }

    public function codes()
    {
        return $this->belongsTo(Code::class, 'codes_id');
    }
    public function tdrProcesses()
    {
        return $this->hasMany(TdrProcess::class, 'tdrs_id');
    }
    public function processName()
    {
        return $this->belongsTo(ProcessName::class, 'process_names_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id'); // Предполагаем, что process_id связывает с Process
    }
}

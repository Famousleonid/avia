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
        'code_id',
        'conditions_id',
        'necessaries_id',
    ];
    public function workorder()
    {
        return $this->belongsTo(Workorder::class, 'workorder_id');
    }
}
